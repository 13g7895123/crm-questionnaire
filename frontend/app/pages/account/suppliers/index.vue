<template>
  <NuxtLayout name="account">
    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('suppliers.management') }}</h1>
          <p class="mt-1 text-sm text-gray-500">管理供應商及其帳戶</p>
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
          @click="fetchSuppliers"
        />
      </div>

      <!-- Table -->
      <DataTable
        v-model="selected"
        :rows="paginatedSuppliers"
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
                    <li>供應商名稱不可重複</li>
                    <li>所有匯入的組織類型均為供應商</li>
                    <li>最多可匯入 1000 筆資料</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <UFormGroup label="選擇檔案" required>
            <div
              class="mt-1 relative border-2 border-dashed rounded-lg p-6 text-center transition-colors cursor-pointer"
              :class="isDragging ? 'border-primary-500 bg-primary-50' : 'border-gray-300 bg-gray-50 hover:bg-gray-100'"
              @click="fileInput?.click()"
              @dragover.prevent="isDragging = true"
              @dragleave.prevent="isDragging = false"
              @drop.prevent="handleDrop"
            >
              <input
                ref="fileInput"
                type="file"
                accept=".xlsx,.xls"
                class="hidden"
                @change="handleFileChange"
              />
              <UIcon name="i-heroicons-document-arrow-up" class="mx-auto h-12 w-12 text-gray-400 mb-2" />
              <div v-if="selectedFile" class="text-sm font-medium text-primary-700">
                已選擇：{{ selectedFile.name }}
              </div>
              <div v-else class="text-sm text-gray-600">
                <p class="font-medium">點擊或拖曳檔案至此處上傳</p>
                <p class="text-xs text-gray-400 mt-1">支援 .xlsx, .xls 格式</p>
              </div>
            </div>
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
import { useSuppliers } from '~/composables/useSuppliers'
import { useSweetAlert } from '~/composables/useSweetAlert'
import { useBreadcrumbs } from '~/composables/useBreadcrumbs'
import DataTable from '~/components/common/DataTable.vue'
import { useI18n } from 'vue-i18n'
import type { Organization } from '~/types/index'

definePageMeta({ middleware: 'auth' })

const router = useRouter()
const { t } = useI18n()
const api = useApi()
const { suppliers, loading, fetchSuppliers, createSupplier, updateSupplier, deleteSupplier, downloadImportTemplate } = useSuppliers()
const { showConfirm, showSystemAlert, closeAlert, showLoading } = useSweetAlert()
const { setBreadcrumbs } = useBreadcrumbs()

const searchQuery = ref('')
const selected = ref<Organization[]>([])
const isModalOpen = ref(false)
const isImportModalOpen = ref(false)
const editingId = ref<string | null>(null)
const form = ref({ name: '' })
const fileInput = ref<HTMLInputElement | null>(null)
const selectedFile = ref<File | null>(null)
const importing = ref(false)
const importResult = ref<any>(null)
const isDragging = ref(false)

const pagination = ref({
  page: 1,
  limit: 10,
  total: 0
})

const columns = computed(() => [
  { key: 'name', label: t('common.name'), sortable: true },
  { key: 'type', label: t('common.type'), sortable: true },
])

// Filtering
const filteredSuppliers = computed(() => {
  if (!searchQuery.value) return suppliers.value
  const query = searchQuery.value.toLowerCase()
  return suppliers.value.filter(s => 
    s.name.toLowerCase().includes(query)
  )
})

// Pagination
const paginatedSuppliers = computed(() => {
  const start = (pagination.value.page - 1) * pagination.value.limit
  const end = start + pagination.value.limit
  return filteredSuppliers.value.slice(start, end)
})

watch(filteredSuppliers, (newVal) => {
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

const openEditModal = (supplier: Organization) => {
  form.value = { name: supplier.name }
  editingId.value = supplier.id
  isModalOpen.value = true
}

const confirmDelete = async (supplier: Organization) => {
  const confirmed = await showConfirm({ text: t('common.confirmDelete') })
  if (!confirmed) return

  try {
    showLoading()
    await deleteSupplier(supplier.id)
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
      await updateSupplier(editingId.value, form.value.name)
    } else {
      await createSupplier(form.value.name)
    }
    isModalOpen.value = false
    showSystemAlert(t('common.success'), 'success')
  } catch (error: any) {
    showSystemAlert(error.message || t('common.error'), 'error')
  }
}

const navigateToMembers = (supplier: Organization) => {
  router.push(`/account/suppliers/${supplier.id}`)
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

const handleDrop = (event: DragEvent) => {
  isDragging.value = false
  const files = event.dataTransfer?.files
  if (files && files.length > 0) {
    const file = files[0]
    const ext = file.name.split('.').pop()?.toLowerCase()
    if (ext === 'xlsx' || ext === 'xls') {
      selectedFile.value = file
      importResult.value = null
    } else {
      showError('只支援 .xlsx 或 .xls 格式')
    }
  }
}

const handleImport = async () => {
  if (!selectedFile.value) return

  try {
    importing.value = true
    
    const formData = new FormData()
    formData.append('file', selectedFile.value)
    
    const response = await api.uploadFormData('/organizations/import', formData)
    
    importResult.value = response.data
    
    // Refresh suppliers list
    await fetchSuppliers()
    
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
    { label: t('suppliers.management') }
  ])
  await fetchSuppliers()
})
</script>
