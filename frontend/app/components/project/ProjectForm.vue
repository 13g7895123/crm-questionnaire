<template>
  <UModal v-model="isOpen" :ui="{ width: 'sm:max-w-2xl' }">
    <UCard>
      <template #header>
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900">
            {{ isEditing ? $t('projects.editProject') : $t('projects.addProject') }}
          </h3>
          <UButton
            color="gray"
            variant="ghost"
            icon="i-heroicons-x-mark"
            @click="closeModal"
          />
        </div>
      </template>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- 專案名稱 -->
        <UFormGroup :label="$t('projects.projectName')" required>
          <UInput
            v-model="form.name"
            :placeholder="$t('projects.projectName')"
            :disabled="loading"
          />
        </UFormGroup>

        <!-- 專案年份 -->
        <UFormGroup :label="$t('projects.projectYear')" required>
          <UInput
            v-model="form.year"
            type="number"
            :min="2020"
            :max="2100"
            :placeholder="$t('projects.projectYear')"
            :disabled="loading"
          />
        </UFormGroup>

        <!-- 範本選擇 / 範本組選擇 -->
        <UFormGroup :label="projectType === 'CONFLICT' ? '範本組' : $t('templates.template')" required>
          <USelectMenu
            v-model="selectedTemplate"
            :options="templateOptions"
            option-attribute="label"
            value-attribute="value"
            :placeholder="projectType === 'CONFLICT' ? '選擇範本組' : $t('templates.selectTemplate')"
            :disabled="loading || templatesLoading"
            :loading="templatesLoading"
          />
        </UFormGroup>

        <!-- 範本版本 (僅 SAQ 需要，RM 已包含在範本組中) -->
        <UFormGroup v-if="projectType === 'SAQ' && selectedTemplate" :label="$t('templates.version')" required>
          <USelectMenu
            v-model="form.templateVersion"
            :options="versionOptions"
            option-attribute="label"
            value-attribute="value"
            :placeholder="$t('templates.selectVersion')"
            :disabled="loading"
          />
        </UFormGroup>

        <!-- 供應商選擇 -->
        <UFormGroup>
          <template #label>
            <div class="flex items-center justify-between w-full">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                {{ $t('suppliers.supplier') }} <span class="text-red-500">*</span>
              </span>
              <div class="flex items-center gap-2">
                <UButton
                  icon="i-heroicons-document-arrow-down"
                  size="xs"
                  color="gray"
                  variant="ghost"
                  @click="downloadSupplierTemplate"
                >
                  {{ $t('suppliers.supplierListTemplate') }}
                </UButton>
                <UButton
                  icon="i-heroicons-document-arrow-up"
                  size="xs"
                  color="primary"
                  variant="ghost"
                  @click="triggerExcelImport"
                >
                  {{ $t('common.import') }}
                </UButton>
                <input
                  ref="excelInput"
                  type="file"
                  class="hidden"
                  accept=".xlsx, .xls"
                  @change="handleExcelImport"
                />
              </div>
            </div>
          </template>
          <USelectMenu
            v-model="form.supplierIds"
            :options="supplierOptions"
            option-attribute="label"
            value-attribute="value"
            :placeholder="$t('suppliers.selectSupplier')"
            :disabled="loading || suppliersLoading"
            :loading="suppliersLoading"
            searchable
            multiple
          >
            <template #label>
              <div v-if="form.supplierIds.length" class="flex flex-wrap gap-1">
                <UBadge
                  v-for="id in form.supplierIds"
                  :key="id"
                  variant="soft"
                  class="mr-1"
                >
                  {{ getSupplierName(id) }}
                  <UIcon
                    name="i-heroicons-x-mark"
                    class="ml-1 w-3 h-3 cursor-pointer hover:text-red-500"
                    @click.stop="removeSupplier(id)"
                  />
                </UBadge>
              </div>
              <span v-else class="text-gray-500">{{ $t('suppliers.selectSupplier') }}</span>
            </template>
          </USelectMenu>
        </UFormGroup>

        <!-- 審核流程設定 -->
        <UFormGroup :label="$t('review.reviewFlow')" required>
          <div class="space-y-3">
            <div
              v-for="(stage, index) in form.reviewConfig"
              :key="index"
              class="flex items-center gap-2"
            >
              <span class="text-sm text-gray-500 w-16">
                {{ $t('review.stage') }} {{ index + 1 }}
              </span>
              <USelectMenu
                v-model="stage.departmentId"
                :options="departmentOptions"
                option-attribute="label"
                value-attribute="value"
                :placeholder="$t('departments.selectDepartment')"
                class="flex-1"
                :disabled="loading || departmentsLoading"
              />
              <UButton
                v-if="form.reviewConfig.length > 1"
                icon="i-heroicons-trash"
                color="red"
                variant="ghost"
                size="sm"
                @click="removeReviewStage(index)"
              />
            </div>
            <UButton
              v-if="form.reviewConfig.length < 5"
              icon="i-heroicons-plus"
              color="gray"
              variant="soft"
              size="sm"
              :label="$t('review.addStage')"
              @click="addReviewStage"
            />
          </div>
        </UFormGroup>
      </form>

      <template #footer>
        <div class="flex justify-end gap-3">
          <UButton
            color="gray"
            variant="soft"
            :label="$t('common.cancel')"
            :disabled="loading"
            @click="closeModal"
          />
          <UButton
            color="primary"
            :label="isEditing ? $t('common.save') : $t('common.create')"
            :loading="loading"
            @click="handleSubmit"
          />
        </div>
      </template>
    </UCard>
  </UModal>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useExcel } from '~/composables/useExcel'
import { useProjects } from '~/composables/useProjects'
import { useTemplates } from '~/composables/useTemplates'
import { useTemplateSets } from '~/composables/useTemplateSets'
import { useSuppliers } from '~/composables/useSuppliers'
import { useDepartments } from '~/composables/useDepartments'
import { useSweetAlert } from '~/composables/useSweetAlert'
import { useI18n } from 'vue-i18n'
import type { Project, Template } from '~/types/index'

const props = defineProps<{
  modelValue: boolean
  projectType: 'SAQ' | 'CONFLICT'
  project?: Project | null
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'saved', project: Project): void
}>()

const { createProject, updateProject, getProject } = useProjects()
const { templates, fetchTemplates } = useTemplates()
const { templateSets, fetchTemplateSets } = useTemplateSets()
const { suppliers, fetchSuppliers, createSupplier } = useSuppliers()
const { departments, fetchDepartments } = useDepartments()
const { showSystemAlert, showConfirm } = useSweetAlert()
const { t } = useI18n()
const { parseExcel, downloadTemplate } = useExcel()

const excelInput = ref<HTMLInputElement | null>(null)

const loading = ref(false)
const templatesLoading = ref(false)
const suppliersLoading = ref(false)
const departmentsLoading = ref(false)
const selectedTemplate = ref<string | undefined>(undefined)

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const isEditing = computed(() => !!props.project?.id)

const form = ref({
  name: '',
  year: 0,
  templateId: '',
  templateVersion: '',
  template_set_id: '',
  supplierIds: [] as string[],
  reviewConfig: [{ stageOrder: 1, departmentId: '' }] as { stageOrder: number; departmentId: string }[]
})

const templateOptions = computed(() => {
  if (props.projectType === 'CONFLICT') {
    return templateSets.value.map(ts => ({
      label: ts.name,
      value: String(ts.id)
    }))
  }
  return templates.value
    .filter(t => t.type === props.projectType)
    .map(t => ({
      label: t.name,
      value: String(t.id)
    }))
})

// ... existing computed properties ...
const versionOptions = computed(() => {
  if (props.projectType === 'CONFLICT' || !selectedTemplate.value) return []
  const template = templates.value.find(t => String(t.id) === String(selectedTemplate.value))
  if (!template?.versions) return []
  return template.versions.map(v => ({
    label: v.version,
    value: v.version
  }))
})

const supplierOptions = computed(() =>
  suppliers.value.map((s: any) => ({
    label: s.name,
    value: String(s.id)
  }))
)

const departmentOptions = computed(() =>
  departments.value.map(d => ({
    label: d.name,
    value: String(d.id)
  }))
)

const resetForm = () => {
  form.value = {
    name: '',
    year: 0,
    templateId: '',
    templateVersion: '',
    template_set_id: '',
    supplierIds: [],
    reviewConfig: [{ stageOrder: 1, departmentId: '' }]
  }
  selectedTemplate.value = undefined
}

watch(selectedTemplate, (newVal) => {
  if (props.projectType === 'CONFLICT') {
    form.value.template_set_id = newVal || ''
  } else {
    form.value.templateId = newVal || ''
  }
})

// Update version options when template changes is handled by computed `versionOptions`.

watch(() => props.project, (val) => {
  // We will handle data loading in `loadInitialData` mainly, 
  // but if project prop changes while open, we might need to react.
  // However, usually modal is closed/opened.
  if (!isOpen.value) return
  
  // If we are just opening, loadInitialData will run.
})

watch(isOpen, async (open) => {
  if (open) {
    if (!isEditing.value) {
      form.value.year = new Date().getFullYear()
    }
    await loadInitialData()
  }
})

const loadInitialData = async () => {
  loading.value = true
  templatesLoading.value = true
  suppliersLoading.value = true
  departmentsLoading.value = true
  
  try {
    const promises: Promise<any>[] = [
      fetchSuppliers(),
      fetchDepartments()
    ]
    
    if (props.projectType === 'CONFLICT') {
      promises.push(fetchTemplateSets())
    } else {
      promises.push(fetchTemplates(props.projectType))
    }
    
    await Promise.all(promises)

    if (isEditing.value && props.project?.id) {
       const { data: projectData } = await getProject(props.project.id, props.projectType)
       
       if (projectData) {
         form.value.name = projectData.name
         form.value.year = projectData.year
         
         if (props.projectType === 'CONFLICT') {
           const tsId = String(projectData.template_set_id)
           form.value.template_set_id = tsId
           selectedTemplate.value = tsId
         } else {
           const tmplId = String(projectData.templateId)
           form.value.templateId = tmplId
           selectedTemplate.value = tmplId
           form.value.templateVersion = String(projectData.templateVersion)
         }
         
         if (projectData.suppliers && Array.isArray(projectData.suppliers)) {
           form.value.supplierIds = projectData.suppliers.map((s: any) => String(s.supplierId))
         } else if (projectData.supplierId) {
           form.value.supplierIds = [String(projectData.supplierId)]
         } else {
           form.value.supplierIds = []
         }

         if (projectData.reviewConfig && Array.isArray(projectData.reviewConfig)) {
           form.value.reviewConfig = projectData.reviewConfig.map((r: any, i: number) => ({
             stageOrder: i + 1,
             departmentId: String(r.departmentId)
           }))
         } else {
           form.value.reviewConfig = [{ stageOrder: 1, departmentId: '' }]
         }
       }
    }
  } catch (error) {
    console.error('Failed to load data:', error)
  } finally {
    loading.value = false
    templatesLoading.value = false
    suppliersLoading.value = false
    departmentsLoading.value = false
  }
}

const addReviewStage = () => {
  if (form.value.reviewConfig.length < 5) {
    form.value.reviewConfig.push({
      stageOrder: form.value.reviewConfig.length + 1,
      departmentId: ''
    })
  }
}

const removeReviewStage = (index: number) => {
  form.value.reviewConfig.splice(index, 1)
  form.value.reviewConfig.forEach((stage, i) => {
    stage.stageOrder = i + 1
  })
}

const getSupplierName = (id: string) => {
  const supplier = suppliers.value.find((s: any) => s.id === id)
  return supplier ? supplier.name : id
}

const removeSupplier = (id: string) => {
  form.value.supplierIds = form.value.supplierIds.filter(sid => sid !== id)
}

const triggerExcelImport = () => {
  excelInput.value?.click()
}

const downloadSupplierTemplate = async () => {
  try {
    // Generate template with all existing suppliers
    const supplierNames = suppliers.value.map((s: any) => [s.name])
    await downloadTemplate(t('suppliers.supplierTemplate'), [t('suppliers.supplierName')], supplierNames)
  } catch (error) {
    console.error('Failed to download template:', error)
  }
}

const handleExcelImport = async (event: Event) => {
  const input = event.target as HTMLInputElement
  if (!input.files?.length) return

  try {
    const rawData = await parseExcel(input.files[0])
    
    // Skip the first row (A1) as it's typically a header
    const jsonData = rawData.slice(1)
    
    // Extract names from Excel
    const importedNames = jsonData
      .map(row => row[0])
      .filter(name => name !== undefined && name !== null && name !== '')
      .map(name => String(name).trim())

    console.group('Excel Supplier Import Debug')
    console.log('Total rows after skipping header:', importedNames.length)

    if (importedNames.length === 0) {
      console.warn('No valid supplier names found in Excel.')
      showSystemAlert(t('common.importExcelNoData'), 'error')
      console.groupEnd()
      return
    }

    // Match with existing suppliers
    const matchedSupplierIds: string[] = []
    const unmatchedNames: string[] = []

    importedNames.forEach((name) => {
      // Remove ALL spaces for comparison to be robust against "Company A" vs "CompanyA"
      const normalizedExcelName = name.replace(/\s/g, '').toLowerCase()
      const supplier = suppliers.value.find(s => s.name.replace(/\s/g, '').toLowerCase() === normalizedExcelName)

      if (supplier) {
        if (!matchedSupplierIds.includes(String(supplier.id))) {
          matchedSupplierIds.push(String(supplier.id))
        }
      } else {
        unmatchedNames.push(name)
      }
    })

    console.log('Matched:', matchedSupplierIds.length, 'Unmatched:', unmatchedNames.length)
    console.groupEnd()

    // Reset input
    input.value = ''

    // If there are unmatched names, ask user if they want to create them
    if (unmatchedNames.length > 0) {
      const confirmed = await showConfirm({
        title: '發現不存在的供應商',
        text: `已成功匯入 ${matchedSupplierIds.length} 筆供應商。\n有 ${unmatchedNames.length} 筆供應商不存在：\n${unmatchedNames.slice(0, 5).join(', ')}${unmatchedNames.length > 5 ? '...' : ''}\n\n是否要建立這些供應商？`,
        icon: 'question',
        confirmButtonText: '建立',
        cancelButtonText: '跳過'
      })

      if (confirmed) {
        // Create new suppliers
        const { createSupplier } = useSuppliers()
        let createdCount = 0
        
        for (const name of unmatchedNames) {
          try {
            const result = await createSupplier(name)
            if (result.data?.id) {
              matchedSupplierIds.push(String(result.data.id))
              createdCount++
            }
          } catch (error) {
            console.error(`Failed to create supplier: ${name}`, error)
          }
        }

        // Refresh suppliers list
        await fetchSuppliers()

        showSystemAlert(`成功建立 ${createdCount} 個新供應商，共匯入 ${matchedSupplierIds.length} 個供應商`, 'success')
      }
    } else {
      showSystemAlert(t('common.importExcelSuccess', { count: matchedSupplierIds.length }), 'success')
    }

    // Update form
    const currentIds = new Set(form.value.supplierIds)
    matchedSupplierIds.forEach(id => currentIds.add(id))
    form.value.supplierIds = Array.from(currentIds)

  } catch (error) {
    console.error('Failed to parse Excel:', error)
    showSystemAlert(t('common.importExcelError'), 'error')
  }
}

const closeModal = () => {
  isOpen.value = false
  resetForm()
}

const handleSubmit = async () => {
  // 驗證專案名稱
  if (!form.value.name.trim()) {
    showSystemAlert(t('validation.required') + ': ' + t('projects.projectName'), 'error')
    return
  }

  // 驗證範本選擇
  if (props.projectType === 'CONFLICT') {
    if (!form.value.template_set_id) {
      showSystemAlert('請選擇範本組', 'error')
      return
    }
  } else {
    if (!form.value.templateId) {
      showSystemAlert(t('validation.required') + ': ' + t('templates.template'), 'error')
      return
    }
    if (!form.value.templateVersion) {
      showSystemAlert(t('validation.required') + ': ' + t('templates.version'), 'error')
      return
    }
  }

  // 驗證供應商選擇
  if (form.value.supplierIds.length === 0) {
    showSystemAlert('請至少選擇一個供應商', 'error')
    return
  }

  // 驗證審核流程
  if (!form.value.reviewConfig.every(r => r.departmentId)) {
    showSystemAlert('請為所有審核階段選擇部門', 'error')
    return
  }

  loading.value = true
  try {
    let result
    const payload: any = {
      name: form.value.name,
      year: form.value.year,
      supplierIds: form.value.supplierIds,
      reviewConfig: form.value.reviewConfig,
      type: props.projectType
    }

    if (props.projectType === 'CONFLICT') {
      // 在編輯模式下，只有當範本組真的被修改時才發送
      if (!isEditing.value || String(props.project?.template_set_id) !== String(form.value.template_set_id)) {
        payload.template_set_id = form.value.template_set_id
      }
    } else {
      // 在編輯模式下，只有當範本真的被修改時才發送
      if (!isEditing.value || String(props.project?.templateId) !== String(form.value.templateId)) {
        payload.templateId = form.value.templateId
      }
      if (!isEditing.value || String(props.project?.templateVersion) !== String(form.value.templateVersion)) {
        payload.templateVersion = form.value.templateVersion
      }
    }

    if (isEditing.value && props.project) {
      result = await updateProject(props.project.id, payload)
    } else {
      result = await createProject(payload)
    }
    
    showSystemAlert(isEditing.value ? '專案更新成功' : '專案建立成功', 'success')
    emit('saved', result.data)
    closeModal()
  } catch (error: any) {
    console.error('Failed to save project:', error)
    showSystemAlert(error?.message || '操作失敗，請稍後再試', 'error')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (isOpen.value) {
    if (!isEditing.value) {
      form.value.year = new Date().getFullYear()
    }
    loadInitialData()
  }
})
</script>