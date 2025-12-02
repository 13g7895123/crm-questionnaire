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
            to="/saq/projects/new"
          />
          <UButton
            icon="i-heroicons-pencil-square"
            color="white"
            :label="$t('common.edit')"
            :disabled="!selected.length || selected.length > 1"
            @click="handleEdit"
          />
          <UButton
            icon="i-heroicons-document-duplicate"
            color="white"
            :label="$t('common.copy')"
            :disabled="!selected.length"
            @click="handleCopy"
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
      >
        <template #status-data="{ row }">
          <ProjectStatusBadge :status="row.status" />
        </template>
        
        <template #updatedAt-data="{ row }">
          {{ new Date(row.updatedAt).toLocaleDateString() }}
        </template>

        <template #empty-state>
          <div class="flex flex-col items-center justify-center py-12 gap-3">
            <span class="italic text-sm text-gray-500">{{ $t('projects.noProjectData') }}</span>
          </div>
        </template>
      </UTable>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useProjects } from '~/composables/useProjects'
import { useRouter } from 'vue-router'
import ProjectStatusBadge from '~/components/project/ProjectStatusBadge.vue'
import { useI18n } from 'vue-i18n'

definePageMeta({ middleware: 'auth' })

const { t } = useI18n()
const router = useRouter()
const { fetchProjects, projects } = useProjects()

const loading = ref(true)
const error = ref('')
const searchQuery = ref('')
const selected = ref([])

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
    key: 'status',
    label: t('projects.status'),
    sortable: true
  },
  {
    key: 'updatedAt',
    label: t('projects.updatedAt'),
    sortable: true
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

const handleEdit = () => {
  if (selected.value.length === 1) {
    router.push(`/saq/projects/${selected.value[0].id}`)
  }
}

const handleCopy = () => {
  // Implement copy logic here
  console.log('Copy projects:', selected.value)
}

onMounted(() => {
  loadData()
})
</script>