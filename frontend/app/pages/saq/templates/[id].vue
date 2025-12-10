<template>
  <div class="min-h-screen bg-gray-50">
    <!-- 頂部導航 -->
    <div class="bg-white shadow-sm sticky top-0 z-10">
      <div class="container mx-auto px-6 py-4">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ templateName }}</h1>
            <p class="text-sm text-gray-500 mt-1">
              步驟 {{ currentStep }}/{{ totalSteps }}
            </p>
          </div>
          <button
            class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-100"
            @click="$router.push('/saq/templates')"
          >
            返回列表
          </button>
        </div>
      </div>
    </div>

    <!-- 載入狀態 -->
    <div v-if="loading" class="container mx-auto px-6 py-12 text-center">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
      <p class="mt-4 text-gray-600">載入範本中...</p>
    </div>

    <!-- 錯誤狀態 -->
    <div v-else-if="error" class="container mx-auto px-6 py-12">
      <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
        <p class="text-red-600">{{ error }}</p>
        <button
          class="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
          @click="$router.push('/saq/templates')"
        >
          返回列表
        </button>
      </div>
    </div>

    <!-- 主要內容 -->
    <div v-else class="container mx-auto px-6 py-6">
      <!-- 步驟指示器 -->
      <div class="flex items-center justify-between mb-8">
        <div
          v-for="step in steps"
          :key="step.number"
          class="flex items-center flex-1"
        >
          <div class="flex flex-col items-center flex-1">
            <div
              :class="[
                'w-10 h-10 rounded-full flex items-center justify-center font-semibold transition-all',
                currentStep >= step.number
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-200 text-gray-500',
              ]"
            >
              {{ step.number }}
            </div>
            <span class="text-xs mt-2 text-center max-w-[100px]">{{ step.title }}</span>
          </div>
          <div
            v-if="step.number < steps.length"
            :class="[
              'h-1 flex-1 mx-2',
              currentStep > step.number ? 'bg-blue-600' : 'bg-gray-200',
            ]"
          />
        </div>
      </div>

      <!-- 表單內容 -->
      <div class="bg-white rounded-lg shadow-md p-8 max-w-5xl mx-auto">
        <!-- 步驟 1：基本資訊 -->
        <BasicInfoFormV2
          v-if="currentStep === 1 && templateStructure?.includeBasicInfo"
          v-model="formData.basicInfo"
        />

        <!-- 步驟 2-N：區段問題 -->
        <SectionFormV2
          v-else-if="currentSection"
          :section="currentSection"
          :answers="formData.answers"
          :visible-questions="visibleQuestions"
          @update:answer="handleAnswerUpdate"
        />

        <!-- 最後一步：結果預覽 -->
        <div v-else-if="currentStep === totalSteps" class="space-y-6">
          <h2 class="text-2xl font-bold text-gray-800">預覽完成</h2>
          <p class="text-gray-600">
            這是範本預覽模式，實際問卷填寫時將會儲存答案並計算分數。
          </p>
          
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-3">範本資訊</h3>
            <dl class="space-y-2">
              <div class="flex justify-between">
                <dt class="text-gray-600">範本名稱：</dt>
                <dd class="font-medium">{{ templateName }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-gray-600">區段數量：</dt>
                <dd class="font-medium">{{ templateStructure?.sections?.length || 0 }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-gray-600">總步驟數：</dt>
                <dd class="font-medium">{{ totalSteps }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-gray-600">已填答數：</dt>
                <dd class="font-medium">{{ answeredCount }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <!-- 操作按鈕 -->
      <div class="flex justify-between max-w-5xl mx-auto mt-6">
        <button
          v-if="currentStep > 1"
          class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium"
          @click="previousStep"
        >
          上一步
        </button>
        <div v-else />
        
        <button
          v-if="currentStep < totalSteps"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
          @click="nextStep"
        >
          下一步
        </button>
        <button
          v-else
          class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium"
          @click="finishPreview"
        >
          完成預覽
        </button>
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
const { t } = useI18n()
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
    const { data: templateData, error: apiError } = await useFetch(
      `${config.public.apiBase}/api/v1/templates/${templateId.value}/structure`,
      {
        headers: {
          'Content-Type': 'application/json',
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
</script>