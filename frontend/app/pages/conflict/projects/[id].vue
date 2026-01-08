<template>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="w-full">
      <!-- Back Button & Title -->
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
          <UButton
            icon="i-heroicons-arrow-left"
            color="gray"
            variant="ghost"
            @click="router.back()"
          />
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ project?.name || $t('projects.projectDetail') }}</h1>
            <p class="text-sm text-gray-500 mt-1" v-if="project">
              {{ project.year }} · {{ project.type }}
            </p>
          </div>
        </div>
        <UButton
          v-if="project"
          icon="i-heroicons-pencil-square"
          color="white"
          :label="$t('common.edit')"
          @click="openEditModal"
        />
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-12">
        <UIcon name="i-heroicons-arrow-path" class="w-8 h-8 animate-spin text-primary-500" />
      </div>

      <!-- Error -->
      <div v-else-if="error" class="text-center py-12 bg-red-50 rounded-lg border border-red-200">
        <div class="text-red-600 font-medium">{{ error }}</div>
        <UButton
          class="mt-4"
          color="primary"
          variant="soft"
          :label="$t('common.retry')"
          @click="loadProject"
        />
      </div>

      <!-- Project Details -->
      <div v-else-if="project" class="space-y-6">
        <!-- Info Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Basic Info -->
          <UCard class="lg:col-span-2">
            <template #header>
              <h3 class="font-semibold text-gray-900">{{ $t('projects.basicInfo') }}</h3>
            </template>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-4">
              <div>
                <dt class="text-sm text-gray-500">{{ $t('projects.projectName') }}</dt>
                <dd class="font-medium">{{ project.name }}</dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">{{ $t('projects.projectYear') }}</dt>
                <dd class="font-medium">{{ project.year }}</dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">{{ $t('projects.projectType') }}</dt>
                <dd class="font-medium">{{ project.type }}</dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">{{ $t('templates.template') }}</dt>
                <dd class="font-medium">{{ project.template?.name || project.templateId }}</dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">{{ $t('templates.version') }}</dt>
                <dd class="font-medium text-primary-600">{{ project.templateVersion }}</dd>
              </div>
            </dl>
          </UCard>

          <!-- Review Config -->
          <UCard v-if="project.reviewConfig?.length">
            <template #header>
              <h3 class="font-semibold text-gray-900">{{ $t('review.reviewFlow') }}</h3>
            </template>
            <div class="space-y-3">
              <div
                v-for="(stage, index) in project.reviewConfig"
                :key="index"
                class="relative flex items-center"
              >
                <!-- Line between stages -->
                <div v-if="index < project.reviewConfig.length - 1" class="absolute left-4 top-8 bottom-[-12px] w-0.5 bg-gray-200"></div>
                
                <div class="z-10 flex items-center justify-center w-8 h-8 rounded-full bg-primary-50 text-primary-600 font-bold text-sm border-2 border-primary-200">
                  {{ stage.stageOrder }}
                </div>
                <div class="ml-4 flex-1 p-2 rounded-lg border border-gray-100 bg-gray-50/50">
                  <p class="text-sm font-medium">{{ stage.department?.name || stage.departmentId }}</p>
                </div>
              </div>
            </div>
          </UCard>
        </div>

        <!-- Suppliers List -->
        <UCard>
          <template #header>
            <div class="flex items-center justify-between">
              <h3 class="font-semibold text-gray-900">{{ $t('suppliers.supplierList') }}</h3>
              <UInput
                v-model="supplierSearch"
                icon="i-heroicons-magnifying-glass"
                size="sm"
                :placeholder="$t('common.search')"
                class="w-64"
              />
            </div>
          </template>

          <UTable
            :rows="filteredSuppliers"
            :columns="supplierColumns"
            :loading="loading"
          >
            <!-- 供應商名稱 -->
            <template #supplierName-data="{ row }">
              <div class="flex flex-col">
                <span class="font-medium text-gray-900">{{ row.supplierName }}</span>
                <span class="text-xs text-gray-400">ID: {{ row.supplierId }}</span>
              </div>
            </template>

            <!-- 狀態標籤 -->
            <template #status-data="{ row }">
              <ProjectStatusBadge :status="row.status" />
            </template>

            <!-- 目前審核階段 -->
            <template #currentStage-data="{ row }">
              <UBadge
                v-if="row.status !== 'APPROVED' && row.status !== 'DRAFT'"
                color="primary"
                variant="subtle"
                size="xs"
              >
                {{ $t('review.stage') }} {{ row.currentStage }}
              </UBadge>
              <span v-else>-</span>
            </template>

            <!-- 提交日期 -->
            <template #submittedAt-data="{ row }">
              <span class="text-sm text-gray-500">
                {{ row.submittedAt ? formatDate(row.submittedAt) : '-' }}
              </span>
            </template>

            <!-- 操作 -->
            <template #actions-data="{ row }">
              <div class="flex items-center gap-2">
                <UButton
                  v-if="row.status === 'SUBMITTED' || row.status === 'REVIEWING'"
                  icon="i-heroicons-check-badge"
                  size="xs"
                  color="primary"
                  variant="soft"
                  :label="$t('review.review')"
                  @click="handleReview(row)"
                />
                <UButton
                  icon="i-heroicons-eye"
                  size="xs"
                  color="gray"
                  variant="ghost"
                  @click="viewDetails(row)"
                />
              </div>
            </template>
          </UTable>
        </UCard>
      </div>
    </div>

    <!-- Edit Modal -->
    <ProjectForm
      v-model="showEditModal"
      :project-type="project?.type || 'CONFLICT'"
      :project="project"
      @saved="handleProjectSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProjects } from '~/composables/useProjects'
import ProjectStatusBadge from '~/components/project/ProjectStatusBadge.vue'
import ProjectForm from '~/components/project/ProjectForm.vue'
import type { Project, ProjectSupplier } from '~/types/index'
import { useI18n } from 'vue-i18n'
import { useBreadcrumbs } from '~/composables/useBreadcrumbs'

definePageMeta({ middleware: 'auth' })

const { t } = useI18n()
const { setBreadcrumbs } = useBreadcrumbs()
const route = useRoute()
const router = useRouter()
const { getProject } = useProjects()

const loading = ref(true)
const error = ref('')
const project = ref<Project | null>(null)
const showEditModal = ref(false)
const supplierSearch = ref('')

const supplierColumns = computed(() => [
  { key: 'supplierName', label: t('suppliers.supplier'), sortable: true },
  { key: 'status', label: t('projects.status'), sortable: true },
  { key: 'currentStage', label: t('review.stage'), sortable: true },
  { key: 'submittedAt', label: t('projects.submittedAt'), sortable: true },
  { key: 'actions', label: t('common.action') }
])

const filteredSuppliers = computed(() => {
  const suppliers = project.value?.suppliers || []
  if (!supplierSearch.value) return suppliers
  
  const search = supplierSearch.value.toLowerCase()
  return suppliers.filter(s => 
    s.supplierName.toLowerCase().includes(search) || 
    s.supplierId.toString().includes(search)
  )
})

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('zh-TW', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const loadProject = async () => {
  const id = route.params.id as string
  if (!id) {
    error.value = 'Invalid project ID'
    return
  }

  loading.value = true
  error.value = ''
  try {
    const response = await getProject(id)
    project.value = response.data

    setBreadcrumbs([
      { label: t('common.home'), to: '/' },
      { label: t('apps.conflict') },
      { label: t('projects.projectList'), to: '/conflict/projects' },
      { label: project.value?.name || '-' }
    ])
  } catch (e) {
    console.error('Failed to load project:', e)
    error.value = 'Failed to load project'
  } finally {
    loading.value = false
  }
}

const openEditModal = () => {
  showEditModal.value = true
}

const handleProjectSaved = (savedProject: Project) => {
  project.value = savedProject
  loadProject() // Reload to get fresh data
}

const handleReview = (supplier: ProjectSupplier) => {
  router.push(`/conflict/projects/${project.value?.id}/review/${supplier.supplierId}`)
}

const viewDetails = (supplier: ProjectSupplier) => {
  router.push(`/conflict/projects/${project.value?.id}/details/${supplier.supplierId}`)
}

onMounted(() => {
  loadProject()
})
</script>