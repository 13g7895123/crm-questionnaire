<template>
  <div class="review-management space-y-6">
    <!-- Header Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <UCard :ui="{ body: { padding: 'p-4' } }" class="bg-orange-50/50 border-orange-100">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-orange-100 rounded-lg">
            <UIcon name="i-heroicons-clipboard-document-list" class="w-5 h-5 text-orange-600" />
          </div>
          <div>
            <div class="text-xs text-gray-500 font-medium">待處理審核</div>
            <div class="text-xl font-bold text-orange-700">{{ pendingReviews.length }}</div>
          </div>
        </div>
      </UCard>
      
      <UCard :ui="{ body: { padding: 'p-4' } }" class="bg-green-50/50 border-green-100">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-green-100 rounded-lg">
            <UIcon name="i-heroicons-check-circle" class="w-5 h-5 text-green-600" />
          </div>
          <div>
            <div class="text-xs text-gray-500 font-medium">已完成審核</div>
            <div class="text-xl font-bold text-green-700">{{ completedReviews.length }}</div>
          </div>
        </div>
      </UCard>

      <UCard :ui="{ body: { padding: 'p-4' } }" class="bg-blue-50/50 border-blue-100">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-blue-100 rounded-lg">
            <UIcon name="i-heroicons-users" class="w-5 h-5 text-blue-600" />
          </div>
          <div>
            <div class="text-xs text-gray-500 font-medium">總提交比例</div>
            <div class="text-xl font-bold text-blue-700">{{ submissionRate }}%</div>
          </div>
        </div>
      </UCard>
    </div>

    <!-- Main Content Tabs -->
    <UCard>
      <template #header>
        <div class="flex items-center justify-between">
          <UTabs v-model="selectedTabIndex" :items="tabItems" @change="onTabChange" />
          <UButton
            icon="i-heroicons-arrow-path"
            color="gray"
            variant="ghost"
            size="sm"
            :loading="loading"
            @click="loadData"
          />
        </div>
      </template>

      <!-- Table Section -->
      <UTable
        :rows="displayRows"
        :columns="columns"
        :loading="loading"
      >
        <template #supplier_name-data="{ row }">
          <div class="flex flex-col">
            <span class="font-medium text-gray-900">{{ row.supplier_name }}</span>
            <span class="text-xs text-gray-500">{{ row.supplier_email }}</span>
          </div>
        </template>

        <template #templates-data="{ row }">
          <div class="flex gap-1">
            <UBadge v-if="row.cmrt_required" size="xs" variant="soft" color="blue">CMRT</UBadge>
            <UBadge v-if="row.emrt_required" size="xs" variant="soft" color="purple">EMRT</UBadge>
            <UBadge v-if="row.amrt_required" size="xs" variant="soft" color="gray">AMRT</UBadge>
          </div>
        </template>

        <template #status-data="{ row }">
          <UBadge 
            :color="getStatusColor(row.status)" 
            variant="soft" 
            size="xs"
            class="capitalize"
          >
            {{ getStatusLabel(row.status) }}
          </UBadge>
        </template>

        <template #submitted_at-data="{ row }">
          <span class="text-sm font-mono text-gray-600">
            {{ row.submitted_at ? formatDate(row.submitted_at) : '-' }}
          </span>
        </template>

        <template #actions-data="{ row }">
          <UButton
            v-if="row.status === 'submitted' || row.status === 'reviewing'"
            size="xs"
            color="primary"
            variant="solid"
            icon="i-heroicons-magnifying-glass"
            :to="`/conflict/projects/${projectId}/review/${row.id}`"
          >
            執行審核
          </UButton>
          <UButton
            v-else-if="row.status === 'completed'"
            size="xs"
            color="gray"
            variant="ghost"
            icon="i-heroicons-eye"
            :to="`/conflict/projects/${projectId}/review/${row.id}`"
          >
            檢視記錄
          </UButton>
        </template>

        <!-- Empty State -->
        <template #empty-state>
          <div class="flex flex-col items-center justify-center py-12 text-gray-400">
            <UIcon name="i-heroicons-inbox" class="w-12 h-12 mb-2 opacity-20" />
            <p>暫無相關資料</p>
          </div>
        </template>
      </UTable>
    </UCard>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useResponsibleMinerals } from '~/composables/useResponsibleMinerals'

interface Props {
  projectId: number
}

const props = defineProps<Props>()

const { fetchSuppliersWithTemplates } = useResponsibleMinerals()
const loading = ref(false)
const allAssignments = ref<any[]>([])
const selectedTabIndex = ref(0)

const tabItems = [
  { label: '待審核', icon: 'i-heroicons-clock' },
  { label: '已完成', icon: 'i-heroicons-check-circle' },
  { label: '全部提交', icon: 'i-heroicons-list-bullet' }
]

const columns = [
  { key: 'supplier_name', label: '供應商名稱' },
  { key: 'templates', label: '需填範本' },
  { key: 'submitted_at', label: '提交時間', sortable: true },
  { key: 'status', label: '狀態' },
  { key: 'actions', label: '操作' }
]

const pendingReviews = computed(() => {
  return allAssignments.value.filter(a => a.status === 'submitted' || a.status === 'reviewing')
})

const completedReviews = computed(() => {
  return allAssignments.value.filter(a => a.status === 'completed')
})

const submissionRate = computed(() => {
  if (allAssignments.value.length === 0) return 0
  const submittedCount = allAssignments.value.filter(a => ['submitted', 'reviewing', 'completed'].includes(a.status)).length
  return Math.round((submittedCount / allAssignments.value.length) * 100)
})

const displayRows = computed(() => {
  if (selectedTabIndex.value === 0) return pendingReviews.value
  if (selectedTabIndex.value === 1) return completedReviews.value
  return allAssignments.value.filter(a => ['submitted', 'reviewing', 'completed'].includes(a.status))
})

const loadData = async () => {
  loading.value = true
  try {
    const data = await fetchSuppliersWithTemplates(props.projectId)
    allAssignments.value = data || []
  } catch (error) {
    console.error('Failed to load review data:', error)
  } finally {
    loading.value = false
  }
}

const onTabChange = () => {
  // nuxt ui tabs v-model handles it
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'submitted': return 'orange'
    case 'reviewing': return 'blue'
    case 'completed': return 'green'
    default: return 'gray'
  }
}

const getStatusLabel = (status: string) => {
  switch (status) {
    case 'submitted': return '已提交'
    case 'reviewing': return '審核中'
    case 'completed': return '審核完成'
    default: return status
  }
}

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('zh-TW', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.review-management {
  /* Nuxt UI components handle styling mostly */
}
</style>
