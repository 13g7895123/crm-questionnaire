<template>
  <div class="project-overview space-y-6">
    <!-- 核心統計數據 -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <UCard
        v-for="stat in quickStats"
        :key="stat.label"
        :ui="{ 
          base: 'overflow-hidden transition-all duration-300 hover:shadow-lg hover:-translate-y-1',
          body: { padding: 'p-5' }
        }"
      >
        <div class="flex items-center gap-4">
          <div :class="['p-3 rounded-xl bg-opacity-10', stat.colorClass.replace('text', 'bg')]">
            <UIcon :name="stat.icon" :class="['w-6 h-6', stat.colorClass]" />
          </div>
          <div>
            <div class="text-sm font-medium text-gray-500">{{ stat.label }}</div>
            <div class="text-2xl font-bold text-gray-900 tracking-tight">
              {{ stat.value }}<span v-if="stat.suffix" class="text-sm font-normal text-gray-400 ml-1">{{ stat.suffix }}</span>
            </div>
          </div>
        </div>
      </UCard>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- 基本資訊明細 -->
      <UCard class="lg:col-span-1 shadow-sm">
        <template #header>
          <div class="flex items-center gap-2">
            <UIcon name="i-heroicons-information-circle" class="w-5 h-5 text-primary-500" />
            <h3 class="font-bold text-gray-900">{{ $t('projects.basicInfo') }}</h3>
          </div>
        </template>
        
        <div class="space-y-4">
          <div v-for="info in basicInfoItems" :key="info.label" class="flex flex-col border-b border-gray-50 pb-3 last:border-0 last:pb-0">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ info.label }}</span>
            <div v-if="info.type === 'badge'" class="mt-1">
              <UBadge :color="getStatusColor(project.status)" variant="soft" size="md">
                {{ getStatusLabel(project.status) }}
              </UBadge>
            </div>
            <span v-else class="text-sm font-medium text-gray-700 mt-0.5 truncate">{{ info.value }}</span>
          </div>
        </div>
      </UCard>

      <!-- 審核階段可視化 -->
      <UCard class="lg:col-span-2 shadow-sm">
        <template #header>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <UIcon name="i-heroicons-shield-check" class="w-5 h-5 text-green-500" />
              <h3 class="font-bold text-gray-900">{{ $t('review.reviewFlow') }}</h3>
            </div>
            <UBadge v-if="project.reviewConfig?.length" size="xs" variant="soft" color="primary">
              {{ project.reviewConfig.length }} {{ $t('review.stage') }}
            </UBadge>
          </div>
        </template>
        
        <div v-if="project.reviewConfig?.length" class="relative py-4">
          <!-- 背景連接線 (Desktop) -->
          <div class="hidden md:block absolute top-[2.25rem] left-8 right-8 h-0.5 bg-gray-100 -z-0"></div>
          
          <div class="flex flex-col md:flex-row items-center md:items-start justify-between gap-8 md:gap-4">
            <div
              v-for="(stage, index) in project.reviewConfig"
              :key="index"
              class="relative flex flex-col items-center flex-1 max-w-[200px]"
            >
              <div 
                class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm z-10 transition-colors shadow-sm"
                :class="[
                  index + 1 <= (project.currentStage || 1) 
                  ? 'bg-primary-500 text-white shadow-primary-200' 
                  : 'bg-white border-2 border-gray-200 text-gray-400'
                ]"
              >
                {{ index + 1 }}
              </div>
              <div class="mt-3 text-center">
                <p class="text-xs font-bold text-gray-400 uppercase">{{ $t('review.stage') }} {{ index + 1 }}</p>
                <p class="text-sm font-semibold text-gray-800 mt-0.5">{{ stage.department?.name || `Dept ${stage.departmentId}` }}</p>
              </div>
              
              <!-- 箭頭 (Mobile only) -->
              <UIcon 
                v-if="index < project.reviewConfig.length - 1"
                name="i-heroicons-chevron-down"
                class="md:hidden w-5 h-5 text-gray-300 mt-2"
              />
            </div>
          </div>
        </div>
        <div v-else class="flex flex-col items-center justify-center py-8 text-gray-400 bg-gray-50 rounded-lg border border-dashed">
          <UIcon name="i-heroicons-no-symbol" class="w-8 h-8 mb-2" />
          <p class="text-sm italic">未設定審核流程</p>
        </div>
      </UCard>
    </div>

    <!-- 快速操作區 -->
    <UCard :ui="{ body: { padding: 'p-4' } }" class="bg-gray-50/50 border-gray-100 shadow-none">
      <div class="flex flex-wrap items-center justify-center sm:justify-between gap-4">
        <div class="flex items-center gap-2">
          <UIcon name="i-heroicons-bolt text-yellow-500" class="w-5 h-5" />
          <span class="text-sm font-bold text-gray-700">{{ $t('common.action') }}</span>
        </div>
        
        <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
          <UButton
            color="primary"
            variant="ghost"
            icon="i-heroicons-envelope"
            class="hidden sm:inline-flex"
            @click="handleNotifyAll"
          >
            發送通知
          </UButton>
          
          <UDropdown
            :items="exportOptions"
            :popper="{ placement: 'bottom-end' }"
          >
            <UButton
              color="white"
              variant="solid"
              icon="i-heroicons-arrow-down-tray"
              trailing-icon="i-heroicons-chevron-down"
              class="border-gray-200"
            >
              {{ $t('common.export') }}
            </UButton>
          </UDropdown>
          
          <UButton
            color="white"
            icon="i-heroicons-pencil-square"
            class="border-gray-200"
            @click="$emit('edit')"
          >
            {{ $t('common.edit') }}
          </UButton>
        </div>
      </div>
    </UCard>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import type { Project } from '~/types/index'
import { useResponsibleMinerals } from '~/composables/useResponsibleMinerals'
import { useSweetAlert } from '~/composables/useSweetAlert'
import { useI18n } from 'vue-i18n'

interface Props {
  project: Project
}

const props = defineProps<Props>()
const emit = defineEmits(['edit'])

const { fetchProgress, progressData, exportProgress, exportConsolidatedReport } = useResponsibleMinerals()
const { showSuccess, showError, showLoading, closeAlert } = useSweetAlert()
const { t } = useI18n()

// 統計數據配置
const supplierCount = computed(() => progressData.value?.summary?.totalSuppliers || props.project.suppliers?.length || 0)
const assignedCount = computed(() => progressData.value?.summary?.assignedSuppliers || 0)
const completedCount = computed(() => progressData.value?.summary?.completedSuppliers || 0)
const completionRate = computed(() => {
  if (supplierCount.value === 0) return 0
  return Math.round((completedCount.value / supplierCount.value) * 100)
})

const quickStats = computed(() => [
  {
    label: '供應商總數',
    value: supplierCount.value,
    icon: 'i-heroicons-user-group',
    colorClass: 'text-indigo-600',
    suffix: ''
  },
  {
    label: '已指派範本',
    value: assignedCount.value,
    icon: 'i-heroicons-clipboard-document-check',
    colorClass: 'text-primary-600',
    suffix: ''
  },
  {
    label: '已完成填寫',
    value: completedCount.value,
    icon: 'i-heroicons-check-circle',
    colorClass: 'text-green-600',
    suffix: ''
  },
  {
    label: '完成度',
    value: completionRate.value,
    icon: 'i-heroicons-chart-pie',
    colorClass: 'text-orange-500',
    suffix: '%'
  }
])

const basicInfoItems = computed(() => [
  { label: t('projects.projectName'), value: props.project.name, type: 'text' },
  { label: t('projects.projectYear'), value: props.project.year, type: 'text' },
  { label: t('projects.projectType'), value: props.project.type, type: 'text' },
  { label: t('projects.status'), value: props.project.status, type: 'badge' },
  { label: t('projects.createdAt'), value: formatDate(props.project.createdAt), type: 'text' },
  { label: t('projects.updatedAt'), value: formatDate(props.project.updatedAt), type: 'text' }
])

const getStatusLabel = (status?: string) => {
  const statusMap: Record<string, string> = {
    DRAFT: t('projects.draft'),
    IN_PROGRESS: t('projects.inProgress'),
    COMPLETED: t('projects.submitted'),
    ARCHIVED: '已封存'
  }
  return statusMap[status || ''] || status || '未知'
}

const getStatusColor = (status?: string) => {
  const colorMap: Record<string, string> = {
    DRAFT: 'gray',
    IN_PROGRESS: 'blue',
    COMPLETED: 'green',
    ARCHIVED: 'gray'
  }
  return colorMap[status || ''] || 'gray'
}

const formatDate = (dateStr?: string) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('zh-TW', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const handleNotifyAll = () => {
  showSuccess('功能開發中：將發送 Email 通知所有未完成的供應商')
}

const handleExportProgress = async () => {
  showLoading('報表產出中...')
  try {
    await exportProgress(Number(props.project.id))
    closeAlert()
  } catch (err: any) {
    showError(err.message || '匯出失敗')
  }
}

const handleExportConsolidated = async () => {
  showLoading('報表產出中...')
  try {
    await exportConsolidatedReport(Number(props.project.id))
    closeAlert()
  } catch (err: any) {
    showError(err.message || '匯出失敗')
  }
}

const exportOptions = [
  [
    {
      label: t('conflict.progressReport'),
      icon: 'i-heroicons-list-bullet',
      click: handleExportProgress
    },
    {
      label: t('conflict.consolidatedReport'),
      icon: 'i-heroicons-table-cells',
      click: handleExportConsolidated
    }
  ]
]

onMounted(async () => {
  if (props.project.id) {
    await fetchProgress(Number(props.project.id))
  }
})
</script>

<style scoped>
/* 保持乾淨，主要利用 TailwindCSS */
.tracking-tight {
  letter-spacing: -0.025em;
}
</style>
