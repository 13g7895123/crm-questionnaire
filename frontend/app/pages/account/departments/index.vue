<template>
  <NuxtLayout name="account">
    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('departments.management') }}</h1>
          <p class="mt-1 text-sm text-gray-500">管理部門及其成員</p>
        </div>
        <div class="flex gap-2">
          <UButton
            icon="i-heroicons-arrow-down-tray"
            color="white"
            :label="$t('common.downloadTemplate')"
            @click="downloadTemplate"
          />
          <UButton
            icon="i-heroicons-arrow-up-tray"
            color="white"
            :label="$t('common.import')"
            @click="openImportModal"
          />
          <UButton
            icon="i-heroicons-plus"
            color="primary"
            :label="$t('common.add')"
            @click="openCreateModal"
          />
        </div>
      </div>

      <!-- Filters & Search -->
      <div class="flex items-center gap-2">
        <UInput
          v-model="searchQuery"
          icon="i-heroicons-magnifying-glass"
          :placeholder="$t('common.search')"
          class="w-full sm:w-64"
        />
        <UButton
          icon="i-heroicons-arrow-path"
          color="white"
          variant="ghost"
          :loading="loading"
          @click="fetchDepartments"
        />
      </div>

      <!-- Table -->
      <DataTable
        v-model="selected"
        :rows="paginatedDepartments"
        :columns="columns"
        :loading="loading"
        :pagination="pagination"
        show-actions
        @update:page="pagination.page = $event"
        @update:limit="pagination.limit = $event"
      >
        <template #name-data="{ row }">
          <span 
            class="text-primary-600 hover:text-primary-700 cursor-pointer font-medium"
            @click="navigateToMembers(row)"
          >
            {{ row.name }}
          </span>
        </template>
        
        <template #actions-data="{ row }">
          <div class="flex items-center gap-1 justify-end">
            <UButton
              icon="i-heroicons-users"
              color="gray"
              variant="ghost"
              size="xs"
              :title="$t('users.management')"
              @click.stop="navigateToMembers(row)"
            />
            <UButton
              icon="i-heroicons-pencil-square"
              color="gray"
              variant="ghost"
              size="xs"
              @click.stop="openEditModal(row)"
            />
            <UButton
              icon="i-heroicons-trash"
              color="red"
              variant="ghost"
              size="xs"
              @click.stop="confirmDelete(row)"
            />
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Create/Edit Modal -->
    <UModal v-model="isModalOpen">
      <UCard>
        <template #header>
          <h3 class="text-lg font-semibold">
            {{ isEditing ? $t('common.edit') : $t('common.add') }}
          </h3>
        </template>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <UFormGroup :label="$t('common.name')" required>
            <UInput v-model="form.name" />
          </UFormGroup>

          <div class="flex justify-end gap-3 mt-6">
            <UButton
              color="gray"
              variant="soft"
              :label="$t('common.cancel')"
              @click="isModalOpen = false"
            />
            <UButton
              type="submit"
              color="primary"
              :label="$t('common.save')"
              :loading="loading"
            />
          </div>
        </form>
      </UCard>
    </UModal>

    <!-- Import Modal -->
    <UModal v-model="isImportModalOpen">
      <UCard>
        <template #header>
          <h3 class="text-lg font-semibold">{{ $t('common.import') }}</h3>
        </template>

        <div class="space-y-4">
          <div class="bg-blue-50 p-4 rounded-lg">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">匯入說明</h3>
                <div class="mt-2 text-sm text-blue-700">
                  <ul class="list-disc list-inside space-y-1">
                    <li>請先下載範本，填寫資料後上傳</li>
                    <li>部門將被建立在您所屬的組織下</li>
                    <li>同一組織內的部門名稱不可重複</li>
                    <li>最多可匯入 1000 筆資料</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <UFormGroup label="選擇檔案" required>
            <div class="mt-1">
              <input
                ref="fileInput"
                type="file"
                accept=".xlsx,.xls"
                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
                @change="handleFileChange"
              />
            </div>
            <p v-if="selectedFile" class="mt-2 text-sm text-gray-600">
              已選擇：{{ selectedFile.name }}
            </p>
          </UFormGroup>

          <div v-if="importResult" class="space-y-2">
            <div class="p-4 rounded-lg" :class="importResult.success > 0 ? 'bg-green-50' : 'bg-yellow-50'">
              <div class="text-sm font-medium" :class="importResult.success > 0 ? 'text-green-800' : 'text-yellow-800'">
                {{ importResult.message }}
              </div>
              <div class="mt-2 text-sm" :class="importResult.success > 0 ? 'text-green-700' : 'text-yellow-700'">
                <p>成功：{{ importResult.success }} 筆</p>
                <p>跳過：{{ importResult.skipped }} 筆</p>
                <p>總計：{{ importResult.total }} 筆</p>
              </div>
            </div>

            <div v-if="importResult.errors && importResult.errors.length > 0" class="bg-red-50 p-4 rounded-lg max-h-60 overflow-y-auto">
              <div class="text-sm font-medium text-red-800 mb-2">錯誤訊息：</div>
              <ul class="text-sm text-red-700 space-y-1">
                <li v-for="(error, index) in importResult.errors" :key="index" class="flex items-start">
                  <span class="mr-2">•</span>
                  <span>{{ error }}</span>
                </li>
              </ul>
            </div>
          </div>

          <div class="flex justify-end gap-3 mt-6">
            <UButton
              color="gray"
              variant="soft"
              :label="$t('common.cancel')"
              @click="closeImportModal"
            />
            <UButton
              color="primary"
              :label="$t('common.upload')"
              :loading="importing"
              :disabled="!selectedFile"
              @click="handleImport"
            />
          </div>
        </div>
      </UCard>
    </UModal>
  </NuxtLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '~/composables/useApi'
import { useDepartments } from '~/composables/useDepartments'
import { useSweetAlert } from '~/composables/useSweetAlert'
import { useBreadcrumbs } from '~/composables/useBreadcrumbs'
import DataTable from '~/components/common/DataTable.vue'
import { useI18n } from 'vue-i18n'
import type { Department } from '~/types/index'

definePageMeta({ middleware: 'auth' })

const router = useRouter()
const { t } = useI18n()
const api = useApi()
const { departments, loading, fetchDepartments, createDepartment, updateDepartment, deleteDepartment, downloadImportTemplate } = useDepartments()
const { showConfirm, showSystemAlert, closeAlert, showLoading } = useSweetAlert()
const { setBreadcrumbs } = useBreadcrumbs()

const searchQuery = ref('')
const selected = ref<Department[]>([])
const isModalOpen = ref(false)
const isImportModalOpen = ref(false)
const editingId = ref<string | null>(null)
const form = ref({ name: '' })
const fileInput = ref<HTMLInputElement | null>(null)
const selectedFile = ref<File | null>(null)
const importing = ref(false)
const importResult = ref<any>(null)

const pagination = ref({
  page: 1,
  limit: 10,
  total: 0
})

const columns = computed(() => [
  { key: 'name', label: t('common.name'), sortable: true },
])

// Filtering
const filteredDepartments = computed(() => {
  if (!searchQuery.value) return departments.value
  const query = searchQuery.value.toLowerCase()
  return departments.value.filter(d => 
    d.name.toLowerCase().includes(query)
  )
})

// Pagination
const paginatedDepartments = computed(() => {
  const start = (pagination.value.page - 1) * pagination.value.limit
  const end = start + pagination.value.limit
  return filteredDepartments.value.slice(start, end)
})

watch(filteredDepartments, (newVal) => {
  pagination.value.total = newVal.length
  if (pagination.value.page > Math.ceil(newVal.length / pagination.value.limit)) {
    pagination.value.page = 1
  }
}, { immediate: true })

const isEditing = computed(() => !!editingId.value)

const resetForm = () => {
  form.value = { name: '' }
  editingId.value = null
}

const openCreateModal = () => {
  resetForm()
  isModalOpen.value = true
}

const openEditModal = (department: Department) => {
  form.value = { name: department.name }
  editingId.value = department.id
  isModalOpen.value = true
}

const confirmDelete = async (department: Department) => {
  const confirmed = await showConfirm({ text: t('common.confirmDelete') })
  if (!confirmed) return

  try {
    showLoading()
    await deleteDepartment(department.id)
    closeAlert()
    showSystemAlert(t('common.deleteSuccess'), 'success')
  } catch (error: any) {
    closeAlert()
    showSystemAlert(error.message || t('common.deleteFailed'), 'error')
  }
}

const handleSubmit = async () => {
  try {
    if (isEditing.value && editingId.value) {
      await updateDepartment(editingId.value, form.value.name)
    } else {
      await createDepartment(form.value.name)
    }
    isModalOpen.value = false
    showSystemAlert(t('common.success'), 'success')
  } catch (error: any) {
    showSystemAlert(error.message || t('common.error'), 'error')
  }
}

const navigateToMembers = (department: Department) => {
  router.push(`/account/departments/${department.id}`)
}

const downloadTemplate = async () => {
  try {
    await downloadImportTemplate()
    showSystemAlert('範本下載成功', 'success')
  } catch (error: any) {
    console.error('Download template error:', error)
    showSystemAlert(error.message || '下載範本失敗', 'error')
  }
}

const openImportModal = () => {
  selectedFile.value = null
  importResult.value = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
  isImportModalOpen.value = true
}

const closeImportModal = () => {
  isImportModalOpen.value = false
  selectedFile.value = null
  importResult.value = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    selectedFile.value = target.files[0]
    importResult.value = null
  }
}

const handleImport = async () => {
  if (!selectedFile.value) return

  try {
    importing.value = true
    
    const formData = new FormData()
    formData.append('file', selectedFile.value)
    
    const response = await api.uploadFormData('/departments/import', formData)
    
    importResult.value = response.data
    
    // Refresh departments list
    await fetchDepartments()
    
    // Show success message
    if (response.data.success > 0) {
      showSystemAlert(response.data.message, 'success')
    }
  } catch (error: any) {
    showSystemAlert(error.message || '匯入失敗', 'error')
  } finally {
    importing.value = false
  }
}

onMounted(async () => {
  setBreadcrumbs([
    { label: t('member.account') },
    { label: t('departments.management') }
  ])
  await fetchDepartments()
})
</script>
