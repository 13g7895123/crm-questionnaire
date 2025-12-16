<template>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-6xl mx-auto">
      <div class="flex items-center gap-4 mb-8">
        <UButton
          icon="i-heroicons-arrow-left"
          color="gray"
          variant="ghost"
          to="/saq/templates/test"
        />
        <h1 class="text-3xl font-bold text-gray-900">Excel 格式範例說明</h1>
      </div>

      <!-- 格式概述 -->
      <UCard class="mb-6">
        <template #header>
          <h3 class="text-lg font-semibold">📋 格式概述</h3>
        </template>

        <div class="prose max-w-none">
          <p class="text-gray-600 mb-4">
            Excel 檔案應包含多個分頁（工作表），每個分頁對應問卷的一個區段。系統會根據分頁標題前綴來識別區段。
          </p>

          <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
            <p class="font-medium text-blue-800">分頁命名規則</p>
            <p class="text-blue-700 text-sm mt-1">
              分頁標題需以 <code class="bg-blue-100 px-1 rounded">A.</code>、<code class="bg-blue-100 px-1 rounded">B.</code>、<code class="bg-blue-100 px-1 rounded">C.</code> 等格式開頭
            </p>
          </div>
        </div>
      </UCard>

      <!-- 分頁結構 -->
      <UCard class="mb-6">
        <template #header>
          <h3 class="text-lg font-semibold">📄 分頁結構範例</h3>
        </template>

        <div class="space-y-4">
          <p class="text-sm text-gray-600">以下為範例 Excel 檔案的分頁結構：</p>
          
          <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            <div 
              v-for="sheet in exampleSheets" 
              :key="sheet.name"
              class="p-3 rounded-lg border-2"
              :class="sheet.isValid ? 'border-green-300 bg-green-50' : 'border-gray-200 bg-gray-50'"
            >
              <div class="flex items-center gap-2">
                <UIcon 
                  :name="sheet.isValid ? 'i-heroicons-check-circle' : 'i-heroicons-minus-circle'" 
                  :class="sheet.isValid ? 'text-green-600' : 'text-gray-400'"
                />
                <span class="font-medium text-sm">{{ sheet.name }}</span>
              </div>
              <p class="text-xs text-gray-500 mt-1">{{ sheet.description }}</p>
            </div>
          </div>
        </div>
      </UCard>

      <!-- 欄位對應 -->
      <UCard class="mb-6">
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

      <!-- 資料結構說明 -->
      <UCard class="mb-6">
        <template #header>
          <h3 class="text-lg font-semibold">🏗️ 資料層級結構</h3>
        </template>

        <div class="space-y-4">
          <p class="text-sm text-gray-600">Excel 資料會依據編號格式解析為三層結構：</p>

          <div class="bg-gray-900 text-gray-100 p-4 rounded-lg font-mono text-sm overflow-x-auto">
            <pre>Section (分頁標題)
├── A. Labor Rights 勞工權益
│
├── Subsection (小標題)
│   ├── A.1. Labor Management 勞工管理
│   └── A.2. Labor Practice 勞動實踐
│
└── Question (題目)
    ├── A.1.1 題目一
    ├── A.1.2 題目二
    └── A.1.3 題目三 (表格題)</pre>
          </div>
        </div>
      </UCard>

      <!-- 題目類型 -->
      <UCard class="mb-6">
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
            <p class="text-xs text-gray-500 mt-2">
              ⚡ 備註欄(E)使用 IF 公式：當「公司自評」為「是」時，顯示追問題目
            </p>
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
            <p class="text-xs text-gray-500 mt-2">
              ⚡ B、C 欄合併儲存格跨越多列。E 欄為列標題，F~H 欄為年度資料欄位
            </p>
          </div>
        </div>
      </UCard>

      <!-- 解析結果 -->
      <UCard>
        <template #header>
          <h3 class="text-lg font-semibold">🔄 解析輸出格式</h3>
        </template>

        <div class="bg-gray-900 text-gray-100 p-4 rounded-lg font-mono text-xs overflow-x-auto">
          <pre>{{ JSON.stringify(outputExample, null, 2) }}</pre>
        </div>
      </UCard>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useBreadcrumbs } from '~/composables/useBreadcrumbs'
import { useI18n } from 'vue-i18n'

definePageMeta({ middleware: 'auth' })

const { t } = useI18n()
const { setBreadcrumbs } = useBreadcrumbs()

const exampleSheets = [
  { name: 'Company Information', isValid: false, description: '公司基本資訊（不解析）' },
  { name: 'A. Labor Rights', isValid: true, description: '勞工權益區段' },
  { name: 'B. Health and Safety', isValid: true, description: '健康與安全區段' },
  { name: 'C. Environmental Protection', isValid: true, description: '環境保護區段' },
  { name: 'D. Sustainability & Risk', isValid: true, description: '永續與風險區段' },
  { name: 'E. Supply Chain Management', isValid: true, description: '供應鏈管理區段' },
]

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

const outputExample = {
  sections: [
    {
      id: 'A',
      title: 'A. Labor Rights 勞工權益',
      subsections: [
        {
          id: 'A.1',
          title: 'A.1. Labor Management 勞工管理',
          questions: [
            {
              id: 'A.1.1',
              text: 'Does the Company establish...',
              type: 'BOOLEAN',
              required: true,
            },
            {
              id: 'A.1.3',
              text: 'Has the Company violated...',
              type: 'BOOLEAN',
              required: true,
              conditionalLogic: {
                followUpQuestions: [
                  {
                    condition: { operator: 'equals', value: true },
                    questions: [
                      { 
                        id: 'A.1.3.table', 
                        type: 'TABLE',
                        tableConfig: {
                          columns: ['Year', 'Number of Fine', 'Fine(USD)', 'Description', 'Corrective Action'],
                          rows: ['2024', '2023', '2022']
                        }
                      }
                    ]
                  }
                ]
              }
            }
          ]
        }
      ]
    }
  ]
}

setBreadcrumbs([
  { label: t('common.home'), to: '/' },
  { label: t('apps.saq') },
  { label: t('templates.management'), to: '/saq/templates' },
  { label: 'Excel 匯入測試', to: '/saq/templates/test' },
  { label: '格式範例說明' }
])
</script>
