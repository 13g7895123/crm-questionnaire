<template>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-4xl mx-auto">
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
          <UButton
            icon="i-heroicons-arrow-left"
            color="gray"
            variant="ghost"
            to="/saq/templates"
          />
          <h1 class="text-3xl font-bold text-gray-900">Excel 匯入測試</h1>
        </div>
        <UButton
          icon="i-heroicons-document-text"
          color="blue"
          variant="soft"
          label="查看範例"
          to="/saq/templates/test/example"
        />
      </div>

      <!-- Upload Section -->
      <UCard class="mb-6">
        <template #header>
          <h3 class="text-lg font-semibold">上傳 Excel 檔案</h3>
        </template>

        <div class="space-y-4">
          <div 
            class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center bg-gray-50 hover:bg-gray-100 transition-colors cursor-pointer"
            @click="triggerFileInput"
            @dragover.prevent
            @drop.prevent="handleDrop"
          >
            <input
              ref="fileInput"
              type="file"
              accept=".xlsx,.xls"
              class="hidden"
              @change="handleFileChange"
            />
            <UIcon name="i-heroicons-document-arrow-up" class="w-12 h-12 text-gray-400 mx-auto mb-2" />
            <p class="text-sm text-gray-600">
              點擊或拖曳檔案至此處上傳
            </p>
            <p class="text-xs text-gray-400 mt-1">
              支援 .xlsx, .xls 格式
            </p>
          </div>

          <div v-if="selectedFile" class="flex items-center gap-2 p-3 bg-blue-50 rounded-lg">
            <UIcon name="i-heroicons-document" class="w-5 h-5 text-blue-600" />
            <span class="text-sm text-blue-800 flex-1">{{ selectedFile.name }}</span>
            <UButton
              icon="i-heroicons-x-mark"
              color="gray"
              variant="ghost"
              size="xs"
              @click="clearFile"
            />
          </div>

          <UButton
            icon="i-heroicons-arrow-up-tray"
            color="primary"
            :label="uploading ? '上傳中...' : '上傳並解析'"
            :loading="uploading"
            :disabled="!selectedFile"
            @click="uploadFile"
          />
        </div>
      </UCard>

      <!-- Result Section -->
      <UCard v-if="result">
        <template #header>
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold">解析結果</h3>
            <div class="flex gap-2">
              <UBadge color="blue">{{ result.metadata?.totalSections || 0 }} 區段</UBadge>
              <UBadge color="purple">{{ result.metadata?.totalSubsections || 0 }} 子區段</UBadge>
              <UBadge color="green">{{ result.metadata?.totalQuestions || 0 }} 題目</UBadge>
            </div>
          </div>
        </template>

        <div class="space-y-4">
          <!-- File Info -->
          <div class="text-sm text-gray-500">
            <span>檔案名稱:</span>
            <span class="ml-2 font-medium text-gray-800">{{ result.fileName }}</span>
          </div>

          <!-- Sections Tree -->
          <div class="space-y-4">
            <div
              v-for="section in result.sections"
              :key="section.id"
              class="border rounded-lg overflow-hidden"
            >
              <!-- Section Header -->
              <div class="bg-blue-600 text-white px-4 py-3 font-medium flex items-center gap-2">
                <UIcon name="i-heroicons-folder" class="w-5 h-5" />
                {{ section.title }}
              </div>

              <!-- Subsections -->
              <div class="divide-y">
                <div
                  v-for="subsection in section.subsections"
                  :key="subsection.id"
                  class="px-4 py-3"
                >
                  <!-- Subsection Header -->
                  <div class="font-medium text-purple-700 mb-2 flex items-center gap-2">
                    <UIcon name="i-heroicons-folder-open" class="w-4 h-4" />
                    {{ subsection.title }}
                  </div>

                  <!-- Questions -->
                  <div class="ml-6 space-y-2">
                    <div
                      v-for="question in subsection.questions"
                      :key="question.id"
                      class="border-l-2 border-gray-200 pl-3 py-1"
                    >
                      <div class="flex items-start gap-2">
                        <UBadge color="gray" variant="subtle" size="xs">{{ question.id }}</UBadge>
                        <span class="text-sm text-gray-700 flex-1">
                          {{ truncateText(question.text, 100) }}
                        </span>
                        <UBadge :color="getTypeColor(question.type)" size="xs">
                          {{ question.type }}
                        </UBadge>
                      </div>

                      <!-- Follow-up Questions -->
                      <div
                        v-if="question.conditionalLogic?.followUpQuestions"
                        class="ml-4 mt-2 space-y-1"
                      >
                        <div
                          v-for="(rule, rIdx) in question.conditionalLogic.followUpQuestions"
                          :key="rIdx"
                          class="text-xs text-orange-600 flex items-center gap-1"
                        >
                          <UIcon name="i-heroicons-arrow-turn-down-right" class="w-3 h-3" />
                          <span>當回答為「是」時：</span>
                          <span
                            v-for="(fq, fqIdx) in rule.questions"
                            :key="fqIdx"
                            class="text-gray-600"
                          >
                            {{ fq.type === 'TABLE' ? '表格資料' : fq.text }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div
            v-if="result.sections?.length === 0"
            class="text-center py-8 text-gray-500"
          >
            <UIcon name="i-heroicons-document-magnifying-glass" class="w-12 h-12 mx-auto mb-2 text-gray-300" />
            <p>未找到符合格式的分頁</p>
            <p class="text-sm">請確保分頁名稱以 A.、B.、C. 等格式開頭</p>
          </div>
        </div>
      </UCard>

      <!-- Error Section -->
      <UAlert
        v-if="error"
        color="red"
        icon="i-heroicons-exclamation-triangle"
        :title="error"
        class="mt-4"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useBreadcrumbs } from '~/composables/useBreadcrumbs'
import { useI18n } from 'vue-i18n'

definePageMeta({ middleware: 'auth' })

// Types
interface Question {
  id: string
  text: string
  type: string
  required?: boolean
  conditionalLogic?: {
    followUpQuestions?: Array<{
      condition: { operator: string; value: any }
      questions: Array<{ id: string; text?: string; type: string; tableConfig?: any }>
    }>
  }
}

interface Subsection {
  id: string
  title: string
  questions: Question[]
}

interface Section {
  id: string
  title: string
  subsections: Subsection[]
}

interface ParseResult {
  fileName: string
  sections: Section[]
  metadata: {
    totalSections: number
    totalSubsections: number
    totalQuestions: number
  }
}

const { t } = useI18n()
const { setBreadcrumbs } = useBreadcrumbs()

const fileInput = ref<HTMLInputElement | null>(null)
const selectedFile = ref<File | null>(null)
const uploading = ref(false)
const result = ref<ParseResult | null>(null)
const error = ref<string | null>(null)

// Helper functions
const truncateText = (text: string, maxLength: number): string => {
  if (!text) return ''
  return text.length > maxLength ? text.substring(0, maxLength) + '...' : text
}

const getTypeColor = (type: string): string => {
  const colors: Record<string, string> = {
    BOOLEAN: 'blue',
    TEXT: 'green',
    NUMBER: 'purple',
    TABLE: 'orange',
    FILE: 'gray',
  }
  return colors[type] || 'gray'
}

const triggerFileInput = () => {
  fileInput.value?.click()
}

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    selectedFile.value = target.files[0]
    error.value = null
  }
}

const handleDrop = (event: DragEvent) => {
  const files = event.dataTransfer?.files
  if (files && files.length > 0) {
    const file = files[0]
    const ext = file.name.split('.').pop()?.toLowerCase()
    if (ext === 'xlsx' || ext === 'xls') {
      selectedFile.value = file
      error.value = null
    } else {
      error.value = '只支援 .xlsx 或 .xls 格式'
    }
  }
}

const clearFile = () => {
  selectedFile.value = null
  result.value = null
  error.value = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const uploadFile = async () => {
  if (!selectedFile.value) return

  uploading.value = true
  error.value = null
  result.value = null

  try {
    const formData = new FormData()
    formData.append('file', selectedFile.value)

    // Get auth token from store
    const authStore = useAuthStore()
    const headers: Record<string, string> = {}
    if (authStore.token) {
      headers['Authorization'] = `Bearer ${authStore.token}`
    }

    // Use native fetch for FormData (useApi doesn't support it properly)
    const response = await fetch('/api/v1/templates/test-excel', {
      method: 'POST',
      headers,
      body: formData,
      credentials: 'include'
    })

    const data = await response.json()

    if (data.success) {
      result.value = data.data
    } else {
      error.value = data.error?.message || data.message || '解析失敗'
    }
  } catch (e: any) {
    console.error('Upload failed:', e)
    error.value = e.message || '上傳失敗'
  } finally {
    uploading.value = false
  }
}

setBreadcrumbs([
  { label: t('common.home'), to: '/' },
  { label: t('apps.saq') },
  { label: t('templates.management'), to: '/saq/templates' },
  { label: 'Excel 匯入測試' }
])
</script>
