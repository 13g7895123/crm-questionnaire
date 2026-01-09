<template>
  <div class="project-overview space-y-6">
    <!-- 基本資訊與統計 -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- 基本資訊卡片 -->
      <UCard>
        <template #header>
          <h3 class="font-semibold text-gray-900">基本資訊</h3>
        </template>
        
        <dl class="space-y-4">
          <div>
            <dt class="text-sm text-gray-500">專案名稱</dt>
            <dd class="font-medium text-gray-900">{{ project.name }}</dd>
          </div>
          <div>
            <dt class="text-sm text-gray-500">專案年份</dt>
            <dd class="font-medium text-gray-900">{{ project.year }}</dd>
          </div>
          <div>
            <dt class="text-sm text-gray-500">專案類型</dt>
            <dd class="font-medium text-gray-900">{{ project.type }}</dd>
          </div>
          <div>
            <dt class="text-sm text-gray-500">專案狀態</dt>
            <dd>
              <UBadge
                :color="getStatusColor(project.status)"
                variant="subtle"
              >
                {{ getStatusLabel(project.status) }}
              </UBadge>
            </dd>
          </div>
          <div>
            <dt class="text-sm text-gray-500">建立日期</dt>
            <dd class="text-sm text-gray-900">{{ formatDate(project.createdAt) }}</dd>
          </div>
          <div>
            <dt class="text-sm text-gray-500">最後更新</dt>
            <dd class="text-sm text-gray-900">{{ formatDate(project.updatedAt) }}</dd>
          </div>
        </dl>
      </UCard>

      <!-- 統計卡片 -->
      <UCard>
        <template #header>
          <h3 class="font-semibold text-gray-900">快速統計</h3>
        </template>
        
        <div class="grid grid-cols-2 gap-4">
          <div class="stat-item">
            <div class="text-2xl font-bold text-gray-900">{{ supplierCount }}</div>
            <div class="text-sm text-gray-500">供應商總數</div>
          </div>
          <div class="stat-item">
            <div class="text-2xl font-bold text-primary-600">{{ assignedCount }}</div>
            <div class="text-sm text-gray-500">已指派範本</div>
          </div>
          <div class="stat-item">
            <div class="text-2xl font-bold text-green-600">{{ completedCount }}</div>
            <div class="text-sm text-gray-500">已完成填寫</div>
          </div>
          <div class="stat-item">
            <div class="text-2xl font-bold text-blue-600">{{ completionRate }}%</div>
            <div class="text-sm text-gray-500">完成度</div>
          </div>
        </div>
      </UCard>
    </div>

    <!-- 審核流程 -->
    <UCard v-if="project.reviewConfig?.length">
      <template #header>
        <h3 class="font-semibold text-gray-900">審核流程</h3>
      </template>
      
      <div class="flex flex-col gap-4">
        <div
          v-for="(stage, index) in project.reviewConfig"
          :key="index"
          class="flex items-center gap-3"
        >
          <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center font-semibold text-sm">
            {{ stage.stageOrder || index + 1 }}
          </div>
          <div class="flex-1 px-4 py-2 rounded-lg border border-gray-200 bg-gray-50">
            <p class="text-xs text-gray-500">審核階段 {{ stage.stageOrder || index + 1 }}</p>
            <p class="font-medium text-gray-900">{{ stage.department?.name || `部門 ${stage.departmentId}` }}</p>
          </div>
          <UIcon 
            v-if="index < project.reviewConfig.length - 1"
            name="i-heroicons-arrow-right"
            class="w-5 h-5 text-gray-400"
          />
        </div>
      </div>
    </UCard>

    <!-- 快速操作 -->
    <UCard>
      <template #header>
        <h3 class="font-semibold text-gray-900">快速操作</h3>
      </template>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <UButton
          color="primary"
          variant="soft"
          block
          @click="handleNotifyAll"
        >
          <template #leading>
            <UIcon name="i-heroicons-envelope" />
          </template>
          發送通知
        </UButton>
        
        <UButton
          color="gray"
          variant="soft"
          block
          @click="handleExportReport"
        >
          <template #leading>
            <UIcon name="i-heroicons-arrow-down-tray" />
          </template>
          匯出報表
        </UButton>
        
        <UButton
          color="gray"
          variant="soft"
          block
          @click="$emit('edit')"
        >
          <template #leading>
            <UIcon name="i-heroicons-pencil-square" />
          </template>
          編輯專案
        </UButton>
      </div>
    </UCard>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Project } from '~/types/index'

interface Props {
  project: Project
}

const props = defineProps<Props>()
const emit = defineEmits(['edit'])

const supplierCount = computed(() => props.project.suppliers?.length || 0)
const assignedCount = computed(() => {
  // TODO: 從 API 取得已指派範本的供應商數量
  return 0
})
const completedCount = computed(() => {
  // TODO: 從 API 取得已完成填寫的供應商數量
  return 0
})
const completionRate = computed(() => {
  if (supplierCount.value === 0) return 0
  return Math.round((completedCount.value / supplierCount.value) * 100)
})

const getStatusLabel = (status?: string) => {
  const statusMap: Record<string, string> = {
    DRAFT: '草稿',
    IN_PROGRESS: '進行中',
    COMPLETED: '已完成',
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
  // TODO: 實作發送通知功能
  console.log('發送通知給所有供應商')
}

const handleExportReport = () => {
  // TODO: 實作匯出報表功能
  console.log('匯出專案報表')
}
</script>

<style scoped>
.project-overview {
  /* 移除原本的 padding，由父組件控制 */
}

.stat-item {
  padding: 12px;
  background: #f9fafb;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}
</style>
