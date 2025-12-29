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
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Basic Info -->
          <UCard>
            <template #header>
              <h3 class="font-semibold text-gray-900">{{ $t('projects.basicInfo') }}</h3>
            </template>
            <dl class="space-y-4">
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
                <dd class="font-medium">{{ project.templateVersion }}</dd>
              </div>
            </dl>
          </UCard>

          <!-- Review Config -->
          <UCard v-if="project.reviewConfig?.length">
            <template #header>
              <h3 class="font-semibold text-gray-900">{{ $t('review.reviewFlow') }}</h3>
            </template>
            <div class="flex flex-col gap-4">
              <div
                v-for="(stage, index) in project.reviewConfig"
                :key="index"
                class="flex items-center"
              >
                <div class="px-4 py-2 rounded-lg border border-gray-200 bg-gray-50 w-full">
                  <p class="text-xs text-gray-500">{{ $t('review.stage') }} {{ stage.stageOrder }}</p>
                  <p class="font-medium">{{ stage.department?.name || stage.departmentId }}</p>
                </div>
              </div>
            </div>
          </UCard>
        </div>

        <!-- Suppliers List -->
        <UCard>
          <template #header>
            <h3 class="font-semibold text-gray-900">{{ $t('suppliers.supplierList') }}</h3>
          </template>
          <UTable
            :rows="project.suppliers || []"
            :columns="supplierColumns"
          >
            <template #status-data="{ row }">
              <ProjectStatusBadge :status="row.status" />
            </template>
            <template #currentStage-data="{ row }">
              <span>{{ row.currentStage }}</span>
            </template>
            <template #submittedAt-data="{ row }">
              {{ formatDate(row.submittedAt) }}
            </template>
            <template #actions-data="{ row }">
              <div class="flex items-center gap-2">
                <UButton
                  size="2xs"
                  color="primary"
                  variant="soft"
                  :label="$t('projects.fillQuestionnaire')"
                  @click="handleFill(row)"
                />
                <UButton
                  size="2xs"
                  color="orange"
                  variant="soft"
                  :label="$t('review.review')"
                  @click="handleAudit(row)"
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
      :project-type="project?.type || 'SAQ'"
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
import type { Project } from '~/types/index'
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

const supplierColumns = computed(() => [
  { key: 'supplierName', label: t('suppliers.supplier') },
  { key: 'status', label: t('projects.status') },
  { key: 'currentStage', label: t('review.stage') },
  { key: 'submittedAt', label: t('projects.submittedAt') },
  { key: 'actions', label: t('common.action') }
])

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

const handleFill = (row: any) => {
  router.push(`/supplier/projects/${row.id}/answer`)
}

const handleAudit = (row: any) => {
  router.push(`/review/${row.id}`)
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
      { label: t('apps.saq') },
      { label: t('projects.projectManagement'), to: '/saq/projects' },
      { label: project.value.name }
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

onMounted(() => {
  loadProject()
})
</script>