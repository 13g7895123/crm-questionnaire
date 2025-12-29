<template>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <ClientOnly>
      <QuestionnaireWizard
        mode="fill"
        :project-supplier-id="id"
        show-back-button
        @saved="handleSaved"
        @submitted="handleSubmitted"
      />
    </ClientOnly>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import QuestionnaireWizard from '~/components/questionnaire/QuestionnaireWizard.vue'

definePageMeta({ middleware: 'auth' })

const route = useRoute()
const router = useRouter()
const id = computed(() => route.params.id as string)

const handleSaved = () => {
  // Optional: Add specific logic after save if needed
}

const handleSubmitted = () => {
  router.push('/supplier/projects')
}
</script>