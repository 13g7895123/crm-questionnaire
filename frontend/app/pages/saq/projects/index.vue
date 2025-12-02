<template>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">{{ $t('projects.projects') }}</h1>
        <NuxtLink
          to="/saq/projects/new"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          {{ $t('projects.createProject') }}
        </NuxtLink>
      </div>

      <div v-if="loading" class="text-center py-12">
        <div class="text-gray-500">{{ $t('common.loading') }}</div>
      </div>

      <div v-else-if="error" class="text-center py-12">
        <div class="text-red-500">{{ error }}</div>
      </div>

      <div v-else-if="projects.length === 0" class="text-center py-12 bg-white rounded-lg border border-gray-200">
        <div class="text-gray-500 mb-4">{{ $t('projects.noProjects') }}</div>
        <NuxtLink
          to="/saq/projects/new"
          class="text-blue-600 hover:text-blue-700 font-medium"
        >
          {{ $t('projects.createProject') }}
        </NuxtLink>
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <ProjectCard
          v-for="project in projects"
          :key="project.id"
          :project="project"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useProjects } from '~/composables/useProjects'
import ProjectCard from '~/components/project/ProjectCard.vue'

definePageMeta({ middleware: 'auth' })

const { fetchProjects, projects } = useProjects()
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    await fetchProjects('SAQ')
  } catch (e) {
    error.value = 'Failed to load projects'
    console.error(e)
  } finally {
    loading.value = false
  }
})
</script>