<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <!-- Back Button -->
        <UButton
          v-if="showBackButton"
          icon="i-heroicons-arrow-left"
          color="gray"
          variant="ghost"
          @click="handleBack"
        />
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ title }}</h1>
          <p class="text-sm text-gray-500 mt-1">
            {{ $t('common.step') }} {{ currentStep }}/{{ totalSteps }}
            <span v-if="mode === 'preview'" class="ml-2 text-orange-500">({{ $t('common.previewMode') }})</span>
          </p>
        </div>
      </div>
      
      <div class="flex items-center gap-2">
        <!-- Auto Fill Menu (Fill mode only) -->
        <UDropdown
          v-if="mode === 'fill' && showAutoFill"
          :items="autoFillOptions"
          :popper="{ placement: 'bottom-end' }"
        >
          <UButton
            color="orange"
            variant="soft"
            icon="i-heroicons-sparkles"
            trailing-icon="i-heroicons-chevron-down-20-solid"
            label="自動填寫"
          />
        </UDropdown>

        <!-- Review Status Badge -->
        <div v-if="mode === 'review' && status">
           <UBadge :color="statusColor">{{ status }}</UBadge>
        </div>
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
        
        <BasicInfoFormV2
          v-if="isBasicInfoStep"
          v-model="formData.basicInfo"
          :disabled="mode === 'review'"
        />

        <SectionFormV2
          v-else-if="currentSection"
          :section="currentSection"
          :answers="formData.answers"
          :visible-questions="visibleQuestions"
          :mode="mode"
          :reviews="reviews"
          @update:answer="handleAnswerUpdate"
          @update:review="handleReviewUpdate"
          :disabled="mode === 'review'" 
        />

        <!-- Final Step: Review Summary or Submission -->
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

        <div v-else class="flex items-center justify-center py-20 text-center">
          <div>
            <UIcon name="i-heroicons-exclamation-circle" class="w-12 h-12 text-gray-300 mx-auto mb-4" />
            <p class="text-gray-500 italic">No section data found (Step: {{ currentStep }})</p>
          </div>
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
             icon="i-heroicons-check-circle"
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

    <!-- Floating Navigation for Review Mode -->
    <div v-if="mode === 'review'" class="fixed bottom-6 right-6 flex flex-col gap-2 z-20">
      <UButton
        v-if="currentStep > 1"
        color="white"
        variant="solid"
        icon="i-heroicons-chevron-up"
        size="lg"
        class="shadow-lg"
        @click="previousStep"
      />
      <UButton
        v-if="currentStep < totalSteps"
        color="primary"
        variant="solid"
        icon="i-heroicons-chevron-down"
        size="lg"
        class="shadow-lg"
        @click="nextStep"
      />
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
  showBackButton?: boolean
  showAutoFill?: boolean
}>()

const emit = defineEmits(['finish', 'saved', 'submitted', 'back'])

const handleBack = () => {
  emit('back')
  router.back()
}

// Composables
const router = useRouter()
const { t, locale } = useI18n()
const config = useRuntimeConfig()
const { getProject } = useProjects()
const { getTemplateStructure } = useTemplates()
const { getAnswers, saveAnswers, submitAnswers, getBasicInfo, getVisibleQuestions } = useAnswers()
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

// Auto-fill options for dropdown
const autoFillOptions = computed(() => [
  [{
    label: '全部選「是」',
    icon: 'i-heroicons-check-circle',
    click: () => autoFillAnswers('allYes')
  }],
  [{
    label: '全部選「否」',
    icon: 'i-heroicons-x-circle',
    click: () => autoFillAnswers('allNo')
  }],
  [{
    label: '隨機填寫',
    icon: 'i-heroicons-sparkles',
    click: () => autoFillAnswers('random')
  }]
])

// Auto-fill all answers
const autoFillAnswers = (strategy: 'allYes' | 'allNo' | 'random') => {
  if (!templateStructure.value?.sections) return
  
  const fillQuestion = (question: any, parentAnswer?: boolean) => {
    let answer: AnswerValue = null
    
    switch (question.type) {
      case 'BOOLEAN':
        if (strategy === 'allYes') answer = true
        else if (strategy === 'allNo') answer = false
        else answer = Math.random() > 0.5
        break
        
      case 'TEXT':
        if (strategy === 'allNo') {
          answer = ''
        } else if (strategy === 'allYes' || parentAnswer === true || (strategy === 'random' && Math.random() > 0.3)) {
          answer = generateRandomText(question)
        }
        break
        
      case 'NUMBER':
        if (strategy === 'allNo') {
          answer = 0
        } else {
          const min = question.config?.minValue ?? 0
          const max = question.config?.maxValue ?? 1000
          answer = Math.floor(Math.random() * (max - min + 1)) + min
        }
        break
        
      case 'RADIO':
      case 'SELECT':
        if (question.config?.options?.length) {
          if (strategy === 'allYes') {
            answer = question.config.options[0].value
          } else if (strategy === 'allNo') {
            answer = question.config.options[question.config.options.length - 1].value
          } else {
            const idx = Math.floor(Math.random() * question.config.options.length)
            answer = question.config.options[idx].value
          }
        }
        break
        
      case 'CHECKBOX':
        if (question.config?.options?.length) {
          if (strategy === 'allYes') {
            answer = question.config.options.map((o: any) => o.value)
          } else if (strategy === 'allNo') {
            answer = []
          } else {
            answer = question.config.options
              .filter(() => Math.random() > 0.5)
              .map((o: any) => o.value)
          }
        }
        break
        
      case 'DATE':
        if (strategy !== 'allNo') {
          const randomDays = Math.floor(Math.random() * 365)
          const date = new Date()
          date.setDate(date.getDate() - randomDays)
          answer = date.toISOString().split('T')[0]
        }
        break
        
      case 'TABLE':
        if (question.tableConfig?.columns) {
          const rows = []
          const numRows = question.tableConfig?.prefilledRows?.length || 
                         Math.floor(Math.random() * 3) + 1
          for (let i = 0; i < numRows; i++) {
            const row: Record<string, any> = {}
            for (const col of question.tableConfig.columns) {
              if (question.tableConfig.prefilledRows?.[i] && col === question.tableConfig.columns[0]) {
                row[col.id] = question.tableConfig.prefilledRows[i]
              } else if (col.type === 'number') {
                row[col.id] = Math.floor(Math.random() * 100)
              } else if (col.type === 'date') {
                const d = new Date()
                d.setDate(d.getDate() - Math.floor(Math.random() * 365))
                row[col.id] = d.toISOString().split('T')[0]
              } else {
                row[col.id] = `Sample ${i + 1}`
              }
            }
            rows.push(row)
          }
          answer = rows
        }
        break
    }
    
    if (answer !== null && answer !== undefined) {
      formData.answers[question.id] = {
        questionId: question.id,
        value: answer,
      }
    }
    
    // Handle follow-up questions
    if (question.conditionalLogic?.followUpQuestions && (answer === true || strategy === 'allYes')) {
      for (const followUp of question.conditionalLogic.followUpQuestions) {
        if (followUp.condition?.operator === 'equals' && followUp.condition?.value === answer) {
          for (const fq of followUp.questions) {
            fillQuestion(fq, true)
          }
        }
      }
    }
  }
  
  // Fill basic info first (if template includes it)
  if (templateStructure.value.includeBasicInfo) {
    fillBasicInfo()
  }
  
  // Fill all questions
  for (const section of templateStructure.value.sections) {
    for (const subsection of section.subsections) {
      for (const question of subsection.questions) {
        fillQuestion(question)
      }
    }
  }
  
  // Trigger reactivity
  formData.basicInfo = { ...formData.basicInfo }
  formData.answers = { ...formData.answers }
  updateVisibleQuestions()
}

// Auto-fill basic info
const fillBasicInfo = () => {
  const companyNames = ['台灣電子科技股份有限公司', '環球製造有限公司', '先進材料工業股份有限公司']
  const locations = ['台北市', '新北市', '桃園市', '新竹市', '台中市', '高雄市']
  const departments = ['品管部', '生產部', '人資部', '財務部', '研發部']
  const titles = ['經理', '主任', '專員', '副理', '課長']
  const certifications = ['ISO 9001', 'ISO 14001', 'ISO 45001', 'IATF 16949', 'SA 8000']
  
  formData.basicInfo = {
    companyName: companyNames[Math.floor(Math.random() * companyNames.length)],
    companyAddress: `${locations[Math.floor(Math.random() * locations.length)]}中正路${Math.floor(Math.random() * 500) + 1}號`,
    employees: {
      total: Math.floor(Math.random() * 5000) + 100,
      male: Math.floor(Math.random() * 2500) + 50,
      female: Math.floor(Math.random() * 2500) + 50,
      foreign: Math.floor(Math.random() * 500)
    },
    facilities: [
      {
        location: locations[Math.floor(Math.random() * locations.length)],
        address: `工業區第${Math.floor(Math.random() * 50) + 1}號`,
        area: `${Math.floor(Math.random() * 10000) + 1000} 平方米`,
        type: '製造廠'
      }
    ],
    certifications: certifications.slice(0, Math.floor(Math.random() * 3) + 1),
    rbaOnlineMember: Math.random() > 0.5,
    contacts: [
      {
        name: `王${['大明', '小華', '志強', '美玲', '建宏'][Math.floor(Math.random() * 5)]}`,
        title: titles[Math.floor(Math.random() * titles.length)],
        department: departments[Math.floor(Math.random() * departments.length)],
        email: `contact${Math.floor(Math.random() * 1000)}@example.com`,
        phone: `02-${Math.floor(Math.random() * 9000) + 1000}-${Math.floor(Math.random() * 9000) + 1000}`
      }
    ]
  }
}

const generateRandomText = (question: any): string => {
  const sampleTexts = [
    '符合公司政策規定',
    '已依照標準作業程序執行',
    '定期進行內部稽核確認',
    '每季檢討並更新相關文件',
    '已取得相關認證並持續維護'
  ]
  return sampleTexts[Math.floor(Math.random() * sampleTexts.length)]
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
     // Let parent component handle the redirect
   } catch (e: any) {
     console.error('Submit failed:', e)
     error.value = e?.message || t('questionnaire.submitFailed')
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
    
    if (visibleQuestions.value.size === 0) {
      initializeVisibleQuestions()
    }
    
    console.log('Init final state:', {
      currentStep: currentStep.value,
      totalSteps: totalSteps.value,
      visibleQuestionsCount: visibleQuestions.value.size,
      sectionsCount: templateStructure.value?.sections?.length
    })
    
  } catch (e: any) {
    console.error('Init failed:', e)
    error.value = e.message || 'Initialization failed'
  } finally {
    loading.value = false
  }
}

const loadTemplate = async (id: string) => {
    const currentLocale = locale.value.startsWith('zh') ? 'zh' : 'en'
    
    try {
      const response = await $fetch<any>(
        `${config.public.apiBase}/api/v1/templates/${id}/structure`,
        {
          query: { lang: currentLocale }
        }
      )
      
      if (response?.data) {
         templateStructure.value = response.data.structure
         templateName.value = `Template Preview (ID: ${id})`
         templateId.value = id
      }
    } catch (e) {
      console.error('Failed to load template:', e)
      throw new Error('Failed to load template')
    }
}

const loadProjectData = async (psId: string) => {
    try {
        console.log('Loading project data for psId:', psId)
        
        // First: Get project info from review logs (need projectId)
        const reviewRes = await getReviewLogs(psId) as any
        status.value = reviewRes.currentStatus
        const projectId = reviewRes.projectId
        
        console.log('Review response:', reviewRes)
        
        if (!projectId) throw new Error('Project ID not linked')
        
        // Parallel: Load project info, answers, basic info, and visible questions
        const [projectRes, answersRes, basicInfoRes, visibleRes] = await Promise.all([
          getProject(projectId),
          getAnswers(psId),
          getBasicInfo(psId),
          getVisibleQuestions(psId)
        ]) as [any, any, any, any]
        
        console.log('Project response:', projectRes)
        console.log('Answers response:', answersRes)
        console.log('Basic Info response:', basicInfoRes)
        console.log('Visible Questions response:', visibleRes)
        
        const project = projectRes.data
        projectInfo.value = project
        
        // Load basic info
        if (basicInfoRes.data?.basicInfo) {
           formData.basicInfo = basicInfoRes.data.basicInfo
        }
        
        // Load answers
        if (answersRes.data?.answers) {
           formData.answers = answersRes.data.answers
           console.log('Loaded answers:', formData.answers)
        } else {
           console.warn('No answers found in response')
        }
        
        // Load visible questions
        if (visibleRes.data?.visibleQuestions) {
          visibleQuestions.value = new Set(visibleRes.data.visibleQuestions)
        }
        
        // Then: Load template structure (needs templateId from project)
        console.log('Project templateId:', project.templateId)
        if (project.templateId) {
           await loadTemplate(project.templateId)
           console.log('Template loaded. Structure:', templateStructure.value)
        } else {
           console.error('No templateId found in project!')
        }
        
    } catch (e) {
       console.error('loadProjectData error:', e)
       throw new Error('Failed to load project data')
    }
}

// Store target question ID from hash
const targetQuestionId = ref<string | null>(null)

onMounted(() => {
  init()
  
  // Read step and question from URL hash on mount
  if (typeof window !== 'undefined') {
    const hash = window.location.hash.slice(1) // Remove #
    const params = new URLSearchParams(hash)
    
    const stepParam = params.get('step')
    if (stepParam) {
      const stepFromHash = parseInt(stepParam, 10)
      if (stepFromHash >= 1) {
        currentStep.value = stepFromHash
      }
    }
    
    const questionParam = params.get('q')
    if (questionParam) {
      targetQuestionId.value = questionParam
    }
  }
})

// Scroll to target question after loading completes
watch(loading, (isLoading) => {
  if (!isLoading && targetQuestionId.value && typeof window !== 'undefined') {
    // Wait for DOM to update
    setTimeout(() => {
      const el = document.getElementById(`question-${targetQuestionId.value}`)
      if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' })
        // Highlight the question briefly
        el.classList.add('ring-2', 'ring-primary-500', 'ring-offset-2')
        setTimeout(() => {
          el.classList.remove('ring-2', 'ring-primary-500', 'ring-offset-2')
        }, 2000)
      }
      targetQuestionId.value = null
    }, 300)
  }
})

// Sync step to URL hash (preserve question if exists)
watch(currentStep, (newStep) => {
  if (typeof window !== 'undefined') {
    const currentHash = window.location.hash.slice(1)
    const params = new URLSearchParams(currentHash)
    params.set('step', String(newStep))
    // Only keep step, remove question on navigation
    params.delete('q')
    window.location.hash = params.toString()
  }
})

watch(() => props.mode, () => init())
watch(locale, () => init())

</script>
