<template>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <UButton
      icon="i-heroicons-arrow-left"
      color="gray"
      variant="ghost"
      class="mb-4"
      @click="router.back()"
    >
      {{ $t('common.back') }}
    </UButton>

    <QuestionnaireForm :id="id" mode="review" />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import QuestionnaireForm from '~/components/questionnaire/QuestionnaireForm.vue'

definePageMeta({ middleware: 'auth' })

const route = useRoute()
const router = useRouter()
const id = computed(() => route.params.id as string)

</script>