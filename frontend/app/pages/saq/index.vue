<template>
  <div class="py-12 px-4 sm:px-6 lg:px-8 bg-gray-50 min-h-[calc(100vh-80px)]">
    <div class="max-w-7xl mx-auto">
      <h1 class="text-3xl font-bold mb-8 text-center">{{ $t('apps.saq') }}</h1>
      <div class="flex flex-wrap justify-center gap-6">
        <AppCard
          :title="$t('projects.projects')"
          icon="📁"
          href="/saq/projects"
          class="w-full sm:w-64 h-64"
        />
        <AppCard
          :title="$t('templates.templates')"
          icon="📝"
          href="/saq/templates"
          class="w-full sm:w-64 h-64"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import AppCard from '~/components/member/AppCard.vue'

definePageMeta({ middleware: 'auth' })
</script>