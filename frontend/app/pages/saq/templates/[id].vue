<template>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="w-full">
      <h1 class="text-3xl font-bold mb-8">{{ $t('templates.editTemplate') }}</h1>
    </div>
  </div>
</template>
<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useBreadcrumbs } from '~/composables/useBreadcrumbs'
import { useTemplates } from '~/composables/useTemplates'

definePageMeta({ middleware: 'auth' })

const { t } = useI18n()
const route = useRoute()
const { setBreadcrumbs } = useBreadcrumbs()
const { getTemplate } = useTemplates()

onMounted(async () => {
  const id = route.params.id as string
  try {
    const response = await getTemplate(id)
    const template = response.data
    
    setBreadcrumbs([
      { label: t('common.home'), to: '/' },
      { label: t('apps.saq') },
      { label: t('projects.projectManagement'), to: '/saq/projects' },
      { label: t('templates.management'), to: '/saq/templates' },
      { label: template.name }
    ])
  } catch (e) {
    console.error(e)
  }
})
</script>