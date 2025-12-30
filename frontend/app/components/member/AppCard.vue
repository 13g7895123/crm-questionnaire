<template>
  <NuxtLink :to="href" class="group block p-8 bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-200 flex flex-col items-center justify-center text-center h-full border border-gray-100">
    <div class="mb-4 w-20 h-20 rounded-full bg-blue-50 text-blue-600 transition-colors group-hover:bg-blue-600 group-hover:text-white flex items-center justify-center">
      <div class="text-4xl leading-none flex items-center justify-center">{{ icon }}</div>
    </div>
    <h3 class="text-lg font-bold text-gray-900">{{ title }}</h3>
  </NuxtLink>
</template>

<script setup lang="ts">
defineProps<{
  title: string
  icon: string
  href: string
}>()
</script>
