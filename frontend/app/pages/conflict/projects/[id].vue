<template>
  <div class="rm-project-detail-page">
    <!-- 頁面標題 -->
    <div class="page-header">
      <div class="header-top">
        <div class="header-left">
          <UButton
            icon="i-heroicons-arrow-left"
            color="gray"
            variant="ghost"
            @click="router.back()"
          />
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ project?.name || '專案詳情' }}</h1>
            <p class="text-sm text-gray-500 mt-1" v-if="project">
              {{ project.year }} · {{ project.type }} · 建立於 {{ formatDate(project.createdAt) }}
            </p>
          </div>
        </div>

        <!-- 頁籤導航 -->
        <div v-if="project" class="tabs-nav">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            :class="['tab-button', { active: activeTab === tab.id }]"
            @click="activeTab = tab.id"
          >
            <UIcon :name="tab.icon" class="w-4 h-4" />
            {{ tab.label }}
            <UBadge v-if="tab.badge" color="gray" variant="soft" size="xs">
              {{ tab.badge }}
            </UBadge>
          </button>
        </div>

        <UButton
          v-if="project"
          icon="i-heroicons-pencil-square"
          color="white"
          @click="showEditModal = true"
        >
          編輯專案
        </UButton>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <UIcon name="i-heroicons-arrow-path" class="w-8 h-8 animate-spin text-primary-500" />
    </div>

    <!-- Error -->
    <div v-else-if="error" class="text-center py-12 bg-red-50 rounded-lg border border-red-200">
      <div class="text-red-600 font-medium">{{ error }}</div>
      <UButton
        class="mt-4"
        color="primary"
        variant="soft"
        @click="loadProject"
      >
        重試
      </UButton>
    </div>

    <!-- 內容區 -->
    <div v-else-if="project" class="content-container">
      <!-- 頁籤內容 -->
      <div class="tab-content">
        <!-- 總覽 -->
        <div v-show="activeTab === 'overview'" class="tab-pane">
          <ProjectOverview :project="project" @edit="showEditModal = true" />
        </div>

        <!-- 供應商範本管理 -->
        <div v-show="activeTab === 'suppliers'" class="tab-pane">
          <SupplierTemplates :project-id="projectId" />
        </div>

        <!-- 進度追蹤 -->
        <div v-show="activeTab === 'progress'" class="tab-pane">
          <ProjectProgress :project-id="projectId" />
        </div>

        <!-- 審核 -->
        <div v-show="activeTab === 'review'" class="tab-pane">
          <ReviewManagement :project-id="projectId" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProjects } from '~/composables/useProjects'
import { useBreadcrumbs } from '~/composables/useBreadcrumbs'
import type { Project } from '~/types/index'

// 動態匯入子頁面
const ProjectOverview = defineAsyncComponent(() => import('~/components/conflict/ProjectOverview.vue'))
const SupplierTemplates = defineAsyncComponent(() => import('./[id]/suppliers.vue'))
const ProjectProgress = defineAsyncComponent(() => import('./[id]/progress.vue'))
const ReviewManagement = defineAsyncComponent(() => import('~/components/conflict/ReviewManagement.vue'))

definePageMeta({ middleware: 'auth' })

const route = useRoute()
const router = useRouter()
const { setBreadcrumbs } = useBreadcrumbs()
const { getProject } = useProjects()

const projectId = computed(() => Number(route.params.id))
const loading = ref(true)
const error = ref('')
const project = ref<Project | null>(null)
const showEditModal = ref(false)
const activeTab = ref('overview')

// 頁籤定義
const tabs = computed(() => [
  {
    id: 'overview',
    label: '專案總覽',
    icon: 'i-heroicons-squares-2x2',
    badge: null
  },
  {
    id: 'suppliers',
    label: '供應商管理',
    icon: 'i-heroicons-user-group',
    badge: project.value?.suppliers?.length || null
  },
  {
    id: 'progress',
    label: '進度追蹤',
    icon: 'i-heroicons-chart-bar',
    badge: null
  },
  {
    id: 'review',
    label: '審核管理',
    icon: 'i-heroicons-check-circle',
    badge: null
  }
])

const formatDate = (dateStr?: string) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('zh-TW', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  })
}

const loadProject = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await getProject(projectId.value.toString())
    project.value = response.data

    setBreadcrumbs([
      { label: '首頁', to: '/' },
      { label: '衝突礦產', to: '/conflict' },
      { label: '專案列表', to: '/conflict/projects' },
      { label: project.value?.name || '專案詳情' }
    ])
  } catch (err: any) {
    error.value = err.message || '載入失敗'
  } finally {
    loading.value = false
  }
}

// URL 參數控制活動頁籤
const initActiveTab = () => {
  const tab = route.query.tab as string
  if (tab && ['overview', 'suppliers', 'progress', 'review'].includes(tab)) {
    activeTab.value = tab
  }
}

// 監聽頁籤變化，更新 URL
watch(activeTab, (newTab) => {
  router.replace({
    query: { ...route.query, tab: newTab }
  })
})

onMounted(() => {
  initActiveTab()
  loadProject()
})
</script>

<style scoped>
.rm-project-detail-page {
  padding: 1.5rem 1rem;
}

@media (min-width: 640px) {
  .rm-project-detail-page {
    padding: 2rem 1.5rem;
  }
}

@media (min-width: 1024px) {
  .rm-project-detail-page {
    padding: 2rem;
  }
}

.page-header {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 2px solid #e5e7eb;
}

.header-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-shrink: 0;
}

.tabs-nav {
  display: flex;
  gap: 0.5rem;
  flex: 1;
  justify-content: center;
  overflow-x: auto;
}

.tab-button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border: none;
  background: none;
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 500;
  color: #6b7280;
  border-radius: 0.5rem;
  white-space: nowrap;
  transition: all 0.2s;
}

.tab-button:hover {
  color: #374151;
  background: #f9fafb;
}

.tab-button.active {
  color: #2563eb;
  background: #eff6ff;
}

.tab-content {
  background: white;
  border-radius: 0.75rem;
  min-height: 400px;
}

.tab-pane {
  animation: fadeIn 0.3s;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* 響應式調整 */
@media (max-width: 1024px) {
  .header-top {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .tabs-nav {
    width: 100%;
    justify-content: flex-start;
  }
}
</style>