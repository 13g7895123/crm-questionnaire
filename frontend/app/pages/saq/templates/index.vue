<template>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="w-full">
      <div class="flex items-center gap-4 mb-8">
        <UButton
          icon="i-heroicons-arrow-left"
          color="gray"
          variant="ghost"
          to="/saq/projects"
        />
        <h1 class="text-3xl font-bold text-gray-900">{{ $t('templates.management') }}</h1>
      </div>

      <!-- Toolbar -->
      <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
        <!-- Left: Actions -->
        <div class="flex items-center gap-2 w-full sm:w-auto">
          <UButton
            icon="i-heroicons-plus"
            color="primary"
            :label="$t('common.add')"
            @click="openCreateModal"
          />
          <UButton
            icon="i-heroicons-pencil-square"
            color="white"
            :label="$t('common.edit')"
            :disabled="!selected.length || selected.length > 1"
            @click="openEditModal"
          />
          <UButton
            icon="i-heroicons-trash"
            color="white"
            class="text-red-600 hover:bg-red-50"
            :label="$t('common.delete')"
            :disabled="!selected.length"
            @click="handleDelete"
          />
          <UButton
            icon="i-heroicons-eye"
            color="blue"
            :label="$t('common.preview')"
            :disabled="!canPreview"
            @click="openPreview(selected[0])"
          />
          <UButton
            icon="i-heroicons-arrow-down-tray"
            color="white"
            :label="$t('common.importQuestions')"
            :disabled="!selected.length || selected.length > 1"
            @click="openImportModal"
          />
          <UButton
            icon="i-heroicons-beaker"
            color="white"
            :label="$t('common.test')"
            @click="navigateTo('/saq/templates/test')"
          />
        </div>

        <!-- Right: Search & Refresh -->
        <div class="flex items-center gap-2 w-full sm:w-auto">
          <UInput
            v-model="searchQuery"
            icon="i-heroicons-magnifying-glass"
            :placeholder="$t('common.search')"
            class="w-full sm:w-64"
          />
          <UButton
            icon="i-heroicons-arrow-path"
            color="white"
            :label="$t('common.refresh')"
            :loading="loading"
            @click="refreshData"
          />
        </div>
      </div>

      <!-- Table -->
      <DataTable
        v-model="selected"
        :rows="paginatedTemplates"
        :columns="columns"
        :loading="loading"
        :pagination="pagination"
        selectable
        show-actions
        @update:page="pagination.page = $event"
        @update:limit="pagination.limit = $event"
      >
        <template #name-data="{ row }">
          <span 
            class="text-primary-600 hover:text-primary-700 cursor-pointer font-medium"
            @click="openEditModal(row)"
          >
            {{ row.name }}
          </span>
        </template>

        <template #actions-data="{ row }">
          <div class="flex items-center gap-1">
            <UButton
              icon="i-heroicons-eye"
              color="blue"
              variant="ghost"
              size="xs"
              :title="$t('common.preview')"
              :disabled="!hasQuestions(row)"
              @click.stop="openPreview(row)"
            />
            <UButton
              icon="i-heroicons-pencil-square"
              color="gray"
              variant="ghost"
              size="xs"
              :title="$t('common.edit') || '編輯'"
              @click.stop="openEditModal(row)"
            />
            <UButton
              icon="i-heroicons-trash"
              color="red"
              variant="ghost"
              size="xs"
              :title="$t('common.delete') || '刪除'"
              @click.stop="handleDeleteRow(row)"
            />
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Create/Edit Modal -->
    <UModal v-model="isFormOpen">
      <UCard>
        <template #header>
          <h3 class="text-lg font-semibold">
            {{ isEditing ? $t('templates.editTemplate') : $t('templates.createTemplate') }}
          </h3>
        </template>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <UFormGroup :label="$t('templates.templateName')" required>
            <UInput v-model="form.name" />
          </UFormGroup>

          <div class="flex justify-end gap-3 mt-6">
            <UButton
              color="gray"
              variant="soft"
              :label="$t('common.cancel')"
              @click="isFormOpen = false"
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
    <UModal v-model="isImportOpen">
      <UCard>
        <template #header>
          <h3 class="text-lg font-semibold">
            {{ $t('common.importQuestions') }}
          </h3>
        </template>
        
        <div class="space-y-4">
          <p class="text-sm text-gray-500">
            匯入題目至範本: <span class="font-medium">{{ selected[0]?.name }}</span>
          </p>
          
          <div 
            class="border-2 border-dashed rounded-lg p-8 text-center transition-colors cursor-pointer"
            :class="importFile ? 'border-green-400 bg-green-50' : 'border-gray-300 bg-gray-50 hover:bg-gray-100'"
            @click="triggerImportFileInput"
            @dragover.prevent
            @drop.prevent="handleImportDrop"
          >
            <input
              ref="importFileInput"
              type="file"
              accept=".xlsx,.xls"
              class="hidden"
              @change="handleImportFileChange"
            />
            <template v-if="importFile">
              <UIcon name="i-heroicons-check-circle" class="w-12 h-12 text-green-500 mx-auto mb-2" />
              <p class="text-sm text-green-700 font-medium">{{ importFile.name }}</p>
              <p class="text-xs text-gray-500 mt-1">點擊選擇其他檔案</p>
            </template>
            <template v-else>
              <UIcon name="i-heroicons-document-arrow-up" class="w-12 h-12 text-gray-400 mx-auto mb-2" />
              <p class="text-sm text-gray-600">點擊或拖曳檔案至此處上傳</p>
              <p class="text-xs text-gray-400 mt-1">支援 .xlsx, .xls 格式</p>
            </template>
          </div>
          
          <UAlert
            v-if="importError"
            color="red"
            icon="i-heroicons-exclamation-triangle"
            :title="importError"
          />
        </div>

        <template #footer>
          <div class="flex justify-end gap-3">
            <UButton
              icon="i-heroicons-document-text"
              color="gray"
              variant="soft"
              label="查看格式範例"
              @click="navigateTo('/saq/templates/test/example')"
            />
            <UButton
              color="gray"
              variant="soft"
              :label="$t('common.cancel')"
              @click="closeImportModal"
            />
            <UButton
              color="primary"
              :label="importing ? '匯入中...' : $t('common.import')"
              :loading="importing"
              :disabled="!importFile"
              @click="handleImport"
            />
          </div>
        </template>
      </UCard>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useTemplates } from '~/composables/useTemplates'
import { useSweetAlert } from '~/composables/useSweetAlert'
import { useBreadcrumbs } from '~/composables/useBreadcrumbs'
import { useApi } from '~/composables/useApi'
import DataTable from '~/components/common/DataTable.vue'
import { useI18n } from 'vue-i18n'
import type { Template } from '~/types/index'

definePageMeta({ middleware: 'auth' })

const { t } = useI18n()
const { templates, loading, fetchTemplates, createTemplate, updateTemplate, deleteTemplate } = useTemplates()
const { showConfirmDialog, showLoading, closeAlert, showSystemAlert } = useSweetAlert()
const { setBreadcrumbs } = useBreadcrumbs()
const { uploadFormData } = useApi()

const searchQuery = ref('')
const selected = ref<Template[]>([])
const isFormOpen = ref(false)
const isImportOpen = ref(false)
const editingId = ref<string | null>(null)
const form = ref({ name: '' })

// Import states
const importFileInput = ref<HTMLInputElement | null>(null)
const importFile = ref<File | null>(null)
const importing = ref(false)
const importError = ref<string | null>(null)

const pagination = ref({
  page: 1,
  limit: 10,
  total: 0
})

const columns = computed(() => [
  {
    key: 'name',
    label: t('templates.templateName'),
    sortable: true
  },
  {
    key: 'latestVersion',
    label: t('templates.version'),
    sortable: true
  }
])

const filteredTemplates = computed(() => {
  if (!searchQuery.value) return templates.value
  const query = searchQuery.value.toLowerCase()
  return templates.value.filter(t => 
    t.name.toLowerCase().includes(query)
  )
})

const paginatedTemplates = computed(() => {
  const start = (pagination.value.page - 1) * pagination.value.limit
  const end = start + pagination.value.limit
  return filteredTemplates.value.slice(start, end)
})

watch(filteredTemplates, (newVal) => {
  pagination.value.total = newVal.length
  if (pagination.value.page > Math.ceil(newVal.length / pagination.value.limit)) {
    pagination.value.page = 1
  }
}, { immediate: true })

const loadData = async () => {
  try {
    await fetchTemplates('SAQ')
    // Clear selection after reload to avoid stale references
    selected.value = []
  } catch (e) {
    console.error('Failed to load templates:', e)
  }
}

const refreshData = () => {
  loadData()
}

const resetForm = () => {
  form.value = { name: '' }
  editingId.value = null
}

const openCreateModal = () => {
  resetForm()
  isFormOpen.value = true
}

const openEditModal = (template?: Template) => {
  const target = template || selected.value[0]
  if (!target) return
  
  form.value = { name: target.name }
  editingId.value = target.id
  isFormOpen.value = true
}

const isEditing = computed(() => !!editingId.value)

const handleSubmit = async () => {
  try {
    if (isEditing.value && editingId.value) {
      await updateTemplate(editingId.value, { name: form.value.name })
    } else {
      await createTemplate({
        name: form.value.name,
        type: 'SAQ'
      })
    }
    isFormOpen.value = false
    await loadData()
    showSystemAlert(t('common.success'), 'success')
  } catch (error) {
    console.error('Operation failed:', error)
    showSystemAlert(t('common.error'), 'error')
  }
}

const handleDelete = async () => {
  if (!selected.value.length) return
  
  const confirmed = await showConfirmDialog(t('common.confirmDelete'))
  if (!confirmed) return

  try {
    showLoading()
    await Promise.all(selected.value.map(t => deleteTemplate(t.id)))
    selected.value = []
    await loadData()
    closeAlert()
    showSystemAlert(t('common.deleteSuccess'), 'success')
  } catch (e: any) {
    console.error('Failed to delete templates:', e)
    closeAlert()
    showSystemAlert(e.message || t('common.deleteFailed'), 'error')
  }
}

const handleDeleteRow = async (row: Template) => {
  const confirmed = await showConfirmDialog(t('common.confirmDelete'))
  if (!confirmed) return

  try {
    showLoading()
    await deleteTemplate(row.id)
    selected.value = selected.value.filter(t => t.id !== row.id)
    await loadData()
    closeAlert()
    showSystemAlert(t('common.deleteSuccess'), 'success')
  } catch (e: any) {
    console.error('Failed to delete template:', e)
    closeAlert()
    showSystemAlert(e.message || t('common.deleteFailed'), 'error')
  }
}

const hasQuestions = (template: Template) => {
  // Check if template has versions or latestVersion is NOT '0.0' or just check existence
  // Assuming if latestVersion is present, it has some content, or if versions array has length
  if (template.versions && template.versions.length > 0) return true
  if (template.latestVersion && template.latestVersion !== '0.0') return true
  return false
}

const canPreview = computed(() => {
  if (selected.value.length !== 1) return false
  return hasQuestions(selected.value[0])
})


const openPreview = (template?: Template) => {
  const target = template || selected.value[0]
  if (!target) return
  
  if (!hasQuestions(target)) {
    showSystemAlert('此範本尚無題目，無法預覽', 'warning')
    return
  }

  // 導航到預覽頁面
  navigateTo(`/saq/templates/${target.id}`)
}

const openImportModal = () => {
  if (selected.value.length !== 1) return
  importFile.value = null
  importError.value = null
  isImportOpen.value = true
}

const closeImportModal = () => {
  isImportOpen.value = false
  importFile.value = null
  importError.value = null
}

const triggerImportFileInput = () => {
  importFileInput.value?.click()
}

const handleImportFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    const file = target.files[0]
    const ext = file.name.split('.').pop()?.toLowerCase()
    if (ext === 'xlsx' || ext === 'xls') {
      importFile.value = file
      importError.value = null
    } else {
      importError.value = '只支援 .xlsx 或 .xls 格式'
    }
  }
}

const handleImportDrop = (event: DragEvent) => {
  const files = event.dataTransfer?.files
  if (files && files.length > 0) {
    const file = files[0]
    const ext = file.name.split('.').pop()?.toLowerCase()
    if (ext === 'xlsx' || ext === 'xls') {
      importFile.value = file
      importError.value = null
    } else {
      importError.value = '只支援 .xlsx 或 .xls 格式'
    }
  }
}

const handleImport = async () => {
  if (!importFile.value || !selected.value[0]) return

  importing.value = true
  importError.value = null

  try {
    const formData = new FormData()
    formData.append('file', importFile.value)

    const templateId = selected.value[0].id
    const data = await uploadFormData<any>(`/templates/${templateId}/import-excel`, formData)

    if (data.success) {
      closeImportModal()
      showSystemAlert(`匯入成功！\n區段: ${data.data.metadata.totalSections}, 子區段: ${data.data.metadata.totalSubsections}, 題目: ${data.data.metadata.totalQuestions}`, 'success')
      await loadData()
    } else {
      importError.value = data.error?.message || data.message || '匯入失敗'
    }
  } catch (e: any) {
    console.error('Import failed:', e)
    importError.value = e.message || '匯入失敗'
  } finally {
    importing.value = false
  }
}

onMounted(() => {
  setBreadcrumbs([
    { label: t('common.home'), to: '/' },
    { label: t('apps.saq') },
    { label: t('projects.projectList'), to: '/saq/projects' },
    { label: t('templates.management') }
  ])
  loadData()
})
</script>