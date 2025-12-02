<template>
  <NuxtLink :to="href" class="block p-6 bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
    <div class="text-4xl mb-4">{{ icon }}</div>
    <h3 class="text-xl font-bold mb-2">{{ title }}</h3>
    <p class="text-gray-600">{{ description }}</p>
  </NuxtLink>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    title: string
    description: string
    icon: string
    href: string
  }>(),
  {}
)
</script>
