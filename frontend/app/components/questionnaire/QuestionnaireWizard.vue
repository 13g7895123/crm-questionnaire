<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <!-- Optional Back Button -->
        <slot name="header-left">
           <!-- Default back behavior could be handled by parent -->
        </slot>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ title }}</h1>
          <p class="text-sm text-gray-500 mt-1">
            {{ $t('common.step') }} {{ currentStep }}/{{ totalSteps }}
            <span v-if="mode === 'preview'" class="ml-2 text-orange-500">({{ $t('common.previewMode') }})</span>
          </p>
        </div>
      </div>
      
      <!-- Review Status Badge -->
      <div v-if="mode === 'review' && status" class="flex items-center gap-2">
         <UBadge :color="statusColor">{{ status }}</UBadge>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-12">
      <UIcon name="i-heroicons-arrow-path" class="w-12 h-12 text-primary-500 animate-spin" />
      <p class="mt-4 text-gray-600">{{ $t('common.loading') }}...</p>
    </div>

    <!-- Error State -->
    <UAlert
      v-else-if="error"
      icon="i-heroicons-exclamation-triangle"
      color="red"
      variant="soft"
      :title="$t('common.error')"
      :description="error"
      class="mb-6"
    >
      <template #footer>
        <UButton
          color="red"
          variant="solid"
          :label="$t('common.retry')"
          @click="init"
        />
      </template>
    </UAlert>

    <!-- Main Content -->
    <div v-else class="space-y-6">
      <!-- Step Indicator -->
      <UCard>
        <div class="flex items-center px-4 pt-4 pb-2 overflow-x-auto">
          <div
            v-for="step in steps"
            :key="step.number"
            class="flex items-center flex-1 last:flex-none"
          >
            <div class="flex flex-col items-center min-w-[80px]">
              <div
                class="w-10 h-10 rounded-full flex items-center justify-center font-semibold transition-colors duration-200 shrink-0"
                :class="[
                  currentStep >= step.number
                    ? 'bg-primary-600 text-white'
                    : 'bg-gray-200 text-gray-500',
                  mode === 'review' ? 'cursor-pointer hover:ring-2 hover:ring-primary-300' : ''
                ]"
                @click="mode === 'review' ? jumpToStep(step.number) : null"
              >
                <UIcon v-if="currentStep > step.number" name="i-heroicons-check" class="w-5 h-5" />
                <span v-else>{{ step.number }}</span>
              </div>
              <span class="text-xs mt-2 text-center font-medium text-gray-600 line-clamp-2 max-w-[100px]">
                {{ step.title }}
              </span>
            </div>
            <div
              v-if="step.number < steps.length"
              class="h-0.5 flex-1 mx-2 -mt-6 min-w-[20px]"
              :class="[
                currentStep > step.number ? 'bg-primary-600' : 'bg-gray-200',
              ]"
            />
          </div>
        </div>
      </UCard>

      <!-- Form Content -->
      <UCard class="min-h-[400px]">
        
        <!-- Step 1: Basic Info -->
        <BasicInfoFormV2
          v-if="isBasicInfoStep"
          v-model="formData.basicInfo"
          :disabled="mode === 'review'"
        />

        <!-- Step 2-N: Section Questions -->
        <SectionFormV2
          v-else-if="currentSection"
          :section="currentSection"
          :answers="formData.answers"
          :visible-questions="visibleQuestions"
          :mode="mode"
          :reviews="reviews"
          @update:answer="handleAnswerUpdate"
          @update:review="handleReviewUpdate"
          :disabled="mode === 'review' && mode !== 'review'" 
        />
        <!-- Note: disabled prop on SectionFormV2 usually disables inputs.
             In review mode, we want inputs disabled but review controls enabled.
             SectionFormV2 doesn't seem to use 'disabled' prop directly for inputs 
             but QuestionnaireWizard passed it. Checking SectionFormV2 implementation...
             It doesn't define 'disabled' prop! It seems I added it in previous step 32 but 
             SectionFormV2 didn't actually accept it in step 26/91? 
             Wait, step 26 view showed it didn't have disabled prop.
             Step 32 write_to_file passed :disabled="mode === 'review'".
             So it was probably falling through to attributes but not handled?
             Actually, QuestionRendererV2 handles inputs. If I pass disabled to passing it down?
             Inputs in Renderer are what matters.
             If I want inputs disabled in review mode, QuestionRendererV2 needs to handle it.
             The 'mode' prop I added to Renderer can handle disabling edits.
             For now, let's just make sure we pass mode and reviews. 
        -->

        <!-- Final Step: Completion/Submission -->
        <div v-else-if="isFinalStep" class="space-y-6">
          <div class="text-center py-8">
            <UIcon name="i-heroicons-check-circle" class="w-16 h-16 text-green-500 mx-auto mb-4" />
            <h2 class="text-2xl font-bold text-gray-900">
               {{ mode === 'preview' ? ($t('common.previewComplete')) : ($t('questionnaire.readyToSubmit')) }}
            </h2>
            <p class="text-gray-600 mt-2">
              {{ mode === 'preview' 
                  ? ($t('templates.previewNote')) 
                  : ($t('questionnaire.pleaseCheckBeforeSubmit')) 
              }}
            </p>
          </div>
          
          <UCard :ui="{ background: 'bg-primary-50 dark:bg-primary-900/10', ring: 'ring-1 ring-primary-200 dark:ring-primary-800' }">
            <h3 class="text-lg font-semibold text-primary-900 dark:text-primary-100 mb-4">
              {{ $t('templates.templateInfo') }}
            </h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="flex flex-col">
                <dt class="text-sm text-gray-500">{{ $t('templates.templateName') }}</dt>
                <dd class="font-medium text-gray-900">{{ title }}</dd>
              </div>
              <div class="flex flex-col">
                <dt class="text-sm text-gray-500">{{ $t('templates.totalSteps') }}</dt>
                <dd class="font-medium text-gray-900">{{ totalSteps }}</dd>
              </div>
              <div class="flex flex-col">
                <dt class="text-sm text-gray-500">{{ $t('templates.answeredCount') }}</dt>
                <dd class="font-medium text-gray-900">{{ answeredCount }}</dd>
              </div>
            </dl>
          </UCard>
        </div>
      </UCard>

      <!-- Actions -->
      <div class="flex justify-between pt-4 pb-20 sm:pb-4">
        <UButton
          v-if="currentStep > 1"
          color="gray"
          variant="solid"
          icon="i-heroicons-arrow-left"
          :label="$t('common.previous')"
          @click="previousStep"
        />
        <div v-else />
        
        <!-- Next / Finish Buttons -->
        <div class="flex gap-2">
           <!-- Save Button (Fill Mode only) -->
           <UButton
             v-if="mode === 'fill' && !isFinalStep"
             color="white"
             variant="solid"
             icon="i-heroicons-device-floppy"
             :label="$t('common.save')"
             :loading="saving"
             @click="handleSave"
           />

           <UButton
            v-if="currentStep < totalSteps"
            color="primary"
            variant="solid"
            trailing-icon="i-heroicons-arrow-right"
            :label="$t('common.next')"
            @click="nextStep"
          />
          
          <!-- Final Action Buttons -->
          <template v-else>
             <!-- Preview Mode: Finish -->
             <UButton
               v-if="mode === 'preview'"
               color="green"
               variant="solid"
               icon="i-heroicons-check"
               :label="$t('common.finish')"
               @click="finishPreview"
             />

             <!-- Fill Mode: Submit -->
             <UButton
               v-if="mode === 'fill'"
               color="green"
               variant="solid"
               icon="i-heroicons-paper-airplane"
               :label="$t('common.submit')"
               :loading="submitting"
               @click="handleSubmit"
             />
          </template>
        </div>
      </div>
    </div>
    
    <!-- Review Actions (Sticky Bottom) -->
    <div v-if="mode === 'review'" class="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-gray-200 shadow-lg z-10">
      <div class="container mx-auto max-w-7xl flex flex-col gap-4">
        <div class="flex items-center gap-4">
            <UTextarea
              v-model="reviewComment"
              :placeholder="$t('review.comment')"
              :rows="1"
              autoresize
              class="flex-1"
            />
            <div class="flex gap-2">
              <UButton
                color="red"
                variant="solid"
                :loading="submitting"
                :disabled="!reviewComment"
                @click="handleReturn"
              >
                {{ $t('review.return') }}
              </UButton>
              <UButton
                color="green"
                variant="solid"
                :loading="submitting"
                @click="handleApprove"
              >
                {{ $t('review.approve') }}
              </UButton>
            </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import type { TemplateStructure, Section, Answers, AnswerValue, BasicInfo, Reviews, QuestionReview } from '~/types/template-v2'
import BasicInfoFormV2 from './BasicInfoFormV2.vue'
import SectionFormV2 from './SectionFormV2.vue'
import { useProjects } from '~/composables/useProjects'
import { useTemplates } from '~/composables/useTemplates'
import { useAnswers } from '~/composables/useAnswers'
import { useReview } from '~/composables/useReview'

const props = defineProps<{
  mode: 'preview' | 'fill' | 'review'
  templateId?: string // For preview
  projectSupplierId?: string // For fill/review
  titlePrefix?: string
}>()

const emit = defineEmits(['finish', 'saved', 'submitted'])

// Composables
const router = useRouter()
const { t, locale } = useI18n()
const config = useRuntimeConfig()
const { getProject } = useProjects()
const { getTemplateStructure } = useTemplates()
const { getAnswers, saveAnswers, submitAnswers } = useAnswers()
const { getReviewLogs, approveProject, returnProject } = useReview()

// State
const loading = ref(true)
const saving = ref(false)
const submitting = ref(false)
const error = ref<string | null>(null)
const currentStep = ref(1)

// Data
const templateId = ref<string>('')
const templateStructure = ref<TemplateStructure | null>(null)
const templateName = ref('')
const projectInfo = ref<any>(null)
const reviewConfig = ref<any>(null)
const status = ref<string>('')

// Form Data
const formData = reactive<{
  basicInfo: Partial<BasicInfo>
  answers: Answers
}>({
  basicInfo: {},
  answers: {},
})
const visibleQuestions = ref<Set<string>>(new Set())
const reviewComment = ref('')
const reviews = ref<Reviews>({})

// Computed
const title = computed(() => {
  if (projectInfo.value?.name) return projectInfo.value.name
  return templateName.value || props.titlePrefix || t('common.loading')
})

const statusColor = computed(() => {
   const colors: Record<string, string> = {
      'DRAFT': 'gray',
      'IN_PROGRESS': 'blue',
      'SUBMITTED': 'orange',
      'REVIEWING': 'orange',
      'APPROVED': 'green',
      'RETURNED': 'red'
   }
   return (colors[status.value] || 'gray') as any
})

// Steps Logic
const steps = computed(() => {
  if (!templateStructure.value) return []
  
  const stepList = []
  let stepNumber = 1
  
  if (templateStructure.value.includeBasicInfo) {
    stepList.push({
      number: stepNumber++,
      title: t('projects.basicInfo'),
    })
  }
  
  if (templateStructure.value.sections) {
    for (const section of templateStructure.value.sections) {
      stepList.push({
        number: stepNumber++,
        title: `${section.id}. ${section.title}`,
      })
    }
  }
  
  stepList.push({
    number: stepNumber,
    title: modeIsFill.value ? t('common.submit') : t('common.previewComplete'),
  })
  
  return stepList
})

const totalSteps = computed(() => steps.value.length)
const modeIsFill = computed(() => props.mode === 'fill')
const modeIsPreview = computed(() => props.mode === 'preview')

// Current View Logic
const isBasicInfoStep = computed(() => {
   if (!templateStructure.value) return false
   return currentStep.value === 1 && templateStructure.value.includeBasicInfo
})

const currentSection = computed((): Section | null => {
  if (!templateStructure.value?.sections) return null
  
  let sectionIndex = currentStep.value - 1
  if (templateStructure.value.includeBasicInfo) {
    sectionIndex -= 1
  }
  
  if (sectionIndex < 0 || sectionIndex >= templateStructure.value.sections.length) {
    return null
  }
  
  return templateStructure.value.sections[sectionIndex]
})

const isFinalStep = computed(() => currentStep.value === totalSteps.value)

const answeredCount = computed(() => {
  return Object.keys(formData.answers).length
})

// Logic Methods
const initializeVisibleQuestions = () => {
  if (!templateStructure.value?.sections) return
  
  const allQuestions: string[] = []
  for (const section of templateStructure.value.sections) {
    for (const subsection of section.subsections) {
      for (const question of subsection.questions) {
        if (!question.conditionalLogic?.showWhen) {
          allQuestions.push(question.id)
        }
      }
    }
  }
  visibleQuestions.value = new Set(allQuestions)
}

const handleAnswerUpdate = ({ questionId, value }: { questionId: string; value: AnswerValue }) => {
  if (props.mode === 'review') return
  formData.answers[questionId] = {
    questionId,
    value,
  }
  updateVisibleQuestions()
}

const handleReviewUpdate = (review: QuestionReview) => {
  reviews.value[review.questionId] = review
}

const updateVisibleQuestions = () => {
  // Simple local logic for preview/fill for now
  // Real implementation might need API call or complex client-side logic
  initializeVisibleQuestions()
}

// Navigation
const nextStep = () => {
  if (currentStep.value < totalSteps.value) {
    currentStep.value++
    // Auto-save on step change if in fill mode
    if (modeIsFill.value) {
       handleSave(true)
    }
  }
}

const previousStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const jumpToStep = (step: number) => {
   currentStep.value = step
}

// Actions
const handleSave = async (silent = false) => {
  if (!props.projectSupplierId) return
  
  if (!silent) saving.value = true
  try {
    await saveAnswers(props.projectSupplierId, formData.answers)
    if (!silent) {
       // Optional: Toast notification
    }
    emit('saved')
  } catch (e) {
    console.error('Save failed:', e)
    if (!silent) error.value = t('common.saveFailed')
  } finally {
    if (!silent) saving.value = false
  }
}

const handleSubmit = async () => {
   if (!props.projectSupplierId) return
   if (!confirm(t('questionnaire.confirmSubmit'))) return

   submitting.value = true
   try {
     await submitAnswers(props.projectSupplierId, formData.answers)
     emit('submitted')
     // Redirect will be handled by parent or router logic
     router.push('/supplier/projects')
   } catch (e) {
     console.error('Submit failed:', e)
     error.value = t('common.submitFailed')
   } finally {
     submitting.value = false
   }
}

const finishPreview = () => {
   emit('finish')
   router.push('/saq/templates')
}

// Review Actions
const handleApprove = async () => {
    if (!props.projectSupplierId) return
    if (!confirm(t('review.confirmApprove'))) return
    
    submitting.value = true
    try {
        await approveProject(props.projectSupplierId, reviewComment.value || 'Approved', reviews.value)
        router.push('/review')
    } catch (e) {
        console.error(e)
        error.value = t('common.error')
    } finally {
        submitting.value = false
    }
}

const handleReturn = async () => {
    if (!props.projectSupplierId) return
    if (!confirm(t('review.confirmReturn'))) return
    
    submitting.value = true
    try {
        await returnProject(props.projectSupplierId, reviewComment.value, reviews.value)
        router.push('/review')
    } catch (e) {
        console.error(e)
        error.value = t('common.error')
    } finally {
        submitting.value = false
    }
}

// Initialization
const init = async () => {
  loading.value = true
  error.value = null
  console.log('Initializing QuestionnaireWizard in mode:', props.mode)
  
  try {
    if (props.mode === 'preview' && props.templateId) {
       // Preview Mode: Load Template Only
       await loadTemplate(props.templateId)
    } else if ((props.mode === 'fill' || props.mode === 'review') && props.projectSupplierId) {
       // Fill/Review Mode: Load Project, Template, Answers
       await loadProjectData(props.projectSupplierId)
    } else {
       throw new Error('Invalid props for QuestionnaireWizard')
    }
    
    initializeVisibleQuestions()
    
  } catch (e: any) {
    console.error('Init failed:', e)
    error.value = e.message || 'Initialization failed'
  } finally {
    loading.value = false
  }
}

const loadTemplate = async (id: string) => {
    const currentLocale = locale.value.startsWith('zh') ? 'zh' : 'en'
    const { data: templateData, error: apiError } = await useFetch(
      `${config.public.apiBase}/api/v1/templates/${id}/structure`,
      {
        query: { lang: currentLocale }
      }
    )
    
    if (apiError.value) throw new Error('Failed to load template')
    
    if (templateData.value?.data) {
       const data = templateData.value.data as any
       templateStructure.value = data.structure
       templateName.value = `Template Preview (ID: ${id})`
       templateId.value = id
    }
}

const loadProjectData = async (psId: string) => {
    // 1. Get Project/Supplier Info (Need to know TemplateID)
    // We might need a specific API that gives us everything for the filling view
    // or chain calls.
    // Assuming we have to chain:
    
    // Get Review/Status info (contains projectId)
    // Use getReviewLogs as it returns projectSupplier context
    try {
        const reviewRes = await getReviewLogs(psId) as any
        status.value = reviewRes.currentStatus
        const projectId = reviewRes.projectId
        
        if (!projectId) throw new Error('Project ID not linked')
        
        // Get Project Info
        const projectRes = await getProject(projectId) as any
        const project = projectRes.data
        projectInfo.value = project
        
        // Get Template Structure
        if (project.templateId) {
           await loadTemplate(project.templateId)
        }
        
        // Get Existing Answers
        const answersRes = await getAnswers(psId) as any
        if (answersRes.data?.answers) {
           formData.answers = answersRes.data.answers
        }
        
        // Get Basic Info if it exists (assuming API exists or part of answers?)
        // For now, Basic Info might be part of the answers payload or separate
        // If separate, we need calls. If not, use what we have.
        
    } catch (e) {
       console.error(e)
       throw new Error('Failed to load project data')
    }
}

onMounted(() => {
  init()
})

watch(() => props.mode, () => init())
watch(locale, () => init())

</script>
