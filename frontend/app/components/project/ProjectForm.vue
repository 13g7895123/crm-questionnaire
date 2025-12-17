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

        <!-- 範本選擇 -->
        <UFormGroup :label="$t('templates.template')" required>
          <USelectMenu
            v-model="selectedTemplate"
            :options="templateOptions"
            option-attribute="label"
            value-attribute="value"
            :placeholder="$t('templates.selectTemplate')"
            :disabled="loading || templatesLoading"
            :loading="templatesLoading"
          />
        </UFormGroup>

        <!-- 範本版本 -->
        <UFormGroup v-if="selectedTemplate" :label="$t('templates.version')" required>
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
        <UFormGroup :label="$t('suppliers.supplier')" required>
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
                  variant="subtle"
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
import { useProjects } from '~/composables/useProjects'
import { useTemplates } from '~/composables/useTemplates'
import { useSuppliers } from '~/composables/useSuppliers'
import { useDepartments } from '~/composables/useDepartments'
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
const { suppliers, fetchSuppliers } = useSuppliers()
const { departments, fetchDepartments } = useDepartments()

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
  supplierIds: [] as string[],
  reviewConfig: [{ stageOrder: 1, departmentId: '' }] as { stageOrder: number; departmentId: string }[]
})

const templateOptions = computed(() => 
  templates.value
    .filter(t => t.type === props.projectType)
    .map(t => ({
      label: t.name,
      value: t.id
    }))
)

// ... existing computed properties ...
const versionOptions = computed(() => {
  if (!selectedTemplate.value) return []
  const template = templates.value.find(t => t.id === selectedTemplate.value)
  if (!template?.versions) return []
  return template.versions.map(v => ({
    label: v.version,
    value: v.version
  }))
})

const supplierOptions = computed(() =>
  suppliers.value.map((s: any) => ({
    label: s.name,
    value: s.id
  }))
)

const departmentOptions = computed(() =>
  departments.value.map(d => ({
    label: d.name,
    value: d.id
  }))
)

const resetForm = () => {
  form.value = {
    name: '',
    year: 0,
    templateId: '',
    templateVersion: '',
    supplierIds: [],
    reviewConfig: [{ stageOrder: 1, departmentId: '' }]
  }
  selectedTemplate.value = undefined
}

watch(selectedTemplate, (newVal) => {
  form.value.templateId = newVal || ''
  // Only reset version if template changed and it's not the initial load of editing project
  // preventing overwrite if we just set it from project data
  // But here we rely on form.value.templateVersion being set after this if needed?
  // Actually, standard behavior: if template changes, version resets. 
  // We need to be careful not to reset it when we programmatically set templateId during edit load.
  // We can handle that by setting version AFTER setting templateId in the load function.
  
  if (newVal) {
     // If the current version in form is not valid for this template, reset it? 
     // Or just let UI handle it. 
     // For safety, if user manually changes template, we reset version.
     // But we need to distinguish manual change vs programmatic set.
     // For now, let's just default to latest if not set or invalid?
     // We'll skip auto-select logic here to avoid overriding loaded data, 
     // unless we add a check.
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
    // 1. Fetch options first
    await Promise.all([
      fetchTemplates(props.projectType),
      fetchSuppliers(),
      fetchDepartments()
    ])

    // 2. If editing, fetch full project details and populate form
    if (isEditing.value && props.project?.id) {
       const { data: projectData } = await getProject(props.project.id)
       
       if (projectData) {
         form.value.name = projectData.name
         form.value.year = projectData.year
         form.value.templateId = projectData.templateId
         selectedTemplate.value = projectData.templateId // This triggers watch
         
         // Set version (need to wait for watch to validly set options? or just set it)
         form.value.templateVersion = projectData.templateVersion
         
         // Populate suppliers
         if (projectData.suppliers && Array.isArray(projectData.suppliers)) {
           form.value.supplierIds = projectData.suppliers.map((s: any) => s.supplierId)
         } else if (projectData.supplierId) {
           form.value.supplierIds = [projectData.supplierId]
         } else {
           form.value.supplierIds = []
         }

         // Populate review config
         if (projectData.reviewConfig && Array.isArray(projectData.reviewConfig)) {
           form.value.reviewConfig = projectData.reviewConfig.map((r: any, i: number) => ({
             stageOrder: i + 1,
             departmentId: r.departmentId
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

const closeModal = () => {
  isOpen.value = false
  resetForm()
}

const handleSubmit = async () => {
  if (!form.value.name.trim()) return

  // Validate required fields (same for create and edit now)
  if (!form.value.templateId || !form.value.templateVersion) return
  if (form.value.supplierIds.length === 0) return
  if (!form.value.reviewConfig.every(r => r.departmentId)) return

  loading.value = true
  try {
    let result
    const payload = {
      name: form.value.name,
      year: form.value.year,
      templateId: form.value.templateId,
      templateVersion: form.value.templateVersion,
      supplierIds: form.value.supplierIds,
      reviewConfig: form.value.reviewConfig
    }

    if (isEditing.value && props.project) {
      result = await updateProject(props.project.id, payload)
    } else {
      result = await createProject({
        ...payload,
        type: props.projectType
      })
    }
    
    emit('saved', result.data)
    closeModal()
  } catch (error) {
    console.error('Failed to save project:', error)
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