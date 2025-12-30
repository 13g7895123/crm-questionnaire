<template>
  <span class="px-2 py-1 rounded text-sm whitespace-nowrap" :class="statusClass">
    {{ statusLabel }}
  </span>
</template>
<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  status: string
}>()

const { t } = useI18n()

const statusLabel = computed(() => {
  const labels: Record<string, string> = {
    'DRAFT': t('projects.draft'),
    'IN_PROGRESS': t('projects.inProgress'),
    'SUBMITTED': t('projects.submitted'),
    'REVIEWING': t('projects.reviewing'),
    'APPROVED': t('projects.approved'),
    'RETURNED': t('projects.returned')
  }
  return labels[props.status] || props.status
})

const statusClass = computed(() => ({
  'bg-gray-100 text-gray-800': props.status === 'DRAFT',
  'bg-blue-100 text-blue-800': props.status === 'IN_PROGRESS',
  'bg-yellow-100 text-yellow-800': props.status === 'SUBMITTED',
  'bg-purple-100 text-purple-800': props.status === 'REVIEWING',
  'bg-green-100 text-green-800': props.status === 'APPROVED',
  'bg-red-100 text-red-800': props.status === 'RETURNED'
}))
</script>