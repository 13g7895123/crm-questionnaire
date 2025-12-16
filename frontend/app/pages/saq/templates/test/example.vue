<template>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-6xl mx-auto">
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
          <UButton
            icon="i-heroicons-arrow-left"
            color="gray"
            variant="ghost"
            to="/saq/templates/test"
          />
          <h1 class="text-3xl font-bold text-gray-900">Excel 格式範例與預覽</h1>
        </div>
        <div class="flex gap-2">
          <UButton
            :color="viewMode === 'preview' ? 'primary' : 'gray'"
            variant="soft"
            label="問卷預覽"
            icon="i-heroicons-eye"
            @click="viewMode = 'preview'"
          />
          <UButton
            :color="viewMode === 'format' ? 'primary' : 'gray'"
            variant="soft"
            label="格式說明"
            icon="i-heroicons-document-text"
            @click="viewMode = 'format'"
          />
        </div>
      </div>

      <!-- 問卷預覽模式 -->
      <div v-if="viewMode === 'preview'" class="space-y-6">
        <!-- 步驟選擇器 -->
        <UCard>
          <template #header>
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold">選擇區段預覽</h3>
              <div class="flex gap-2">
                <UButton
                  v-for="section in exampleSections"
                  :key="section.id"
                  :color="currentSection?.id === section.id ? 'primary' : 'gray'"
                  variant="soft"
                  size="sm"
                  :label="section.id"
                  @click="currentSection = section"
                />
              </div>
            </div>
          </template>

          <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded mb-4">
            <p class="text-sm text-blue-800">
              <strong>這是使用者填答時會看到的介面</strong>，展示從 Excel 匯入後的題目呈現方式
            </p>
          </div>

          <!-- 問卷填答介面 -->
          <div class="bg-white border rounded-lg p-6">
            <QuestionnaireStepDynamicQuestions
              v-if="currentSection"
              :section="currentSection"
              v-model="answers"
            />
          </div>
        </UCard>

        <!-- 答案預覽 -->
        <UCard>
          <template #header>
            <h3 class="text-lg font-semibold">填答資料預覽 (Debug)</h3>
          </template>
          <div class="bg-gray-900 text-gray-100 p-4 rounded-lg font-mono text-xs overflow-x-auto">
            <pre>{{ JSON.stringify(answers, null, 2) }}</pre>
          </div>
        </UCard>
      </div>

      <!-- 格式說明模式 -->
      <div v-else class="space-y-6">
        <!-- 格式概述 -->
        <UCard>
          <template #header>
            <h3 class="text-lg font-semibold">📋 格式概述</h3>
          </template>

          <div class="prose max-w-none">
            <p class="text-gray-600 mb-4">
              Excel 檔案應包含多個分頁，每個分頁對應問卷的一個區段。系統會根據分頁標題前綴來識別區段。
            </p>

            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
              <p class="font-medium text-blue-800">分頁命名規則</p>
              <p class="text-blue-700 text-sm mt-1">
                分頁標題需以 <code class="bg-blue-100 px-1 rounded">A.</code>、<code class="bg-blue-100 px-1 rounded">B.</code>、<code class="bg-blue-100 px-1 rounded">C.</code> 等格式開頭
              </p>
            </div>
          </div>
        </UCard>

        <!-- 欄位對應 -->
        <UCard>
          <template #header>
            <h3 class="text-lg font-semibold">📊 欄位對應表</h3>
          </template>

          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left font-medium text-gray-700 border-b">欄位</th>
                  <th class="px-4 py-3 text-left font-medium text-gray-700 border-b">Excel 標題</th>
                  <th class="px-4 py-3 text-left font-medium text-gray-700 border-b">對應欄位</th>
                  <th class="px-4 py-3 text-left font-medium text-gray-700 border-b">說明</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="col in columnMappings" :key="col.column" class="hover:bg-gray-50">
                  <td class="px-4 py-3 border-b font-mono text-blue-600">{{ col.column }}</td>
                  <td class="px-4 py-3 border-b">{{ col.header }}</td>
                  <td class="px-4 py-3 border-b">
                    <UBadge :color="col.fieldColor" variant="subtle">{{ col.field }}</UBadge>
                  </td>
                  <td class="px-4 py-3 border-b text-gray-600">{{ col.description }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </UCard>

        <!-- 題目類型範例 -->
        <UCard>
          <template #header>
            <h3 class="text-lg font-semibold">📝 題目類型</h3>
          </template>

          <div class="space-y-6">
            <!-- 一般題目 -->
            <div>
              <h4 class="font-medium text-gray-800 mb-3 flex items-center gap-2">
                <UBadge color="blue">Type 1</UBadge>
                一般題目 (單列)
              </h4>
              <div class="overflow-x-auto border rounded-lg">
                <table class="w-full text-sm">
                  <thead class="bg-gray-100">
                    <tr>
                      <th class="px-3 py-2 text-center w-16 border-r">B</th>
                      <th class="px-3 py-2 text-left border-r">C</th>
                      <th class="px-3 py-2 text-center w-24 border-r">D</th>
                      <th class="px-3 py-2 text-left">E</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td class="px-3 py-2 text-center font-mono text-blue-600 border-r">A.1.2</td>
                      <td class="px-3 py-2 border-r">Does the Company establish...</td>
                      <td class="px-3 py-2 text-center border-r">
                        <UBadge color="gray" variant="subtle">是/否</UBadge>
                      </td>
                      <td class="px-3 py-2 text-gray-600 text-xs">
                        <code>=IF(D7=1, "Content of...", "")</code>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- 表格題目 -->
            <div>
              <h4 class="font-medium text-gray-800 mb-3 flex items-center gap-2">
                <UBadge color="purple">Type 2</UBadge>
                表格題目 (多列 N×N)
              </h4>
              <div class="overflow-x-auto border rounded-lg">
                <table class="w-full text-sm">
                  <thead class="bg-gray-100">
                    <tr>
                      <th class="px-3 py-2 text-center w-16 border-r">B</th>
                      <th class="px-3 py-2 text-left border-r">C</th>
                      <th class="px-3 py-2 text-center w-16 border-r">D</th>
                      <th class="px-3 py-2 text-left w-32 border-r">E</th>
                      <th class="px-3 py-2 text-center border-r">F</th>
                      <th class="px-3 py-2 text-center border-r">G</th>
                      <th class="px-3 py-2 text-center">H</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(row, idx) in tableQuestionExample" :key="idx" class="border-t">
                      <td class="px-3 py-2 text-center font-mono text-blue-600 border-r" :rowspan="idx === 0 ? 5 : undefined" v-if="idx === 0">
                        A.1.3
                      </td>
                      <td class="px-3 py-2 border-r text-xs" :rowspan="idx === 0 ? 5 : undefined" v-if="idx === 0">
                        Has the Company violated...
                      </td>
                      <td class="px-3 py-2 text-center border-r" :rowspan="idx === 0 ? 5 : undefined" v-if="idx === 0">
                        <UBadge color="gray" variant="subtle">是</UBadge>
                      </td>
                      <td class="px-3 py-2 border-r text-gray-600">{{ row.label }}</td>
                      <td class="px-3 py-2 text-center border-r text-xs text-gray-400">{{ row.col1 }}</td>
                      <td class="px-3 py-2 text-center border-r text-xs text-gray-400">{{ row.col2 }}</td>
                      <td class="px-3 py-2 text-center text-xs text-gray-400">{{ row.col3 }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </UCard>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useBreadcrumbs } from '~/composables/useBreadcrumbs'
import { useI18n } from 'vue-i18n'
import QuestionnaireStepDynamicQuestions from '~/components/questionnaire/StepDynamicQuestions.vue'

definePageMeta({ middleware: 'auth' })

const { t } = useI18n()
const { setBreadcrumbs } = useBreadcrumbs()

const viewMode = ref<'preview' | 'format'>('preview')
const answers = ref<Record<string, any>>({})

// 範例區段資料 (模擬 Excel 匯入後的結構)
const exampleSections = [
  {
    id: 'A',
    title: 'A. 勞工權益 (Labor Rights)',
    subsections: [
      {
        id: 'A.1',
        title: 'A.1 勞動管理',
        questions: [
          {
            id: 'A.1.1',
            text: '貴公司是否有制定並執行勞動政策？',
            type: 'boolean' as const,
            required: true,
            followUp: {
              condition: true,
              questions: [
                {
                  id: 'A.1.1.1',
                  text: '請描述貴公司的勞動政策內容',
                  type: 'text' as const,
                },
              ],
            },
          },
          {
            id: 'A.1.2',
            text: '貴公司是否建立定期識別勞動風險的程序？',
            type: 'boolean' as const,
            required: true,
          },
          {
            id: 'A.1.3',
            text: '近三年公司是否因違犯勞動法規遭政府判罰？',
            type: 'boolean' as const,
            required: true,
            followUp: {
              condition: true,
              questions: [
                {
                  id: 'A.1.3.1',
                  text: '違規詳情',
                  type: 'table' as const,
                  columns: ['年度', '違犯件數', '金額(USD)', '違犯事項', '改善措施'],
                  rows: ['2024', '2023', '2022'],
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
        title: 'B.1 職業安全管理',
        questions: [
          {
            id: 'B.1.1',
            text: '貴公司是否具有 ISO 45001 或同等職業安全衛生管理系統認證？',
            type: 'boolean' as const,
            required: true,
          },
          {
            id: 'B.1.2',
            text: '過去一年是否發生重大工安事故？',
            type: 'boolean' as const,
            required: true,
            followUp: {
              condition: true,
              questions: [
                {
                  id: 'B.1.2.1',
                  text: '事故詳情',
                  type: 'table' as const,
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
]

const currentSection = ref(exampleSections[0])

// 格式說明資料
const columnMappings = [
  { column: 'B', header: 'No.', field: 'question_id', fieldColor: 'blue', description: '題目編號 (A.1.1, A.1.2...)' },
  { column: 'C', header: '項目 Item', field: 'text', fieldColor: 'green', description: '題目內容' },
  { column: 'D', header: '公司自評 Self Assessment', field: 'type: BOOLEAN', fieldColor: 'purple', description: '是/否選項' },
  { column: 'E', header: '備註 Remark', field: 'followUp', fieldColor: 'orange', description: '條件式追問（當 D=是）' },
  { column: 'F~H', header: '附件/說明 Evidence', field: 'table columns', fieldColor: 'gray', description: '表格題的資料欄位' },
]

const tableQuestionExample = [
  { label: 'Year 年度:', col1: '2024', col2: '2023', col3: '2022' },
  { label: 'Number of Fine 違犯件數:', col1: '', col2: '', col3: '' },
  { label: 'Fine(USD) 金額:', col1: '', col2: '', col3: '' },
  { label: 'Description 違犯事項:', col1: '', col2: '', col3: '' },
  { label: 'Corrective Action 改善措施:', col1: '', col2: '', col3: '' },
]

setBreadcrumbs([
  { label: t('common.home'), to: '/' },
  { label: t('apps.saq') },
  { label: t('templates.management'), to: '/saq/templates' },
  { label: 'Excel 匯入測試', to: '/saq/templates/test' },
  { label: '格式範例與預覽' }
])
</script>
