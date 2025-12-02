<template>
  <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-200">
    <div class="flex justify-between items-start mb-4">
      <div>
        <h3 class="text-lg font-bold text-gray-900 mb-1">{{ project.name }}</h3>
        <p class="text-sm text-gray-500">{{ project.year }}</p>
      </div>
      <ProjectStatusBadge :status="project.status" />
    </div>
    
    <div class="flex justify-between items-center text-sm text-gray-500 mt-4 pt-4 border-t border-gray-50">
      <span>{{ new Date(project.updatedAt).toLocaleDateString() }}</span>
      <NuxtLink :to="`/saq/projects/${project.id}`" class="text-blue-600 hover:text-blue-700 font-medium">
        {{ $t('common.edit') }}
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Project } from '~/types'
import ProjectStatusBadge from './ProjectStatusBadge.vue'

defineProps<{
  project: Project
}>()
</script>