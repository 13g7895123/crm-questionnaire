<template>
  <div class="min-h-screen bg-gray-50">
    <!-- 顶部导航 -->
    <div class="bg-white shadow-sm sticky top-0 z-10">
      <div class="container mx-auto px-6 py-4">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ templateName }}</h1>
            <p class="text-sm text-gray-500 mt-1">步驟 {{ currentStep }}/6</p>
          </div>
          <button
            @click="$router.push('/templates')"
            class="text-gray-600 hover:text-gray-800"
          >
            返回
          </button>
        </div>
      </div>
    </div>

    <!-- 步骤指示器 -->
    <div class="container mx-auto px-6 py-6">
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
            <span class="text-xs mt-2 text-center">{{ step.title }}</span>
          </div>
          <div
            v-if="step.number < steps.length"
            :class="[
              'h-1 flex-1 mx-2',
              currentStep > step.number ? 'bg-blue-600' : 'bg-gray-200',
            ]"
          ></div>
        </div>
      </div>

      <!-- 表单内容 -->
      <div class="bg-white rounded-lg shadow-md p-8 max-w-4xl mx-auto">
        <!-- 第一步：公司基本资料 -->
        <div v-if="currentStep === 1">
          <QuestionnaireStepOneBasicInfo v-model="formData.step1" />
        </div>

        <!-- 第二步到第五步：A-E 评估 -->
        <div v-else-if="currentStep >= 2 && currentStep <= 5">
          <QuestionnaireStepDynamicQuestions
            :section="sections[currentStep - 2]"
            v-model="formData[`step${currentStep}`]"
          />
        </div>

        <!-- 第六步：结果展示 -->
        <div v-else-if="currentStep === 6">
          <QuestionnaireStepResults :data="formData" />
        </div>
      </div>

      <!-- 操作按钮 -->
      <div class="flex justify-between max-w-4xl mx-auto mt-6">
        <button
          v-if="currentStep > 1"
          @click="previousStep"
          class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
        >
          上一步
        </button>
        <div v-else></div>
        <button
          v-if="currentStep < 6"
          @click="nextStep"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
          下一步
        </button>
        <button
          v-else
          @click="submitForm"
          class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
        >
          提交問卷
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute();
const router = useRouter();

const templateName = ref('2025 SAQ 供應商評估範本');
const currentStep = ref(1);

const steps = [
  { number: 1, title: '公司基本資料' },
  { number: 2, title: 'A. 勞工' },
  { number: 3, title: 'B. 健康與安全' },
  { number: 4, title: 'C. 環境' },
  { number: 5, title: 'D. 道德規範' },
  { number: 6, title: '評估結果' },
];

const sections = [
  {
    id: 'A',
    title: 'A. 勞工 (Labor)',
    subsections: [
      {
        id: 'A.1',
        title: 'A.1 勞動管理',
        questions: [
          {
            id: 'A.1.1',
            text: '貴公司是否有制定並執行勞動政策？',
            type: 'boolean',
            followUp: {
              condition: true,
              questions: [
                {
                  id: 'A.1.1.1',
                  text: '請描述貴公司的勞動政策內容',
                  type: 'text',
                },
              ],
            },
          },
          {
            id: 'A.1.2',
            text: '過去三年是否有違反勞動法規的紀錄？',
            type: 'boolean',
            followUp: {
              condition: true,
              questions: [
                {
                  id: 'A.1.2.1',
                  text: '違規詳情',
                  type: 'table',
                  columns: ['年度', '違犯件數', '金額(USD)', '違犯事項', '改善措施'],
                  rows: ['2023', '2024', '2025'],
                },
              ],
            },
          },
          {
            id: 'A.1.3',
            text: '是否定期進行員工滿意度調查？',
            type: 'boolean',
            followUp: {
              condition: true,
              questions: [
                {
                  id: 'A.1.3.1',
                  text: '最近一次調查的滿意度分數',
                  type: 'number',
                },
              ],
            },
          },
        ],
      },
      {
        id: 'A.2',
        title: 'A.2 工作時間',
        questions: [
          {
            id: 'A.2.1',
            text: '每週工作時數是否符合當地法規？',
            type: 'boolean',
          },
          {
            id: 'A.2.2',
            text: '是否有加班管理制度？',
            type: 'boolean',
            followUp: {
              condition: true,
              questions: [
                {
                  id: 'A.2.2.1',
                  text: '每月平均加班時數',
                  type: 'number',
                },
              ],
            },
          },
        ],
      },
    ],
  },
  {
    id: 'B',
    title: 'B. 健康與安全 (Health & Safety)',
    subsections: [
      {
        id: 'B.1',
        title: 'B.1 職業安全',
        questions: [
          {
            id: 'B.1.1',
            text: '是否具有職業安全衛生管理系統認證（如 ISO 45001）？',
            type: 'boolean',
          },
          {
            id: 'B.1.2',
            text: '過去一年是否發生重大工安事故？',
            type: 'boolean',
            followUp: {
              condition: true,
              questions: [
                {
                  id: 'B.1.2.1',
                  text: '事故詳情',
                  type: 'table',
                  columns: ['日期', '事故類型', '受傷人數', '損失金額(USD)', '改善措施'],
                  rows: ['事故1', '事故2', '事故3'],
                },
              ],
            },
          },
        ],
      },
    ],
  },
  {
    id: 'C',
    title: 'C. 環境 (Environment)',
    subsections: [
      {
        id: 'C.1',
        title: 'C.1 環境管理',
        questions: [
          {
            id: 'C.1.1',
            text: '是否具有環境管理系統認證（如 ISO 14001）？',
            type: 'boolean',
          },
          {
            id: 'C.1.2',
            text: '是否有溫室氣體排放管理計畫？',
            type: 'boolean',
            followUp: {
              condition: true,
              questions: [
                {
                  id: 'C.1.2.1',
                  text: '年度碳排放量統計',
                  type: 'table',
                  columns: ['年度', '排放量(噸CO2e)', '減量目標(%)', '實際減量(%)', '措施說明'],
                  rows: ['2023', '2024', '2025'],
                },
              ],
            },
          },
        ],
      },
    ],
  },
  {
    id: 'D',
    title: 'D. 道德規範 (Ethics)',
    subsections: [
      {
        id: 'D.1',
        title: 'D.1 商業誠信',
        questions: [
          {
            id: 'D.1.1',
            text: '是否制定反貪腐與反賄賂政策？',
            type: 'boolean',
          },
          {
            id: 'D.1.2',
            text: '是否有揭弊者保護機制？',
            type: 'boolean',
            followUp: {
              condition: true,
              questions: [
                {
                  id: 'D.1.2.1',
                  text: '請描述揭弊者保護機制的運作方式',
                  type: 'text',
                },
              ],
            },
          },
        ],
      },
      {
        id: 'D.2',
        title: 'D.2 資訊安全',
        questions: [
          {
            id: 'D.2.1',
            text: '是否具有資訊安全管理系統認證（如 ISO 27001）？',
            type: 'boolean',
          },
        ],
      },
    ],
  },
];

const formData = ref({
  step1: {},
  step2: {},
  step3: {},
  step4: {},
  step5: {},
});

const nextStep = () => {
  if (currentStep.value < 6) {
    currentStep.value++;
  }
};

const previousStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--;
  }
};

const submitForm = () => {
  alert('問卷已提交！');
  console.log('Form Data:', formData.value);
};
</script>
