<template>
  <div class="py-8">
    <h1 class="text-3xl font-bold mb-8">{{ $t('projects.projects') }}</h1>
    <div class="grid gap-4">
      <div class="bg-white p-6 rounded-lg shadow">
        <p class="text-gray-600">{{ $t('projects.noProjects') }}</p>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
definePageMeta({ middleware: 'auth' })
</script>