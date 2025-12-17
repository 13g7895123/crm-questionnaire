<template>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="w-full">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
          <UButton
            icon="i-heroicons-arrow-left"
            color="gray"
            variant="ghost"
            to="/saq/templates"
          />
          <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ templateName }}</h1>
            <p class="text-sm text-gray-500 mt-1">
              {{ $t('common.step') }} {{ currentStep }}/{{ totalSteps }}
            </p>
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
            :label="$t('common.back')"
            to="/saq/templates"
          />
        </template>
      </UAlert>

      <!-- Main Content -->
      <div v-else class="space-y-6">
        <!-- Step Indicator -->
        <UCard>
          <div class="flex items-center justify-between px-4 py-2">
            <div
              v-for="step in steps"
              :key="step.number"
              class="flex items-center flex-1 last:flex-none"
            >
              <div class="flex flex-col items-center relative">
                <div
                  :class="[
                    'w-8 h-8 rounded-full flex items-center justify-center font-semibold transition-colors duration-200',
                    currentStep >= step.number
                      ? 'bg-primary-600 text-white'
                      : 'bg-gray-200 text-gray-500',
                  ]"
                >
                  <UIcon v-if="currentStep > step.number" name="i-heroicons-check" class="w-5 h-5" />
                  <span v-else>{{ step.number }}</span>
                </div>
                <span class="text-xs mt-2 absolute -bottom-6 w-32 text-center font-medium text-gray-600">
                  {{ step.title }}
                </span>
              </div>
              <div
                v-if="step.number < steps.length"
                :class="[
                  'h-0.5 flex-1 mx-4 mt-[-1.5rem]',
                  currentStep > step.number ? 'bg-primary-600' : 'bg-gray-200',
                ]"
              />
            </div>
          </div>
          <div class="h-6"></div> <!-- Spacer for absolute positioned labels -->
        </UCard>

        <!-- Form Content -->
        <UCard class="min-h-[400px]">
          <!-- Step 1: Basic Info -->
          <BasicInfoFormV2
            v-if="currentStep === 1 && templateStructure?.includeBasicInfo"
            v-model="formData.basicInfo"
          />

          <!-- Step 2-N: Section Questions -->
          <SectionFormV2
            v-else-if="currentSection"
            :section="currentSection"
            :answers="formData.answers"
            :visible-questions="visibleQuestions"
            @update:answer="handleAnswerUpdate"
          />

          <!-- Final Step: Preview Complete -->
          <div v-else-if="currentStep === totalSteps" class="space-y-6">
            <div class="text-center py-8">
              <UIcon name="i-heroicons-check-circle" class="w-16 h-16 text-green-500 mx-auto mb-4" />
              <h2 class="text-2xl font-bold text-gray-900">{{ $t('common.previewComplete') || '預覽完成' }}</h2>
              <p class="text-gray-600 mt-2">
                {{ $t('templates.previewNote') || '這是範本預覽模式，實際問卷填寫時將會儲存答案並計算分數。' }}
              </p>
            </div>
            
            <UCard :ui="{ background: 'bg-primary-50 dark:bg-primary-900/10', ring: 'ring-1 ring-primary-200 dark:ring-primary-800' }">
              <h3 class="text-lg font-semibold text-primary-900 dark:text-primary-100 mb-4">
                {{ $t('templates.templateInfo') || '範本資訊' }}
              </h3>
              <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex flex-col">
                  <dt class="text-sm text-gray-500">{{ $t('templates.templateName') }}</dt>
                  <dd class="font-medium text-gray-900">{{ templateName }}</dd>
                </div>
                <div class="flex flex-col">
                  <dt class="text-sm text-gray-500">{{ $t('templates.sectionCount') || '區段數量' }}</dt>
                  <dd class="font-medium text-gray-900">{{ templateStructure?.sections?.length || 0 }}</dd>
                </div>
                <div class="flex flex-col">
                  <dt class="text-sm text-gray-500">{{ $t('templates.totalSteps') || '總步驟數' }}</dt>
                  <dd class="font-medium text-gray-900">{{ totalSteps }}</dd>
                </div>
                <div class="flex flex-col">
                  <dt class="text-sm text-gray-500">{{ $t('templates.answeredCount') || '已填答數' }}</dt>
                  <dd class="font-medium text-gray-900">{{ answeredCount }}</dd>
                </div>
              </dl>
            </UCard>
          </div>
        </UCard>

        <!-- Actions -->
        <div class="flex justify-between pt-4">
          <UButton
            v-if="currentStep > 1"
            color="gray"
            variant="solid"
            icon="i-heroicons-arrow-left"
            :label="$t('common.previous')"
            @click="previousStep"
          />
          <div v-else />
          
          <UButton
            v-if="currentStep < totalSteps"
            color="primary"
            variant="solid"
            trailing-icon="i-heroicons-arrow-right"
            :label="$t('common.next')"
            @click="nextStep"
          />
          <UButton
            v-else
            color="green"
            variant="solid"
            icon="i-heroicons-check"
            :label="$t('common.finish')"
            @click="finishPreview"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { TemplateStructure, Section, Answers, AnswerValue, BasicInfo } from '~/types/template-v2'
import BasicInfoFormV2 from '~/components/questionnaire/BasicInfoFormV2.vue'
import SectionFormV2 from '~/components/questionnaire/SectionFormV2.vue'

definePageMeta({ middleware: 'auth' })

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()
const { t, locale } = useI18n()
const { setBreadcrumbs } = useBreadcrumbs()

const templateId = computed(() => route.params.id as string)
const templateName = ref('載入中...')
const currentStep = ref(1)
const loading = ref(true)
const error = ref<string | null>(null)

const templateStructure = ref<TemplateStructure | null>(null)
const formData = reactive<{
  basicInfo: Partial<BasicInfo>
  answers: Answers
}>({
  basicInfo: {},
  answers: {},
})
const visibleQuestions = ref<Set<string>>(new Set())

// 載入範本結構
const loadTemplateStructure = async () => {
  try {
    loading.value = true
    
    // Get current locale for i18n
    const currentLocale = locale.value.startsWith('zh') ? 'zh' : 'en'
    
    const { data: templateData, error: apiError } = await useFetch(
      `${config.public.apiBase}/api/v1/templates/${templateId.value}/structure`,
      {
        headers: {
          'Content-Type': 'application/json',
        },
        query: {
          lang: currentLocale,
        },
      }
    )

    if (apiError.value) {
      error.value = '無法載入範本資料，請確認範本 ID 是否正確'
      return
    }

    if (templateData.value?.data) {
      const data = templateData.value.data as any
      templateStructure.value = data.structure
      templateName.value = `範本預覽 (ID: ${templateId.value})`
      
      // 初始化所有問題為可見
      initializeVisibleQuestions()
    }
  } catch (e) {
    console.error('載入範本失敗:', e)
    error.value = '載入範本時發生錯誤'
  } finally {
    loading.value = false
  }
}

// 初始化可見問題清單
const initializeVisibleQuestions = () => {
  if (!templateStructure.value?.sections) return
  
  const allQuestions: string[] = []
  for (const section of templateStructure.value.sections) {
    for (const subsection of section.subsections) {
      for (const question of subsection.questions) {
        // 沒有 showWhen 條件的問題預設可見
        if (!question.conditionalLogic?.showWhen) {
          allQuestions.push(question.id)
        }
      }
    }
  }
  visibleQuestions.value = new Set(allQuestions)
}

// 計算步驟
const steps = computed(() => {
  if (!templateStructure.value) return []
  
  const stepList = []
  let stepNumber = 1
  
  if (templateStructure.value.includeBasicInfo) {
    stepList.push({
      number: stepNumber++,
      title: '公司基本資料',
    })
  }
  
  if (templateStructure.value.sections) {
    for (const section of templateStructure.value.sections) {
      stepList.push({
        number: stepNumber++,
        title: section.title,
      })
    }
  }
  
  stepList.push({
    number: stepNumber,
    title: '預覽完成',
  })
  
  return stepList
})

const totalSteps = computed(() => steps.value.length)

// 取得當前區段
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

// 已回答問題數量
const answeredCount = computed(() => {
  return Object.keys(formData.answers).length
})

// 處理答案更新
const handleAnswerUpdate = ({ questionId, value }: { questionId: string; value: AnswerValue }) => {
  formData.answers[questionId] = {
    questionId,
    value,
  }
  
  // 更新可見問題（簡化版，實際應該呼叫 API）
  updateVisibleQuestions()
}

// 更新可見問題
const updateVisibleQuestions = () => {
  // 在預覽模式下，簡單地重新計算本地條件邏輯
  // 實際應用中應該呼叫 API: GET /project-suppliers/{id}/visible-questions
  initializeVisibleQuestions()
}

const nextStep = () => {
  if (currentStep.value < totalSteps.value) {
    currentStep.value++
  }
}

const previousStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const finishPreview = () => {
  router.push('/saq/templates')
}

// 初始化
onMounted(async () => {
  await loadTemplateStructure()
  
  if (templateName.value !== '載入中...') {
    setBreadcrumbs([
      { label: t('common.home'), to: '/' },
      { label: t('apps.saq') },
      { label: t('projects.projectManagement'), to: '/saq/projects' },
      { label: t('templates.management'), to: '/saq/templates' },
      { label: templateName.value },
    ])
  }
})

// 監聽語系變更，重新載入資料
watch(locale, async () => {
  await loadTemplateStructure()
})
</script>