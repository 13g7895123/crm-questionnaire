<template>
  <select class="w-full px-3 py-2 border border-gray-300 rounded-md">
    <option v-for="dept in departments" :key="dept.id" :value="dept.id">
      {{ dept.name }}
    </option>
  </select>
</template>
<script setup lang="ts">
import { ref } from 'vue'
import type { Department } from '~/types/index'

defineProps<{
  departments: Department[]
}>()
</script>