<template>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="w-full">
      <ClientOnly>
        <QuestionnaireWizard
          mode="preview"
          :template-id="templateId"
          show-back-button
        />
      </ClientOnly>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import QuestionnaireWizard from '~/components/questionnaire/QuestionnaireWizard.vue'

definePageMeta({ middleware: 'auth' })

const route = useRoute()
const templateId = computed(() => route.params.id as string)

</script>