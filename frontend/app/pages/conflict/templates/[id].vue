<template>
  <div class="py-8">
    <h1 class="text-3xl font-bold mb-8">{{ $t('templates.editTemplate') }}</h1>
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
      { label: t('apps.conflict') },
      { label: t('projects.projectList'), to: '/conflict/projects' },
      { label: t('templates.management'), to: '/conflict/templates' },
      { label: template.name }
    ])
  } catch (e) {
    console.error(e)
  }
})
</script>