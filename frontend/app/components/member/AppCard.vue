<template>
  <NuxtLink :to="href" class="block p-8 bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-200 flex flex-col items-center justify-center text-center h-full border border-gray-100">
    <div class="mb-4 p-4 rounded-full bg-blue-50 text-blue-600">
      <div class="text-4xl">{{ icon }}</div>
    </div>
    <h3 class="text-lg font-bold text-gray-900">{{ title }}</h3>
  </NuxtLink>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    title: string
    icon: string
    href: string
  }>(),
  {}
)
</script>
