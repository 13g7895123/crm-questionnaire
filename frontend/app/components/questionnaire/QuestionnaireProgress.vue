<template>
  <div>Progress: {{ completed }}/{{ total }}</div>
</template>
<script setup lang="ts">
defineProps<{
  completed: number
  total: number
}>()
</script>