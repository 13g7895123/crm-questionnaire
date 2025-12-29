<template>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <ClientOnly>
      <QuestionnaireWizard
        mode="review"
        :project-supplier-id="supplierId"
        show-back-button
        @back="goBack"
        @finish="goBack"
      />
    </ClientOnly>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import QuestionnaireWizard from '~/components/questionnaire/QuestionnaireWizard.vue'
import { useBreadcrumbs } from '~/composables/useBreadcrumbs'

definePageMeta({ middleware: 'auth' })

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const { setBreadcrumbs } = useBreadcrumbs()

const projectId = computed(() => route.params.projectId as string)
const supplierId = computed(() => route.params.supplierId as string)

const goBack = () => {
  router.push(`/saq/projects/${projectId.value}`)
}

onMounted(() => {
  setBreadcrumbs([
    { label: t('common.home'), to: '/' },
    { label: t('apps.saq') },
    { label: t('projects.projectManagement'), to: '/saq/projects' },
    { label: t('projects.projectDetail'), to: `/saq/projects/${projectId.value}` },
    { label: t('review.review') }
  ])
})
</script>
