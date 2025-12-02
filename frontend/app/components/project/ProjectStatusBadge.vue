<template>
  <span class="px-2 py-1 rounded text-sm" :class="statusClass">{{ status }}</span>
</template>
<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  status: string
}>()

const statusClass = computed(() => ({
  'bg-gray-100 text-gray-800': props.status === 'DRAFT',
  'bg-blue-100 text-blue-800': props.status === 'IN_PROGRESS',
  'bg-yellow-100 text-yellow-800': props.status === 'SUBMITTED',
  'bg-purple-100 text-purple-800': props.status === 'REVIEWING',
  'bg-green-100 text-green-800': props.status === 'APPROVED',
  'bg-red-100 text-red-800': props.status === 'RETURNED'
}))
</script>