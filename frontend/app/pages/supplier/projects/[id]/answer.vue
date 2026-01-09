<template>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <!-- Loading -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-12">
      <UIcon name="i-heroicons-arrow-path" class="w-12 h-12 text-primary-500 animate-spin" />
      <p class="mt-4 text-gray-600">載入問卷中...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="text-center py-12 bg-red-50 rounded-lg border border-red-200">
      <div class="text-red-600 font-medium">{{ error }}</div>
      <UButton
        class="mt-4"
        color="primary"
        variant="soft"
        @click="init"
      >
        重試
      </UButton>
    </div>

    <ClientOnly v-else>
      <!-- 分流：根據專案類型顯示不同介面 -->
      <RMQuestionnairePortal
        v-if="project?.type === 'CONFLICT'"
        :id="id"
        @back="router.back()"
        @submitted="handleSubmitted"
      />
      <QuestionnaireWizard
        v-else
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
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import QuestionnaireWizard from '~/components/questionnaire/QuestionnaireWizard.vue'
import RMQuestionnairePortal from '~/components/conflict/RMQuestionnairePortal.vue'
import { useResponsibleMinerals } from '~/composables/useResponsibleMinerals'

definePageMeta({ middleware: 'auth' })

const route = useRoute()
const router = useRouter()
const { getAssignmentInfo } = useResponsibleMinerals()

const id = computed(() => route.params.id as string)
const loading = ref(true)
const error = ref('')
const project = ref<any>(null)

const init = async () => {
  loading.value = true
  error.value = ''
  try {
    const data = await getAssignmentInfo(Number(id.value))
    project.value = data.assignment.project
  } catch (err: any) {
    error.value = err.message || '無法載入專案資訊'
  } finally {
    loading.value = false
  }
}

const handleSaved = () => {
  // Optional
}

const handleSubmitted = () => {
  router.push('/supplier/projects')
}

onMounted(() => {
  init()
})
</script>