<template>
  <RMOnlineForm
    v-if="onlineFormMode.isEditing"
    :assignment-id="id"
    :assignment="assignment"
    :type="onlineFormMode.type"
    :initial-data="currentOnlineData"
    @back="onlineFormMode.isEditing = false"
    @saved="onOnlineFormSaved"
    @submitted="onOnlineFormSubmitted"
  />

  <div v-else class="space-y-8 animate-fade-in pt-6 pb-8">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 px-6">
        <div class="flex items-center gap-4">
          <UButton
            icon="i-heroicons-arrow-left"
            color="gray"
            variant="ghost"
            class="rounded-full"
            @click="$emit('back')"
          />
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ assignment?.project?.name || '責任礦產問卷' }}</h1>
            <p class="text-sm text-gray-500 mt-1">
              <span class="font-medium text-gray-700">{{ assignment?.supplier_name }}</span>
              <span class="mx-2">•</span>
              {{ $t('questionnaire.instruction') || '請依序完成以下範本填寫，或直接上傳官方 Excel。' }}
            </p>
          </div>
        </div>
        <div class="flex flex-col items-end gap-2">
          <UBadge :color="statusColor" variant="solid" size="lg" class="px-4 py-1.5 rounded-full font-bold shadow-sm">
            <span class="relative flex h-2 w-2 mr-2" v-if="assignment?.status === 'in_progress'">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
            </span>
            {{ statusText }}
          </UBadge>
        </div>
      </div>

      <!-- Summary Statistics Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 px-6">
        <UCard
          v-for="tmpl in assignedTemplates"
          :key="tmpl.type"
          :ui="{ 
            base: 'relative group overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-1',
            body: { padding: 'p-0' },
            ring: tmpl.completed ? 'ring-2 ring-green-500' : 'ring-1 ring-gray-100 hover:ring-primary-500'
          }"
        >
          <div class="p-6">
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-4">
                <div
                  class="w-14 h-14 rounded-2xl flex items-center justify-center transition-colors shadow-inner"
                  :class="tmpl.completed ? 'bg-green-50 text-green-600' : 'bg-orange-50 text-orange-500'"
                >
                  <UIcon :name="tmpl.completed ? 'i-heroicons-check-badge' : 'i-heroicons-document-text'" class="w-8 h-8" />
                </div>
                <div>
                  <div class="font-black text-xl text-gray-900 tracking-tight">{{ tmpl.type }}</div>
                  <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ tmpl.version || 'Revised 2025' }}</div>
                </div>
              </div>
              
              <div class="flex flex-col items-end">
                <UBadge :color="tmpl.completed ? 'green' : 'orange'" variant="soft" size="xs" class="font-bold">
                  {{ tmpl.completed ? $t('common.completed') : $t('common.pending') }}
                </UBadge>
              </div>
            </div>

            <div class="mt-8 flex gap-2">
              <UButton
                block
                v-if="!tmpl.completed"
                size="md"
                color="primary"
                variant="solid"
                class="font-bold shadow-lg shadow-primary-500/20"
                @click="openUploadModal(tmpl.type)"
              >
                {{ $t('conflict.uploadExcel') }}
              </UButton>
              <UButton
                block
                v-else
                size="md"
                color="green"
                variant="soft"
                icon="i-heroicons-pencil-square"
                @click="enterOnlineForm(tmpl.type)"
              >
                {{ $t('common.modify') }}
              </UButton>
            </div>
          </div>
          
          <!-- Background Decoration -->
          <div class="absolute -right-4 -bottom-4 opacity-[0.03] rotate-12 transition-transform group-hover:scale-110">
            <UIcon :name="tmpl.completed ? 'i-heroicons-check-circle' : 'i-heroicons-document-text'" class="w-24 h-24" />
          </div>
        </UCard>
      </div>

      <!-- Review Feedback Section -->
      <transition name="slide-down">
        <div v-if="latestReturnLog" class="bg-red-50 rounded-2xl p-6 border border-red-100 shadow-sm relative overflow-hidden mx-6">
          <div class="flex items-start gap-4">
            <div class="bg-red-100 p-2 rounded-xl text-red-600">
              <UIcon name="i-heroicons-exclamation-triangle" class="w-6 h-6" />
            </div>
            <div>
              <h3 class="font-black text-red-900">{{ $t('review.returnFeedback') }}</h3>
              <p class="text-sm text-red-700 mt-1 leading-relaxed">{{ latestReturnLog.comment }}</p>
            </div>
          </div>
          <div class="absolute right-0 top-0 bottom-0 w-2 bg-red-400 opacity-20"></div>
        </div>
      </transition>

      <!-- Detailed View -->
      <UCard :ui="{ body: { padding: 'p-0' }, rounded: 'rounded-2xl', shadow: 'shadow-lg' }" class="overflow-hidden border-none ring-1 ring-gray-100 mx-6">
        <UTabs :items="tabItems" @change="onTabChange" :ui="{ list: { base: 'bg-gray-50/50 p-1 border-b border-gray-100', rounded: 'rounded-none' } }">
          <template #default="{ item, selected }">
            <div class="flex items-center gap-2 px-6 py-3 transition-all duration-300">
              <UIcon name="i-heroicons-document-text" class="w-5 h-5 transition-all" :class="selected ? 'text-primary-500 scale-110' : 'text-gray-400'" />
              <span class="font-bold text-sm" :class="selected ? 'text-gray-900' : 'text-gray-400'">{{ item.label }}</span>
              <UIcon v-if="item.completed" name="i-heroicons-check-circle-20-solid" class="w-4 h-4 text-green-500" />
            </div>
          </template>

          <template #item="{ item }">
            <div class="p-8">
              <div v-if="!tmplStatus[item.type]?.completed" class="flex flex-col items-center justify-center py-12 space-y-8 animate-fade-in">
                <div class="relative">
                  <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center text-gray-300">
                    <UIcon name="i-heroicons-cloud-arrow-up" class="w-12 h-12" />
                  </div>
                  <div class="absolute -right-1 -bottom-1 w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center border-4 border-white">
                    <UIcon name="i-heroicons-plus" class="w-4 h-4 text-white" />
                  </div>
                </div>
                
                <div class="text-center max-w-sm">
                  <h3 class="text-xl font-black text-gray-900 italic">{{ $t('common.noDataYet') }}</h3>
                  <p class="text-sm text-gray-400 font-medium mt-2">{{ $t('conflict.uploadInstruction') || '請下載官方範本填寫後上傳，或使用直觀的線上表單進行手動輸入。' }}</p>
                </div>

                <div class="flex flex-wrap justify-center gap-4 w-full">
                  <UButton
                    size="lg"
                    color="white"
                    icon="i-heroicons-arrow-down-tray"
                    class="px-8 shadow-sm ring-1 ring-gray-200"
                    @click="handleDownloadTemplate(item.type)"
                  >
                    {{ $t('common.downloadTemplate') }}
                  </UButton>
                  <UButton
                    size="lg"
                    color="primary"
                    icon="i-heroicons-plus"
                    class="px-8 shadow-lg shadow-primary-500/30"
                    @click="openUploadModal(item.type)"
                  >
                    {{ $t('conflict.uploadExcel') }}
                  </UButton>
                </div>
              </div>

              <div v-else class="space-y-8 animate-fade-in">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 bg-gradient-to-r from-green-50 to-emerald-50 rounded-2xl border border-green-100 shadow-sm relative overflow-hidden">
                  <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-green-500 ring-4 ring-green-100/50">
                      <UIcon name="i-heroicons-check-circle" class="w-7 h-7" />
                    </div>
                    <div>
                      <div class="font-black text-green-900 tracking-tight">{{ $t('projects.submitted') }}</div>
                      <div class="text-[10px] text-green-600 font-bold uppercase tracking-widest">{{ $t('projects.updatedAt') }}：{{ formatDate(tmplStatus[item.type]?.updatedAt) }}</div>
                    </div>
                  </div>
                  
                  <div class="flex flex-wrap gap-2 z-10">
                    <UButton size="sm" color="white" variant="solid" icon="i-heroicons-pencil-square" @click="enterOnlineForm(item.type)">{{ $t('common.edit') }}</UButton>
                    <UButton size="sm" color="green" variant="solid" icon="i-heroicons-paper-airplane" class="font-bold" @click="handleSubmit(item.type)" :loading="submitting">{{ $t('common.submit') }}</UButton>
                  </div>
                  
                  <!-- Watermark -->
                  <UIcon name="i-heroicons-shield-check" class="absolute -right-8 -bottom-8 w-40 h-40 text-green-200/20 rotate-12" />
                </div>

                <!-- Smelter Preview Section -->
                <div>
                  <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                      <UIcon name="i-heroicons-table-cells" class="text-primary-500 w-5 h-5" />
                      <h4 class="font-bold text-gray-900">{{ $t('conflict.smelterListPreview') || '冶煉廠/加工廠清單預覽' }}</h4>
                    </div>
                    <span class="text-xs font-bold text-gray-400">{{ tmplStatus[item.type]?.smelters?.length || 0 }} 筆資料</span>
                  </div>
                  
                  <div class="border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
                    <UTable
                      :columns="smelterColumns"
                      :rows="tmplStatus[item.type]?.smelters || []"
                      :ui="{ thead: 'bg-gray-50/50', td: { base: 'py-3' } }"
                    >
                      <template #rmi_conformant-data="{ row }">
                        <UBadge 
                          :color="row.rmi_conformant ? 'green' : 'gray'" 
                          size="xs" 
                          variant="soft" 
                          class="font-bold rounded-md"
                        >
                          <UIcon v-if="row.rmi_conformant" name="i-heroicons-shield-check" class="w-3 h-3 mr-1" />
                          {{ row.rmi_conformant ? 'Conformant' : 'Not Validated' }}
                        </UBadge>
                      </template>
                      <template #metal_type-data="{ row }">
                        <span class="text-xs font-bold text-gray-600">{{ row.metal_type }}</span>
                      </template>
                    </UTable>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </UTabs>
      </UCard>
    </div>

    <!-- Upload Modal -->
    <UModal v-model="uploadModal.isOpen">
      <UCard :ui="{ ring: '', divide: 'divide-y divide-gray-100 dark:divide-gray-800' }">
        <template #header>
          <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white">
              上傳 {{ uploadModal.type }} Excel 檔案
            </h3>
            <UButton color="gray" variant="ghost" icon="i-heroicons-x-mark-20-solid" class="-my-1" @click="uploadModal.isOpen = false" />
          </div>
        </template>

        <div class="p-4 space-y-4">
          <p class="text-sm text-gray-500">
            請選擇填寫完成的 {{ uploadModal.type }} 官方 Excel 檔案。系統將自動解析其中的聲明與冶煉廠清單。
          </p>
          
          <div
            class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-primary-500 transition-colors cursor-pointer"
            @click="fileInput?.click()"
          >
            <div class="space-y-1 text-center">
              <UIcon name="i-heroicons-document-arrow-up" class="mx-auto h-12 w-12 text-gray-400" />
              <div class="flex text-sm text-gray-600">
                <span class="relative cursor-pointer bg-white rounded-md font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500">
                  {{ selectedFile ? selectedFile.name : '點擊或拖放檔案至此' }}
                </span>
              </div>
              <p class="text-xs text-gray-500">XLS, XLSX (最大 10MB)</p>
            </div>
          </div>
          <input
            ref="fileInput"
            type="file"
            class="hidden"
            accept=".xls,.xlsx"
            @change="handleFileChange"
          />
        </div>

        <template #footer>
          <div class="flex justify-end gap-3">
            <UButton color="gray" variant="ghost" @click="uploadModal.isOpen = false">取消</UButton>
            <UButton
              color="primary"
              :disabled="!selectedFile"
              :loading="isUploading"
              @click="handleUpload"
            >
              開始匯入
            </UButton>
          </div>
        </template>
      </UCard>
    </UModal>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue'
import { useResponsibleMinerals } from '~/composables/useResponsibleMinerals'
import { useSweetAlert } from '~/composables/useSweetAlert'
import { useAuthStore } from '~/stores/auth'
import RMOnlineForm from './RMOnlineForm.vue'

const props = defineProps<{
  id: string | number
}>()

const emit = defineEmits(['back', 'submitted'])

const { getAssignmentInfo, importQuestionnaire, submitQuestionnaire, fetchReviewHistory } = useResponsibleMinerals()
const { showSuccess, showError, showConfirm } = useSweetAlert()

const assignment = ref<any>(null)
const answers = ref<any[]>([])
const reviewHistory = ref<any[]>([])
const loading = ref(true)
const submitting = ref(false)

const onlineFormMode = reactive({
  isEditing: false,
  type: ''
})

const currentOnlineData = ref<any>(null)

const tmplStatus = reactive<any>({
  CMRT: { completed: false, smelters: [], updatedAt: '', declaration: {}, mineralDeclaration: {} },
  EMRT: { completed: false, smelters: [], updatedAt: '', declaration: {}, mineralDeclaration: {} },
  AMRT: { completed: false, smelters: [], updatedAt: '', declaration: {}, mineralDeclaration: {} }
})

const init = async () => {
  loading.value = true
  try {
    const data = await getAssignmentInfo(Number(props.id))
    assignment.value = data.assignment
    answers.value = data.answers
    
    // Fetch review history if status is in_progress (might be returned)
    if (assignment.value.status === 'in_progress') {
      reviewHistory.value = await fetchReviewHistory(Number(props.id))
    }

    // Reset status
    tmplStatus.CMRT.completed = false
    tmplStatus.EMRT.completed = false
    tmplStatus.AMRT.completed = false

    // Map answers to status
    answers.value.forEach(ans => {
      if (tmplStatus[ans.template_type]) {
        tmplStatus[ans.template_type].completed = ans.status !== 'draft'
        tmplStatus[ans.template_type].updatedAt = ans.updated_at
        tmplStatus[ans.template_type].smelters = ans.smelters || []
        tmplStatus[ans.template_type].declaration = {
          companyName: ans.company_name,
          declarationScope: ans.declaration_scope,
          companyCountry: ans.company_country || '' // 後端可能需要補齊此欄位
        }
        try {
          tmplStatus[ans.template_type].mineralDeclaration = JSON.parse(ans.mineral_declaration || '{}')
        } catch {
          tmplStatus[ans.template_type].mineralDeclaration = {}
        }
      }
    })
  } catch (err: any) {
    showError(err.message || '無法載入問卷資訊')
  } finally {
    loading.value = false
  }
}

const enterOnlineForm = (type: string) => {
  const status = tmplStatus[type]
  currentOnlineData.value = {
    declaration: status.declaration,
    mineralDeclaration: status.mineralDeclaration,
    smelters: status.smelters
  }
  onlineFormMode.type = type
  onlineFormMode.isEditing = true
}

const onOnlineFormSaved = async () => {
  await init()
}

const onOnlineFormSubmitted = async () => {
  onlineFormMode.isEditing = false
  await init()
  if (assignment.value.status === 'reviewing' || assignment.value.status === 'submitted') {
    emit('submitted')
  }
}

const onTabChange = (index: number) => {
  // 可以根據需求處理標籤切換
}

const handleDownloadTemplate = async (type: string) => {
  // 從後端下載官方範本檔案
  const config = useRuntimeConfig()
  const authStore = useAuthStore()
  const apiBase = config.public.apiBase || 'http://localhost:9104/api/v1'
  
  const templateFiles: Record<string, string> = {
    CMRT: '/rm/templates/download/cmrt',
    EMRT: '/rm/templates/download/emrt',
    AMRT: '/rm/templates/download/amrt'
  }
  
  const apiPath = templateFiles[type]
  if (!apiPath) {
    showError('無法取得範本下載連結')
    return
  }

  try {
    const downloadUrl = `${apiBase}${apiPath}`
    
    // 使用 fetch 帶上認證 token 下載檔案
    const response = await fetch(downloadUrl, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${authStore.token}`
      }
    })

    if (!response.ok) {
      throw new Error('下載失敗')
    }

    // 獲取 blob 並創建下載連結
    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `${type}_Template_${new Date().toISOString().split('T')[0]}.xlsx`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    
    showSuccess(`${type} 範本下載成功`)
  } catch (error) {
    showError('範本下載失敗，請稍後再試')
  }
}

const assignedTemplates = computed(() => {
  if (!assignment.value) return []
  const list = []
  if (assignment.value.cmrt_required) list.push({ type: 'CMRT', completed: tmplStatus.CMRT.completed, version: '6.4' })
  if (assignment.value.emrt_required) list.push({ type: 'EMRT', completed: tmplStatus.EMRT.completed, version: '1.2' })
  if (assignment.value.amrt_required) list.push({ type: 'AMRT', completed: tmplStatus.AMRT.completed, version: '2.1' })
  return list
})

const tabItems = computed(() => {
  return assignedTemplates.value.map(t => ({
    label: t.type,
    type: t.type,
    completed: t.completed
  }))
})

const latestReturnLog = computed(() => {
  if (!reviewHistory.value.length) return null
  return reviewHistory.value.find(log => log.action === 'Returned')
})

const statusText = computed(() => {
  const map: any = {
    'not_assigned': '未開始',
    'assigned': '待填寫',
    'in_progress': '進行中',
    'submitted': '已核准',
    'reviewing': '審核中',
    'completed': '已完成'
  }
  return map[assignment.value?.status] || assignment.value?.status || '-'
})

const statusColor = computed(() => {
  const map: any = {
    'submitted': 'green',
    'reviewing': 'orange',
    'in_progress': 'blue',
    'completed': 'green'
  }
  return map[assignment.value?.status] || 'gray'
})

const smelterColumns = [
  { key: 'metal_type', label: '金屬類型' },
  { key: 'smelter_id', label: '冶煉廠 ID' },
  { key: 'smelter_name', label: '名稱' },
  { key: 'smelter_country', label: '國家' },
  { key: 'rmi_conformant', label: '驗證狀態' }
]

// Upload handling
const uploadModal = reactive({
  isOpen: false,
  type: ''
})
const fileInput = ref<HTMLInputElement | null>(null)
const selectedFile = ref<File | null>(null)
const isUploading = ref(false)

const openUploadModal = (type: string) => {
  uploadModal.type = type
  uploadModal.isOpen = true
  selectedFile.value = null
}

const handleFileChange = (e: any) => {
  selectedFile.value = e.target.files[0]
}

const handleUpload = async () => {
  if (!selectedFile.value) return
  isUploading.value = true
  try {
    await importQuestionnaire(Number(props.id), selectedFile.value)
    showSuccess('匯入完成')
    uploadModal.isOpen = false
    await init()
  } catch (err: any) {
    showError(err.message || '匯入失敗')
  } finally {
    isUploading.value = false
  }
}

const handleSubmit = async (type: string) => {
  const confirmed = await showConfirm({
    text: `確定要提交 ${type} 嗎？提交後將無法修改。`,
    icon: 'question',
    confirmButtonText: '確定提交'
  })
  if (!confirmed) return

  submitting.value = true
  try {
    await submitQuestionnaire(Number(props.id), type)
    showSuccess(`${type} 提交成功`)
    await init()
    
    // Check if all submitted
    if (assignment.value.status === 'submitted' || assignment.value.status === 'completed') {
      emit('submitted')
    }
  } catch (err: any) {
    showError(err.message || '提交失敗')
  } finally {
    submitting.value = false
  }
}

const formatDate = (dateString: string): string => {
  if (!dateString || dateString === '-') return '-'
  
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('zh-TW', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch {
    return dateString
  }
}

onMounted(() => {
  init()
})
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.4s ease-out;
}

.animate-slide-up {
  animation: slideUp 0.5s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.slide-down-enter-active, .slide-down-leave-active {
  transition: all 0.3s ease;
}
.slide-down-enter-from, .slide-down-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}
</style>
