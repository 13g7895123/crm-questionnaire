<template>
  <div class="rm-progress-page space-y-8">
    <!-- 頁面標頭 (若非嵌入式則顯示) -->
    <div v-if="!projectIdFromProps" class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $t('projects.progress') }}</h1>
        <p class="text-sm text-gray-500 mt-1">查看各供應商的範本指派與填寫狀況</p>
      </div>
      <div class="flex gap-2">
        <UButton
          color="white"
          variant="solid"
          icon="i-heroicons-arrow-path"
          :loading="loading"
          @click="loadProgress"
        >
          重新整理
        </UButton>
        <UButton
          color="primary"
          icon="i-heroicons-arrow-down-tray"
          @click="handleExport"
        >
          {{ $t('common.exportExcel') }}
        </UButton>
      </div>
    </div>

    <!-- 骨架屏或載入狀態 -->
    <div v-if="loading && !progressData" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <USkeleton v-for="i in 6" :key="i" class="h-32 w-full rounded-xl" />
    </div>

    <div v-else-if="error" class="flex flex-col items-center justify-center py-20 bg-red-50 rounded-2xl border border-red-100">
      <UIcon name="i-heroicons-exclamation-triangle" class="w-12 h-12 text-red-500 mb-4" />
      <p class="text-red-700 font-bold text-lg mb-6">{{ error }}</p>
      <UButton color="red" variant="soft" icon="i-heroicons-arrow-path" @click="loadProgress">重試</UButton>
    </div>

    <div v-else class="space-y-10 animate-fade-in">
      <!-- 1. 核心指標摘要 (Summary) -->
      <section>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          <UCard
            v-for="stat in summaryStats"
            :key="stat.key"
            :ui="{ 
              base: 'group overflow-hidden border-none shadow-sm', 
              body: { padding: 'p-4' },
              divide: '',
              ring: 'ring-1 ring-gray-100 hover:ring-primary-400 transition-all duration-300'
            }"
            v-show="stat.show"
          >
            <div class="flex flex-col items-center text-center space-y-2">
              <div :class="['p-2 rounded-lg bg-opacity-10 group-hover:scale-110 transition-transform duration-300', stat.colorClass.replace('text', 'bg')]">
                <UIcon :name="stat.icon" :class="['w-5 h-5', stat.colorClass]" />
              </div>
              <div>
                <div class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ stat.label }}</div>
                <div class="text-2xl font-black text-gray-900 mt-0.5 leading-none">{{ stat.value }}</div>
              </div>
            </div>
          </UCard>
        </div>
      </section>

      <!-- 2. 合規統計專區 (Analytics) -->
      <section class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <!-- 冶煉廠合規率 -->
        <UCard :ui="{ base: 'shadow-md border-t-4 border-t-green-500' }">
          <template #header>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <UIcon name="i-heroicons-presentation-chart-line" class="text-green-500 w-6 h-6" />
                <h2 class="text-lg font-bold text-gray-900">{{ $t('conflict.smelterAnalytics') }}</h2>
              </div>
              <UBadge color="green" variant="soft" size="md">RMI Conformant</UBadge>
            </div>
          </template>
          
          <div class="space-y-6">
            <div class="flex flex-col md:flex-row items-baseline md:items-center justify-between gap-4">
              <div>
                <p class="text-sm font-medium text-gray-500">{{ $t('conflict.conformantRate') }}</p>
                <div class="flex items-baseline gap-2 mt-1">
                  <span class="text-4xl font-black text-gray-900 tracking-tighter">{{ progressData?.smelterStats?.percentage || 0 }}%</span>
                  <span class="text-xs text-gray-400 font-bold">Conformant / Total Reported</span>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-x-8 gap-y-1">
                <div class="flex flex-col">
                  <span class="text-xs font-bold text-gray-400 uppercase">{{ $t('conflict.smelterCount') }}</span>
                  <span class="text-lg font-bold text-gray-800">{{ progressData?.smelterStats?.total || 0 }}</span>
                </div>
                <div class="flex flex-col">
                  <span class="text-xs font-bold text-gray-400 uppercase">{{ $t('conflict.rmiConformant') }}</span>
                  <span class="text-lg font-bold text-green-600">{{ progressData?.smelterStats?.conformant || 0 }}</span>
                </div>
              </div>
            </div>

            <div class="relative pt-1">
              <div class="overflow-hidden h-4 text-xs flex rounded-full bg-gray-100 shadow-inner">
                <div
                  :style="{ width: `${progressData?.smelterStats?.percentage || 0}%` }"
                  class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-gradient-to-r from-green-500 to-emerald-400 transition-all duration-1000"
                ></div>
              </div>
              <div class="flex justify-between text-xs mt-1 font-bold text-gray-300">
                <span>0%</span>
                <span>100%</span>
              </div>
            </div>
          </div>
        </UCard>

        <!-- 範本填寫狀況 -->
        <UCard :ui="{ base: 'shadow-md border-t-4 border-t-primary-500' }">
          <template #header>
            <div class="flex items-center gap-2">
              <UIcon name="i-heroicons-document-check" class="text-primary-500 w-6 h-6" />
              <h2 class="text-lg font-bold text-gray-900">範本填寫狀況</h2>
            </div>
          </template>
          
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div v-for="(stats, type) in progressData?.templateStats" :key="type" class="space-y-3">
              <div class="flex justify-between items-end">
                <div>
                  <h4 class="font-bold text-gray-900">{{ type }}</h4>
                  <p class="text-xs text-gray-400 font-semibold">{{ getTemplateDesc(type) }}</p>
                </div>
                <span class="text-sm font-black text-primary-600">{{ stats.percentage }}%</span>
              </div>
              <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                <div 
                  class="h-full bg-primary-500 transition-all duration-700"
                  :class="getTemplateColor(type)"
                  :style="{ width: `${stats.percentage}%` }"
                ></div>
              </div>
              <div class="flex justify-between items-center text-xs font-bold text-gray-500">
                <span>{{ stats.completed }} / {{ stats.total }}</span>
                <span v-if="stats.completed === stats.total" class="text-green-500 flex items-center gap-0.5">
                  <UIcon name="i-heroicons-check-circle" class="w-3 h-3" />
                  已達成
                </span>
              </div>
            </div>
          </div>
        </UCard>
      </section>

      <!-- 3. 詳細清單與篩選 (List) -->
      <section class="space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-4 rounded-xl shadow-sm border border-gray-100">
          <div class="flex flex-wrap items-center gap-3">
            <UInput
              v-model="searchQuery"
              icon="i-heroicons-magnifying-glass"
              size="md"
              color="white"
              :placeholder="$t('common.search')"
              class="w-full sm:w-64"
            />
            
            <USelect
              v-model="templateFilter"
              :options="templateFilterOptions"
              size="md"
              class="w-full sm:w-40"
            />

            <USelect
              v-model="statusFilter"
              :options="statusFilterOptions"
              size="md"
              class="w-full sm:w-40"
            />
          </div>
          
          <div class="text-sm text-gray-400 font-medium">
            共 <span class="text-gray-900 font-bold mx-0.5">{{ filteredSuppliers.length }}</span> 筆結果
          </div>
        </div>

        <div class="overflow-hidden border border-gray-100 rounded-xl shadow-sm bg-white">
          <UTable
            :columns="supplierColumns"
            :rows="filteredSuppliers"
            :ui="{
              thead: 'bg-gray-50/50',
              th: { base: 'text-[11px] font-bold text-gray-400 uppercase tracking-wider' },
              td: { base: 'py-4' }
            }"
          >
            <!-- 供應商名稱 -->
            <template #supplier_name-data="{ row }">
              <div class="flex flex-col">
                <span class="font-bold text-gray-900">{{ row.supplier_name }}</span>
                <span class="text-xs text-gray-400 font-medium">ID: {{ row.supplier_id }}</span>
              </div>
            </template>

            <!-- 指派範本 -->
            <template #assignedTemplates-data="{ row }">
              <div class="flex flex-wrap gap-1">
                <UBadge
                  v-for="tpl in row.assignedTemplates"
                  :key="tpl"
                  size="xs"
                  :color="getTemplateBadgeColor(tpl)"
                  variant="soft"
                  class="font-bold border border-current border-opacity-10"
                >
                  {{ tpl }}
                </UBadge>
                <span v-if="row.assignedTemplates.length === 0" class="text-xs text-gray-300 italic">無</span>
              </div>
            </template>

            <!-- 狀態 -->
            <template #status-data="{ row }">
              <UBadge
                :color="getStatusColor(row.status)"
                variant="soft"
                class="px-2.5 py-0.5 rounded-md text-xs font-bold"
              >
                {{ row.status }}
              </UBadge>
            </template>

            <!-- 完成率 -->
            <template #completionRate-data="{ row }">
              <div class="flex items-center gap-3 w-32">
                <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                  <div
                    class="h-full bg-green-500 rounded-full transition-all duration-300"
                    :style="{ width: `${row.completionRate}%` }"
                  ></div>
                </div>
                <span class="text-xs font-bold text-gray-700 min-w-[32px]">{{ row.completionRate }}%</span>
              </div>
            </template>

            <!-- 最後更新 -->
            <template #lastUpdated-data="{ row }">
              <span class="text-xs text-gray-500 font-medium whitespace-nowrap">
                {{ formatDate(row.lastUpdated) }}
              </span>
            </template>
          </UTable>

          <div v-if="filteredSuppliers.length === 0" class="flex flex-col items-center justify-center py-20 bg-gray-50/30">
            <UIcon name="i-heroicons-face-frown" class="w-10 h-10 text-gray-300 mb-2" />
            <p class="text-sm font-bold text-gray-400">{{ $t('common.noData') }}</p>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useResponsibleMinerals } from '~/composables/useResponsibleMinerals'
import { useSweetAlert } from '~/composables/useSweetAlert'
import { useI18n } from '#imports'

const props = defineProps<{
  projectId?: number | string
}>()

const route = useRoute()
const { showSuccess, showError } = useSweetAlert()
const { t } = useI18n()
const {
  progressData,
  fetchProgress,
  exportProgress
} = useResponsibleMinerals()

const projectIdFromProps = computed(() => props.projectId)
const projectIdFromRoute = computed(() => Number(route.params.id))
const projectId = computed(() => Number(projectIdFromProps.value || projectIdFromRoute.value))

// 狀態
const loading = ref(false)
const error = ref('')
const searchQuery = ref('')
const templateFilter = ref('all')
const statusFilter = ref('all')

// 摘要統計配置
const summaryStats = computed(() => [
  { key: 'total', label: '總供應商數', value: progressData.value?.summary.totalSuppliers || 0, icon: 'i-heroicons-users', colorClass: 'text-gray-600', show: true },
  { key: 'assigned', label: '已指派範本', value: progressData.value?.summary.assignedSuppliers || 0, icon: 'i-heroicons-clipboard-document-check', colorClass: 'text-primary-600', show: true },
  { key: 'not_assigned', label: '未指派範本', value: progressData.value?.summary.notAssignedSuppliers || 0, icon: 'i-heroicons-exclamation-circle', colorClass: 'text-red-500', show: true },
  { key: 'completed', label: '已完成填寫', value: progressData.value?.summary.completedSuppliers || 0, icon: 'i-heroicons-check-circle', colorClass: 'text-green-600', show: true },
  { key: 'in_progress', label: '進行中', value: progressData.value?.summary.inProgressSuppliers || 0, icon: 'i-heroicons-pencil-square', colorClass: 'text-orange-500', show: true },
  { key: 'not_started', label: '未開始', value: progressData.value?.summary.notStartedSuppliers || 0, icon: 'i-heroicons-pause-circle', colorClass: 'text-gray-400', show: true }
])

const templateFilterOptions = [
  { label: '全部範本', value: 'all' },
  { label: 'CMRT', value: 'CMRT' },
  { label: 'EMRT', value: 'EMRT' },
  { label: 'AMRT', value: 'AMRT' }
]

const statusFilterOptions = [
  { label: '全部狀態', value: 'all' },
  { label: '已完成', value: 'completed' },
  { label: '進行中', value: 'in_progress' },
  { label: '未開始', value: 'not_started' },
  { label: '未指派', value: 'not_assigned' }
]

const supplierColumns = [
  { key: 'supplier_name', label: '供應商名稱', sortable: true },
  { key: 'assignedTemplates', label: '指派範本' },
  { key: 'status', label: '狀態' },
  { key: 'completionRate', label: '完成度', sortable: true },
  { key: 'lastUpdated', label: '最後更新' }
]

// 計算屬性
const filteredSuppliers = computed(() => {
  if (!progressData.value) return []
  
  let result = progressData.value.suppliers

  // 搜尋過濾
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(s =>
      s.supplier_name.toLowerCase().includes(query)
    )
  }

  // 範本類型過濾
  if (templateFilter.value && templateFilter.value !== 'all') {
    result = result.filter(s =>
      s.assignedTemplates.includes(templateFilter.value)
    )
  }

  // 狀態過濾
  if (statusFilter.value && statusFilter.value !== 'all') {
    if (statusFilter.value === 'not_assigned') {
      result = result.filter(s => s.assignedTemplates.length === 0)
    } else {
      result = result.filter(s => getStatusClass(s.status) === statusFilter.value)
    }
  }

  return result
})

// 方法
const loadProgress = async () => {
  loading.value = true
  error.value = ''
  try {
    await fetchProgress(projectId.value)
  } catch (err: any) {
    error.value = err.message || '載入失敗'
  } finally {
    loading.value = false
  }
}

const handleExport = async () => {
  try {
    await exportProgress(projectId.value)
    showSuccess('報表匯出成功')
  } catch (err: any) {
    showError(err.message || '匯出失敗')
  }
}

const getStatusClass = (status: string): string => {
  const statusMap: Record<string, string> = {
    '已提交': 'completed',
    '進行中': 'in_progress',
    '未開始': 'not_started',
    '審核中': 'in_progress',
    '已核准': 'completed',
    '-': 'not_assigned'
  }
  return statusMap[status] || status
}

const getStatusColor = (status: string) => {
  const s = getStatusClass(status)
  if (s === 'completed') return 'green'
  if (s === 'in_progress') return 'orange'
  if (s === 'not_started') return 'red'
  return 'gray'
}

const getTemplateDesc = (type: string | number) => {
  const map: Record<string, string> = {
    CMRT: 'Conflict Minerals (3TG)',
    EMRT: 'Extended Minerals',
    AMRT: 'Additional Minerals'
  }
  return map[String(type)] || ''
}

const getTemplateColor = (type: string | number) => {
  const map: Record<string, string> = {
    CMRT: 'bg-blue-500',
    EMRT: 'bg-green-500',
    AMRT: 'bg-yellow-500'
  }
  return map[String(type)] || 'bg-primary-500'
}

const getTemplateBadgeColor = (type: string) => {
  const map: Record<string, string> = {
    CMRT: 'blue',
    EMRT: 'green',
    AMRT: 'yellow'
  }
  return map[type] || 'primary'
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
  loadProgress()
})
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* 確保進度條在高負載下平滑 */
.transition-all {
  transition-duration: 500ms;
}
</style>
