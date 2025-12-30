<template>
  <ProjectListTable
    project-type="SAQ"
    base-route="/saq"
    show-templates-button
  />
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useBreadcrumbs } from '~/composables/useBreadcrumbs'
import ProjectListTable from '~/components/project/ProjectListTable.vue'

definePageMeta({ middleware: 'auth' })

const { t } = useI18n()
const { setBreadcrumbs } = useBreadcrumbs()

onMounted(() => {
  setBreadcrumbs([
    { label: t('common.home'), to: '/' },
    { label: t('apps.saq') },
    { label: t('projects.projectList') }
  ])
})
</script>