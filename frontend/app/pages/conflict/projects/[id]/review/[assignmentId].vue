<template>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
      <div class="flex items-center gap-4">
        <UButton
          icon="i-heroicons-arrow-left"
          color="gray"
          variant="ghost"
          @click="router.back()"
        />
        <div>
          <h1 class="text-2xl font-bold text-gray-900">審核問卷：{{ assignment?.supplier_name }}</h1>
          <p class="text-sm text-gray-500 mt-1">專案：{{ assignment?.project?.name }}</p>
        </div>
      </div>
      <UBadge color="orange">等待審核</UBadge>
    </div>

    <div v-if="loading" class="flex flex-col items-center justify-center py-20">
      <UIcon name="i-heroicons-arrow-path" class="w-12 h-12 text-primary-500 animate-spin" />
      <p class="mt-4 text-gray-600">載入問卷資料中...</p>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Left: Parsed Data -->
      <div class="lg:col-span-2 space-y-6">
        <UCard v-for="answer in answers" :key="answer.id" class="overflow-hidden">
          <template #header>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <UIcon name="i-heroicons-document-text" class="w-5 h-5 text-primary-500" />
                <h3 class="font-bold text-lg">{{ answer.template_type }} 回答內容</h3>
              </div>
              <UBadge v-if="answer.status === 'approved'" color="green">已核准</UBadge>
            </div>
          </template>

          <!-- Company Declaration -->
          <div class="space-y-4">
            <h4 class="font-semibold text-gray-700 border-b pb-2">基本宣告</h4>
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="text-gray-500">公司名稱：</span>
                <span class="font-medium">{{ answer.company_name }}</span>
              </div>
              <div>
                <span class="text-gray-500">聲明範圍：</span>
                <span class="font-medium">{{ answer.declaration_scope }}</span>
              </div>
            </div>

            <!-- Mineral Declaration -->
            <h4 class="font-semibold text-gray-700 border-b pb-2 mt-6">礦產使用宣告</h4>
            <div class="flex flex-wrap gap-4">
              <div
                v-for="(info, mineral) in answer.mineral_declaration"
                :key="mineral"
                class="px-3 py-2 rounded-lg border flex items-center gap-2"
                :class="info.used === 'Yes' ? 'bg-orange-50 border-orange-200' : 'bg-gray-50 border-gray-200'"
              >
                <span class="text-xs font-bold">{{ mineral }}:</span>
                <span class="text-sm" :class="info.used === 'Yes' ? 'text-orange-700 font-bold' : 'text-gray-500'">{{ info.used }}</span>
              </div>
            </div>

            <!-- Smelter List -->
            <h4 class="font-semibold text-gray-700 border-b pb-2 mt-6">冶煉廠/加工廠清單 ({{ answer.smelters?.length || 0 }})</h4>
            <UTable
              :columns="smelterColumns"
              :rows="answer.smelters || []"
            >
              <template #rmi_conformant-data="{ row }">
                <UBadge :color="row.rmi_conformant ? 'green' : 'gray'" size="xs">
                  {{ row.rmi_conformant ? '符合' : '未驗證' }}
                </UBadge>
              </template>
            </UTable>

            <!-- Mine List (EMRT only) -->
            <div v-if="answer.template_type === 'EMRT' && answer.mines?.length">
              <h4 class="font-semibold text-gray-700 border-b pb-2 mt-6">礦場清單 ({{ answer.mines?.length || 0 }})</h4>
              <UTable
                :columns="mineColumns"
                :rows="answer.mines || []"
              />
            </div>
          </div>
        </UCard>
      </div>

      <!-- Right: Review Action & History -->
      <div class="space-y-6">
        <!-- Action Card -->
        <UCard>
          <template #header>
            <h3 class="font-bold text-gray-900">審核操作</h3>
          </template>

          <div class="space-y-4">
            <UFormGroup label="審核意見">
              <UTextarea
                v-model="reviewForm.comment"
                placeholder="請輸入核准或退回的理由..."
                rows="4"
              />
            </UFormGroup>

            <div class="grid grid-cols-2 gap-4">
              <UButton
                block
                color="red"
                variant="soft"
                icon="i-heroicons-arrow-uturn-left"
                :loading="submitting"
                @click="handleReview('return')"
              >
                退回修正
              </UButton>
              <UButton
                block
                color="green"
                variant="solid"
                icon="i-heroicons-check"
                :loading="submitting"
                @click="handleReview('approve')"
              >
                核准通過
              </UButton>
            </div>
          </div>
        </UCard>

        <!-- History Card -->
        <UCard>
          <template #header>
            <h3 class="font-bold text-gray-900">審核紀錄</h3>
          </template>

          <div class="space-y-4">
            <div
              v-for="log in history"
              :key="log.id"
              class="relative pl-6 pb-4 border-l border-gray-200 last:pb-0"
            >
              <div class="absolute left-[-5px] top-1 w-2.5 h-2.5 rounded-full bg-primary-500"></div>
              <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-gray-900">{{ log.reviewer_name }}</span>
                <UBadge :color="log.action === 'Approved' ? 'green' : 'red'" size="xs" variant="soft">
                  {{ log.action === 'Approved' ? '核准' : '退回' }}
                </UBadge>
              </div>
              <p class="text-xs text-gray-600 mb-1">{{ log.comment }}</p>
              <span class="text-[10px] text-gray-400">{{ log.created_at }}</span>
            </div>
            <div v-if="history.length === 0" class="text-center py-4 text-xs text-gray-400">
              尚無紀錄
            </div>
          </div>
        </UCard>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useResponsibleMinerals } from '~/composables/useResponsibleMinerals'
import { useSweetAlert } from '~/composables/useSweetAlert'

definePageMeta({ middleware: 'auth' })

const route = useRoute()
const router = useRouter()
const { getAssignmentInfo, reviewAssignment, fetchReviewHistory } = useResponsibleMinerals()
const { showSuccess, showError, showConfirm } = useSweetAlert()

const id = route.params.id // Project ID
const assignmentId = route.params.assignmentId // Assignment ID

const loading = ref(true)
const submitting = ref(false)
const assignment = ref<any>(null)
const answers = ref<any[]>([])
const history = ref<any[]>([])

const reviewForm = reactive({
  comment: ''
})

const smelterColumns = [
  { key: 'metal_type', label: '金屬' },
  { key: 'smelter_name', label: '名稱' },
  { key: 'smelter_country', label: '國家' },
  { key: 'rmi_conformant', label: '驗證' }
]

const mineColumns = [
  { key: 'metal_type', label: '金屬' },
  { key: 'mine_name', label: '礦場名稱' },
  { key: 'mine_country', label: '國家' },
  { key: 'mine_location', label: '位置' }
]

const init = async () => {
  loading.value = true
  try {
    const data = await getAssignmentInfo(Number(assignmentId))
    assignment.value = data.assignment
    answers.value = data.answers
    history.value = await fetchReviewHistory(Number(assignmentId))
  } catch (err: any) {
    showError(err.message || '無法載入審核資料')
  } finally {
    loading.value = false
  }
}

const handleReview = async (action: 'approve' | 'return') => {
  const actionText = action === 'approve' ? '核准' : '退回'
  const confirm = await showConfirm(`確定要${actionText}這筆問卷嗎？`)
  if (!confirm.isConfirmed) return

  submitting.value = true
  try {
    await reviewAssignment(Number(assignmentId), action, reviewForm.comment)
    showSuccess(`${actionText}成功`)
    router.push('/conflict/reviews/pending')
  } catch (err: any) {
    showError(err.message || '審核失敗')
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  init()
})
</script>
