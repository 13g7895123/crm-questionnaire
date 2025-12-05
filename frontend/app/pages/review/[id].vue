<template>
  <div class="py-8">
    <h1 class="text-3xl font-bold mb-8">{{ $t('review.review') }}</h1>
  </div>
</template>
<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useBreadcrumbs } from '~/composables/useBreadcrumbs'
import { useProjects } from '~/composables/useProjects'

definePageMeta({ middleware: 'auth' })

const { t } = useI18n()
const route = useRoute()
const { setBreadcrumbs } = useBreadcrumbs()
const { getProject } = useProjects()

onMounted(async () => {
  const id = route.params.id as string
  try {
    const response = await getProject(id)
    const project = response.data
    
    setBreadcrumbs([
      { label: t('common.home'), to: '/' },
      { label: t('review.review'), to: '/review' },
      { label: project.name }
    ])
  } catch (e) {
    console.error(e)
  }
})
</script>