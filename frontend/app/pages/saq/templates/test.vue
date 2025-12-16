<template>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-4xl mx-auto">
      <div class="flex items-center gap-4 mb-8">
        <UButton
          icon="i-heroicons-arrow-left"
          color="gray"
          variant="ghost"
          to="/saq/templates"
        />
        <h1 class="text-3xl font-bold text-gray-900">Excel 匯入測試</h1>
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
            <UBadge color="green">{{ result.rowCount }} 列</UBadge>
          </div>
        </template>

        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <span class="text-gray-500">檔案名稱:</span>
              <span class="ml-2 font-medium">{{ result.fileName }}</span>
            </div>
            <div>
              <span class="text-gray-500">工作表:</span>
              <span class="ml-2 font-medium">{{ result.sheetName }}</span>
            </div>
          </div>

          <div class="overflow-auto max-h-96 border rounded-lg">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 sticky top-0">
                <tr>
                  <th 
                    v-for="(header, idx) in result.data[0]" 
                    :key="idx"
                    class="px-3 py-2 text-left font-medium text-gray-700 border-b"
                  >
                    {{ header || `欄位 ${idx + 1}` }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr 
                  v-for="(row, rowIdx) in result.data.slice(1)" 
                  :key="rowIdx"
                  class="hover:bg-gray-50"
                >
                  <td 
                    v-for="(cell, cellIdx) in row" 
                    :key="cellIdx"
                    class="px-3 py-2 border-b text-gray-600"
                  >
                    {{ cell ?? '' }}
                  </td>
                </tr>
              </tbody>
            </table>
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
import { useApi } from '~/composables/useApi'
import { useBreadcrumbs } from '~/composables/useBreadcrumbs'
import { useI18n } from 'vue-i18n'

definePageMeta({ middleware: 'auth' })

const { t } = useI18n()
const { setBreadcrumbs } = useBreadcrumbs()
const api = useApi()

const fileInput = ref<HTMLInputElement | null>(null)
const selectedFile = ref<File | null>(null)
const uploading = ref(false)
const result = ref<{ fileName: string; sheetName: string; rowCount: number; data: any[][] } | null>(null)
const error = ref<string | null>(null)

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

    const response = await api.post('/templates/test-excel', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    if (response.data.success) {
      result.value = response.data.data
    } else {
      error.value = response.data.message || '解析失敗'
    }
  } catch (e: any) {
    console.error('Upload failed:', e)
    error.value = e.response?.data?.message || e.message || '上傳失敗'
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
