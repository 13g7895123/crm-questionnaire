<template>
  <div class="space-y-6">
    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <UIcon name="i-heroicons-arrow-path" class="w-8 h-8 animate-spin text-primary-500" />
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 p-4 rounded-lg border border-red-200">
      <p class="text-red-600">{{ error }}</p>
      <UButton
        class="mt-4"
        color="primary"
        variant="soft"
        :label="$t('common.retry')"
        @click="initData"
      />
    </div>

    <!-- Content -->
    <div v-else class="space-y-8">
      <!-- Project Header -->
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ project?.name }}</h1>
        <div class="flex items-center gap-4 text-sm text-gray-500">
          <span>{{ project?.year }} · {{ project?.type }}</span>
          <span v-if="project?.template">
            {{ $t('templates.template') }}: {{ project.template.name }} (v{{ project.templateVersion }})
          </span>
        </div>
        
        <!-- Status Banner for Review Mode -->
        <div v-if="mode === 'review' && reviewData" class="mt-4 p-3 bg-gray-50 rounded border border-gray-100 flex items-center justify-between">
          <div>
            <span class="text-gray-500 mr-2">{{ $t('projects.status') }}:</span>
            <UBadge :color="getStatusColor(reviewData.currentStatus)">{{ reviewData.currentStatus }}</UBadge>
          </div>
          <div v-if="reviewData.reviews?.length" class="text-sm">
             <UButton variant="link" color="gray" @click="showReviewHistory = true">
               {{ $t('review.reviewHistory') }}
             </UButton>
          </div>
        </div>
      </div>

      <!-- Questionnaire Form -->
      <form @submit.prevent>
        <fieldset :disabled="mode === 'review' || isSubmitting">
          <div v-for="section in structure?.sections" :key="section.id" class="mb-8 bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <StepDynamicQuestions
              :section="section"
              v-model="answers"
            />
          </div>
        </fieldset>

        <!-- Fill Mode Actions -->
        <div v-if="mode === 'fill'" class="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-gray-200 shadow-lg z-10 flex justify-end gap-3">
          <UButton
            color="white"
            variant="solid"
            :loading="saving"
            :disabled="isSubmitting"
            @click="handleSave"
          >
            {{ $t('common.save') }}
          </UButton>
          <UButton
            color="primary"
            variant="solid"
            :loading="isSubmitting"
            @click="handleSubmit"
          >
            {{ $t('common.submit') }}
          </UButton>
        </div>

        <!-- Review Mode Actions -->
        <div v-if="mode === 'review'" class="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-gray-200 shadow-lg z-10">
          <div class="container mx-auto max-w-7xl flex flex-col gap-4">
            <UTextarea
              v-model="reviewComment"
              :placeholder="$t('review.comment')"
              :rows="2"
            />
            <div class="flex justify-end gap-3">
              <UButton
                color="red"
                variant="solid"
                :loading="isSubmitting"
                :disabled="!reviewComment"
                @click="handleReturn"
              >
                {{ $t('review.return') }}
              </UButton>
              <UButton
                color="green"
                variant="solid"
                :loading="isSubmitting"
                @click="handleApprove"
              >
                {{ $t('review.approve') }}
              </UButton>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- History Modal -->
    <UModal v-model="showReviewHistory">
      <UCard>
        <template #header>
          <h3 class="font-semibold">{{ $t('review.reviewHistory') }}</h3>
        </template>
        <div v-if="reviewData?.reviews" class="space-y-4">
           <div v-for="log in reviewData.reviews" :key="log.id" class="border-l-2 border-gray-200 pl-3">
              <p class="text-sm font-medium">{{ log.action }} - {{ log.reviewerName }}</p>
              <p class="text-sm text-gray-500">{{ log.timestamp }}</p>
              <p class="mt-1 text-sm bg-gray-50 p-2 rounded">{{ log.comment }}</p>
           </div>
        </div>
        <div v-else class="text-center text-gray-500 py-4">
          No history
        </div>
      </UCard>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useProjects } from '~/composables/useProjects'
import { useTemplates } from '~/composables/useTemplates'
import { useAnswers } from '~/composables/useAnswers'
import { useReview } from '~/composables/useReview'
import StepDynamicQuestions from './StepDynamicQuestions.vue'

const props = defineProps<{
  id: string // projectSupplierId
  mode: 'fill' | 'review'
}>()

const { t } = useI18n()
const router = useRouter()
const { getProject } = useProjects()
const { getTemplateStructure } = useTemplates()
const { getAnswers, saveAnswers, submitAnswers } = useAnswers()
const { getReviewLogs, approveProject, returnProject } = useReview()

const loading = ref(true)
const saving = ref(false)
const isSubmitting = ref(false)
const error = ref('')
const project = ref<any>(null)
const structure = ref<any>(null)
const answers = ref<Record<string, any>>({})
const reviewData = ref<any>(null)
const reviewComment = ref('')
const showReviewHistory = ref(false)

const getStatusColor = (status: string) => {
  const map: Record<string, string> = {
    'DRAFT': 'gray',
    'IN_PROGRESS': 'blue',
    'SUBMITTED': 'orange',
    'REVIEWING': 'orange',
    'APPROVED': 'green',
    'RETURNED': 'red'
  }
  return map[status] || 'gray'
}

const initData = async () => {
  loading.value = true
  error.value = ''
  try {
    // 1. Get Metadata (Review Logs contains projectId)
    // Note: This works for both Supplier and Host as long as they have access
    const reviewRes = await getReviewLogs(props.id) 
    reviewData.value = reviewRes
    const projectId = reviewRes.projectId

    if (!projectId) {
      throw new Error('Project ID not found')
    }

    // 2. Get Project Info (for templateId)
    const projectRes = await getProject(projectId)
    project.value = projectRes.data

    // 3. Get Template Structure
    if (project.value.templateId) {
      const structRes = await getTemplateStructure(project.value.templateId) // TODO: Handle Version?
      structure.value = structRes.data
    }

    // 4. Get Answers
    const answersRes = await getAnswers(props.id)
    if (answersRes.data?.answers) {
      answers.value = answersRes.data.answers
    } else {
      // Default empty if structure exists
      answers.value = {}
    }

  } catch (e: any) {
    console.error('Failed to init questionnaire:', e)
    error.value = e.message || 'Failed to load questionnaire data'
  } finally {
    loading.value = false
  }
}

const handleSave = async () => {
  saving.value = true
  try {
    await saveAnswers(props.id, answers.value)
    // Show toast or alert
    alert(t('common.success'))
  } catch (e) {
    console.error(e)
    alert(t('common.error'))
  } finally {
    saving.value = false
  }
}

const handleSubmit = async () => {
  if (!confirm(t('common.submit') + '?')) return
  
  isSubmitting.value = true
  try {
    await submitAnswers(props.id, answers.value)
    alert(t('questionnaire.submitSuccess'))
    router.push('/supplier/projects')
  } catch (e) {
    console.error(e)
    alert(t('common.error'))
  } finally {
    isSubmitting.value = false
  }
}

const handleApprove = async () => {
  if (!confirm(t('review.approve') + '?')) return

  isSubmitting.value = true
  try {
    await approveProject(props.id, reviewComment.value || 'Approved')
    alert(t('common.success'))
    router.push('/review')
  } catch (e) {
    console.error(e)
    alert(t('common.error'))
  } finally {
    isSubmitting.value = false
  }
}

const handleReturn = async () => {
  if (!reviewComment.value) {
    alert(t('validation.required'))
    return
  }
  if (!confirm(t('review.return') + '?')) return

  isSubmitting.value = true
  try {
    await returnProject(props.id, reviewComment.value)
    alert(t('common.success'))
    router.push('/review')
  } catch (e) {
    console.error(e)
    alert(t('common.error'))
  } finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  initData()
})
</script>
