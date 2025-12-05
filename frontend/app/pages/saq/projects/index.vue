<template>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="w-full">
      <h1 class="text-3xl font-bold mb-8 text-gray-900">{{ $t('projects.projectManagement') }}</h1>

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
            icon="i-heroicons-document-duplicate"
            color="white"
            :label="$t('common.copy')"
            :disabled="!selected.length"
            @click="handleCopy"
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
            icon="i-heroicons-document-text"
            color="white"
            :label="$t('templates.templates')"
            @click="openTemplateManager"
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

      <!-- Error State -->
      <div v-if="error" class="text-center py-12 bg-red-50 rounded-lg border border-red-200 mb-6">
        <div class="text-red-600 font-medium">{{ $t('projects.noProjectData') }}</div>
        <div class="text-sm text-red-500 mt-1">{{ error }}</div>
      </div>

      <!-- Table -->
      <UTable
        v-else
        v-model="selected"
        :rows="filteredProjects"
        :columns="columns"
        :loading="loading"
        class="w-full bg-white shadow-sm rounded-lg border border-gray-200"
        @select="handleRowSelect"
      >
        <template #name-data="{ row }">
          <span 
            class="text-primary-600 hover:text-primary-700 cursor-pointer font-medium"
            @click="openEditModal(row)"
          >
            {{ row.name }}
          </span>
        </template>
        
        <template #progress-data="{ row }">
          <div class="flex items-center gap-2">
            <span class="text-sm font-medium">{{ row.approvedCount || 0 }} / {{ row.supplierCount || 0 }}</span>
            <span class="text-xs text-gray-500">{{ $t('projects.approved') }}</span>
          </div>
        </template>
        
        <template #updatedAt-data="{ row }">
          {{ formatDate(row.updatedAt) }}
        </template>

        <template #actions-data="{ row }">
          <div class="flex items-center gap-1">
            <UButton
              icon="i-heroicons-pencil-square"
              color="gray"
              variant="ghost"
              size="xs"
              @click.stop="openEditModal(row)"
            />
            <UButton
              icon="i-heroicons-eye"
              color="gray"
              variant="ghost"
              size="xs"
              @click.stop="viewProject(row)"
            />
            <UButton
              icon="i-heroicons-trash"
              color="red"
              variant="ghost"
              size="xs"
              @click.stop="handleDeleteRow(row)"
            />
          </div>
        </template>

        <template #empty-state>
          <div class="flex flex-col items-center justify-center py-12 gap-3">
            <span class="italic text-sm text-gray-500">{{ $t('projects.noProjectData') }}</span>
            <UButton
              color="primary"
              variant="soft"
              :label="$t('projects.createFirst')"
              @click="openCreateModal"
            />
          </div>
        </template>
      </UTable>
    </div>

    <!-- Project Form Modal -->
    <ProjectForm
      v-model="showFormModal"
      project-type="SAQ"
      :project="editingProject"
      @saved="handleProjectSaved"
    />

    <!-- Template Manager Modal -->
    <TemplateManager
      v-model="showTemplateManager"
      type="SAQ"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useProjects } from '~/composables/useProjects'
import { useSweetAlert } from '~/composables/useSweetAlert'
import { useRouter } from 'vue-router'
import ProjectStatusBadge from '~/components/project/ProjectStatusBadge.vue'
import ProjectForm from '~/components/project/ProjectForm.vue'
import TemplateManager from '~/components/template/TemplateManager.vue'
import { useI18n } from 'vue-i18n'
import type { Project } from '~/types/index'

definePageMeta({ middleware: 'auth' })

const { t } = useI18n()
const router = useRouter()
const { fetchProjects, projects, deleteProject } = useProjects()
const { showConfirmDialog, showLoading, closeAlert, showSystemAlert } = useSweetAlert()

const loading = ref(true)
const error = ref('')
const searchQuery = ref('')
const selected = ref<Project[]>([])
const showFormModal = ref(false)
const showTemplateManager = ref(false)
const editingProject = ref<Project | null>(null)

const columns = computed(() => [
  {
    key: 'name',
    label: t('projects.projectName'),
    sortable: true
  },
  {
    key: 'year',
    label: t('projects.projectYear'),
    sortable: true
  },
  {
    key: 'progress',
    label: t('projects.progress'),
    sortable: false
  },
  {
    key: 'updatedAt',
    label: t('projects.updatedAt'),
    sortable: true
  },
  {
    key: 'actions',
    label: '',
    sortable: false
  }
])

const filteredProjects = computed(() => {
  if (!searchQuery.value) return projects.value
  const query = searchQuery.value.toLowerCase()
  return projects.value.filter(p => 
    p.name.toLowerCase().includes(query) || 
    p.year.toString().includes(query)
  )
})

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString()
}

const loadData = async () => {
  loading.value = true
  error.value = ''
  try {
    await fetchProjects('SAQ')
  } catch (e) {
    console.error('Failed to load projects:', e)
    error.value = 'Failed to load projects'
  } finally {
    loading.value = false
  }
}

const refreshData = () => {
  loadData()
}

const openCreateModal = () => {
  editingProject.value = null
  showFormModal.value = true
}

const openTemplateManager = () => {
  showTemplateManager.value = true
}

const openEditModal = (project?: Project) => {
  if (project) {
    editingProject.value = project
  } else if (selected.value.length === 1) {
    editingProject.value = selected.value[0]
  }
  showFormModal.value = true
}

const handleRowSelect = (row: Project) => {
  // Double click to edit
}

const viewProject = (project: Project) => {
  router.push(`/saq/projects/${project.id}`)
}

const handleCopy = () => {
  // Implement copy logic here
  console.log('Copy projects:', selected.value)
}

const handleDelete = async () => {
  if (!selected.value.length) return
  
  const confirmed = await showConfirmDialog(t('common.confirmDelete'))
  if (!confirmed) return

  try {
    showLoading()
    // Delete all selected projects
    await Promise.all(selected.value.map(p => deleteProject(p.id)))
    selected.value = []
    await loadData()
    closeAlert()
    showSystemAlert(t('common.deleteSuccess') || '刪除成功', 'success')
  } catch (e: any) {
    console.error('Failed to delete projects:', e)
    closeAlert()
    showSystemAlert(e.message || t('common.deleteFailed'), 'error')
  }
}

const handleDeleteRow = async (row: Project) => {
  const confirmed = await showConfirmDialog(t('common.confirmDelete'))
  if (!confirmed) return

  try {
    showLoading()
    await deleteProject(row.id)
    // Remove from selection if present
    selected.value = selected.value.filter(p => p.id !== row.id)
    await loadData()
    closeAlert()
    showSystemAlert(t('common.deleteSuccess') || '刪除成功', 'success')
  } catch (e: any) {
    console.error('Failed to delete project:', e)
    closeAlert()
    showSystemAlert(e.message || t('common.deleteFailed'), 'error')
  }
}

const handleProjectSaved = (project: Project) => {
  loadData()
  selected.value = []
}

onMounted(() => {
  loadData()
})
</script>