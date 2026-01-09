<template>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">責任礦產審核管理</h1>
      <p class="mt-2 text-sm text-gray-600">管理供應商提交的 CMRT/EMRT 問卷審核申請。</p>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
      <UCard :ui="{ body: { padding: 'p-4' } }">
        <div class="text-sm text-gray-500">待審核總數</div>
        <div class="text-2xl font-bold text-orange-600">{{ pendingReviews.length }}</div>
      </UCard>
    </div>

    <!-- Pending Reviews Table -->
    <UCard>
      <template #header>
        <div class="flex items-center justify-between">
          <h2 class="font-semibold text-gray-900">待審核列表</h2>
          <UButton
            icon="i-heroicons-arrow-path"
            color="gray"
            variant="ghost"
            :loading="loading"
            @click="init"
          />
        </div>
      </template>

      <UTable
        :rows="pendingReviews"
        :columns="columns"
        :loading="loading"
      >
        <template #project-data="{ row }">
          <div class="font-medium text-gray-900">{{ row.project_name }}</div>
          <div class="text-xs text-gray-500">ID: {{ row.project_id }}</div>
        </template>

        <template #supplier-data="{ row }">
          <div class="font-medium text-gray-900">{{ row.supplier_name }}</div>
          <div class="text-xs text-gray-500">{{ row.supplier_email }}</div>
        </template>

        <template #templates-data="{ row }">
          <div class="flex flex-wrap gap-1">
            <UBadge v-if="row.cmrt_required" size="xs" color="blue" variant="soft">CMRT</UBadge>
            <UBadge v-if="row.emrt_required" size="xs" color="purple" variant="soft">EMRT</UBadge>
            <UBadge v-if="row.amrt_required" size="xs" color="gray" variant="soft">AMRT</UBadge>
          </div>
        </template>

        <template #submitted_at-data="{ row }">
          <div class="text-sm">{{ row.submitted_at || '-' }}</div>
        </template>

        <template #actions-data="{ row }">
          <UButton
            size="xs"
            color="primary"
            variant="solid"
            icon="i-heroicons-magnifying-glass"
            :to="`/conflict/projects/${row.project_id}/review/${row.id}`"
          >
            檢視並審核
          </UButton>
        </template>
      </UTable>

      <div v-if="!loading && pendingReviews.length === 0" class="text-center py-12">
        <UIcon name="i-heroicons-check-badge" class="w-12 h-12 text-gray-300 mx-auto mb-4" />
        <p class="text-gray-500 font-medium">目前沒有待審核的問卷</p>
      </div>
    </UCard>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useResponsibleMinerals } from '~/composables/useResponsibleMinerals'

definePageMeta({ middleware: 'auth' })

const { fetchPendingReviews } = useResponsibleMinerals()
const loading = ref(true)
const pendingReviews = ref<any[]>([])

const columns = [
  { key: 'project', label: '專案名稱' },
  { key: 'supplier', label: '供應商' },
  { key: 'templates', label: '範本類型' },
  { key: 'submitted_at', label: '提交時間', sortable: true },
  { key: 'actions', label: '操作' }
]

const init = async () => {
  loading.value = true
  try {
    pendingReviews.value = await fetchPendingReviews()
  } catch (err) {
    console.error('Failed to fetch pending reviews:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  init()
})
</script>
