<template>
  <div class="rm-progress-page">
    <div class="page-header">
      <h1>填寫進度追蹤</h1>
      <p class="page-description">查看各供應商的範本指派與填寫狀況</p>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>載入中...</p>
    </div>

    <div v-else-if="error" class="error-state">
      <p class="error-message">{{ error }}</p>
      <button class="btn btn-primary" @click="loadProgress">重試</button>
    </div>

    <div v-else>
      <!-- 總覽統計卡片 -->
      <div class="stats-grid">
        <div class="stat-card total">
          <div class="stat-icon">👥</div>
          <div class="stat-content">
            <div class="stat-label">總供應商數</div>
            <div class="stat-value">{{ progressData?.summary.totalSuppliers || 0 }}</div>
          </div>
        </div>

        <div class="stat-card assigned">
          <div class="stat-icon">📋</div>
          <div class="stat-content">
            <div class="stat-label">已指派範本</div>
            <div class="stat-value">{{ progressData?.summary.assignedSuppliers || 0 }}</div>
          </div>
        </div>

        <div class="stat-card not-assigned">
          <div class="stat-icon">⚠️</div>
          <div class="stat-content">
            <div class="stat-label">未指派範本</div>
            <div class="stat-value">{{ progressData?.summary.notAssignedSuppliers || 0 }}</div>
          </div>
        </div>

        <div class="stat-card completed">
          <div class="stat-icon">✅</div>
          <div class="stat-content">
            <div class="stat-label">已完成填寫</div>
            <div class="stat-value">{{ progressData?.summary.completedSuppliers || 0 }}</div>
          </div>
        </div>

        <div class="stat-card in-progress">
          <div class="stat-icon">📝</div>
          <div class="stat-content">
            <div class="stat-label">進行中</div>
            <div class="stat-value">{{ progressData?.summary.inProgressSuppliers || 0 }}</div>
          </div>
        </div>

        <div class="stat-card not-started">
          <div class="stat-icon">⏸️</div>
          <div class="stat-content">
            <div class="stat-label">未開始</div>
            <div class="stat-value">{{ progressData?.summary.notStartedSuppliers || 0 }}</div>
          </div>
        </div>
      </div>

      <!-- 範本類型統計 -->
      <div class="template-stats-section">
        <h2 class="section-title">範本類型統計</h2>
        <div class="template-stats-grid">
          <div class="template-stat-card">
            <div class="template-name">CMRT</div>
            <div class="template-description">Conflict Minerals (3TG)</div>
            <div class="progress-bar">
              <div
                class="progress-fill"
                :style="{ width: `${progressData?.templateStats.cmrt.percentage || 0}%`, background: '#007bff' }"
              ></div>
            </div>
            <div class="progress-text">
              {{ progressData?.templateStats.cmrt.percentage || 0 }}% 
              ({{ progressData?.templateStats.cmrt.completed || 0 }}/{{ progressData?.templateStats.cmrt.total || 0 }})
            </div>
          </div>

          <div class="template-stat-card">
            <div class="template-name">EMRT</div>
            <div class="template-description">Extended Minerals</div>
            <div class="progress-bar">
              <div
                class="progress-fill"
                :style="{ width: `${progressData?.templateStats.emrt.percentage || 0}%`, background: '#28a745' }"
              ></div>
            </div>
            <div class="progress-text">
              {{ progressData?.templateStats.emrt.percentage || 0 }}% 
              ({{ progressData?.templateStats.emrt.completed || 0 }}/{{ progressData?.templateStats.emrt.total || 0 }})
            </div>
          </div>

          <div class="template-stat-card">
            <div class="template-name">AMRT</div>
            <div class="template-description">Additional Minerals</div>
            <div class="progress-bar">
              <div
                class="progress-fill"
                :style="{ width: `${progressData?.templateStats.amrt.percentage || 0}%`, background: '#ffc107' }"
              ></div>
            </div>
            <div class="progress-text">
              {{ progressData?.templateStats.amrt.percentage || 0 }}% 
              ({{ progressData?.templateStats.amrt.completed || 0 }}/{{ progressData?.templateStats.amrt.total || 0 }})
            </div>
          </div>
        </div>
      </div>

      <!-- 篩選工具列 -->
      <div class="filters-toolbar">
        <div class="filters-left">
          <input
            v-model="searchQuery"
            type="text"
            class="search-input"
            placeholder="搜尋供應商名稱..."
          />
          
          <select v-model="templateFilter" class="filter-select">
            <option value="all">全部範本</option>
            <option value="CMRT">CMRT</option>
            <option value="EMRT">EMRT</option>
            <option value="AMRT">AMRT</option>
          </select>

          <select v-model="statusFilter" class="filter-select">
            <option value="all">全部狀態</option>
            <option value="completed">已完成</option>
            <option value="in_progress">進行中</option>
            <option value="not_started">未開始</option>
            <option value="not_assigned">未指派</option>
          </select>
        </div>

        <div class="filters-right">
          <button class="btn btn-secondary" @click="handleExport">
            <i class="icon-download"></i>
            匯出 Excel
          </button>
          <button class="btn btn-primary" @click="loadProgress">
            <i class="icon-refresh"></i>
            重新整理
          </button>
        </div>
      </div>

      <!-- 供應商明細表格 -->
      <div class="suppliers-detail-table-container">
        <table class="suppliers-detail-table">
          <thead>
            <tr>
              <th>供應商名稱</th>
              <th class="text-center">指派範本</th>
              <th class="text-center">狀態</th>
              <th class="text-center">完成度</th>
              <th class="text-center">最後更新時間</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="supplier in filteredSuppliers" :key="supplier.supplierId">
              <td class="supplier-name">{{ supplier.supplierName }}</td>
              <td class="templates-cell">
                <div class="template-badges">
                  <span
                    v-for="tpl in supplier.assignedTemplates"
                    :key="tpl"
                    :class="['template-badge', `template-${tpl.toLowerCase()}`]"
                  >
                    {{ tpl }}
                  </span>
                  <span v-if="supplier.assignedTemplates.length === 0" class="text-muted">
                    未指派
                  </span>
                </div>
              </td>
              <td class="text-center">
                <span :class="['status-badge', `status-${getStatusClass(supplier.status)}`]">
                  {{ supplier.status }}
                </span>
              </td>
              <td class="completion-cell">
                <div class="completion-bar-container">
                  <div class="completion-bar">
                    <div
                      class="completion-fill"
                      :style="{ width: `${supplier.completionRate}%` }"
                    ></div>
                  </div>
                  <span class="completion-text">{{ supplier.completionRate }}%</span>
                </div>
              </td>
              <td class="text-center text-muted">
                {{ formatDate(supplier.lastUpdated) }}
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="filteredSuppliers.length === 0" class="empty-state">
          <p>沒有符合條件的資料</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useResponsibleMinerals } from '~/composables/useResponsibleMinerals'
import { useSweetAlert } from '~/composables/useSweetAlert'

const route = useRoute()
const { showSuccess, showError } = useSweetAlert()
const {
  progressData,
  fetchProgress,
  exportProgress
} = useResponsibleMinerals()

const projectId = computed(() => Number(route.params.id))

// 狀態
const loading = ref(false)
const error = ref('')
const searchQuery = ref('')
const templateFilter = ref('all')
const statusFilter = ref('all')

// 計算屬性
const filteredSuppliers = computed(() => {
  if (!progressData.value) return []
  
  let result = progressData.value.suppliers

  // 搜尋過濾
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(s =>
      s.supplierName.toLowerCase().includes(query)
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

const formatDate = (dateString: string): string => {
  if (!dateString || dateString === '-') return '-'
  
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('zh-TW', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit'
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
.rm-progress-page {
  padding: 24px;
}

.page-header {
  margin-bottom: 24px;
}

.page-header h1 {
  font-size: 28px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 8px 0;
}

.page-description {
  color: #666;
  margin: 0;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 32px;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  border-left: 4px solid #ddd;
  transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.stat-card.total { border-left-color: #333; }
.stat-card.assigned { border-left-color: #007bff; }
.stat-card.not-assigned { border-left-color: #dc3545; }
.stat-card.completed { border-left-color: #28a745; }
.stat-card.in-progress { border-left-color: #ffc107; }
.stat-card.not-started { border-left-color: #6c757d; }

.stat-icon {
  font-size: 32px;
}

.stat-content {
  flex: 1;
}

.stat-label {
  font-size: 13px;
  color: #666;
  margin-bottom: 4px;
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #1a1a1a;
}

.template-stats-section {
  background: white;
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 24px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.section-title {
  font-size: 20px;
  font-weight: 600;
  margin: 0 0 20px 0;
  color: #1a1a1a;
}

.template-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}

.template-stat-card {
  padding: 20px;
  background: #f8f9fa;
  border-radius: 8px;
}

.template-name {
  font-size: 20px;
  font-weight: 700;
  margin-bottom: 4px;
}

.template-description {
  font-size: 13px;
  color: #666;
  margin-bottom: 16px;
}

.progress-bar {
  height: 24px;
  background: #e9ecef;
  border-radius: 12px;
  overflow: hidden;
  margin-bottom: 8px;
}

.progress-fill {
  height: 100%;
  transition: width 0.3s;
  border-radius: 12px;
}

.progress-text {
  font-size: 14px;
  font-weight: 600;
  color: #495057;
}

.filters-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
}

.filters-left {
  display: flex;
  gap: 12px;
  flex: 1;
}

.filters-right {
  display: flex;
  gap: 12px;
}

.search-input {
  flex: 1;
  padding: 10px 16px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  max-width: 300px;
}

.filter-select {
  padding: 10px 16px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  min-width: 150px;
}

.suppliers-detail-table-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.suppliers-detail-table {
  width: 100%;
  border-collapse: collapse;
}

.suppliers-detail-table th {
  background: #f8f9fa;
  padding: 16px;
  text-align: left;
  font-weight: 600;
  font-size: 14px;
  color: #495057;
  border-bottom: 2px solid #dee2e6;
}

.suppliers-detail-table td {
  padding: 16px;
  border-bottom: 1px solid #dee2e6;
  font-size: 14px;
}

.suppliers-detail-table tr:hover {
  background: #f8f9fa;
}

.text-center {
  text-align: center !important;
}

.templates-cell {
  min-width: 200px;
}

.template-badges {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  justify-content: center;
}

.template-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}

.template-cmrt {
  background: #cfe2ff;
  color: #084298;
}

.template-emrt {
  background: #d1e7dd;
  color: #0f5132;
}

.template-amrt {
  background: #fff3cd;
  color: #856404;
}

.status-badge {
  display: inline-block;
  padding: 6px 14px;
  border-radius: 14px;
  font-size: 13px;
  font-weight: 500;
}

.status-completed {
  background: #d4edda;
  color: #155724;
}

.status-in_progress {
  background: #fff3cd;
  color: #856404;
}

.status-not_started {
  background: #f8d7da;
  color: #721c24;
}

.status-not_assigned {
  background: #e9ecef;
  color: #6c757d;
}

.completion-cell {
  min-width: 180px;
}

.completion-bar-container {
  display: flex;
  align-items: center;
  gap: 12px;
}

.completion-bar {
  flex: 1;
  height: 18px;
  background: #e9ecef;
  border-radius: 9px;
  overflow: hidden;
}

.completion-fill {
  height: 100%;
  background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
  transition: width 0.3s;
  border-radius: 9px;
}

.completion-text {
  font-size: 13px;
  font-weight: 600;
  color: #495057;
  min-width: 45px;
  text-align: right;
}

.text-muted {
  color: #6c757d;
}

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-primary {
  background: #007bff;
  color: white;
}

.btn-primary:hover {
  background: #0056b3;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background: #545b62;
}

.loading-state,
.error-state,
.empty-state {
  text-align: center;
  padding: 60px 20px;
}

.spinner {
  border: 3px solid #f3f3f3;
  border-top: 3px solid #007bff;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-message {
  color: #dc3545;
  margin-bottom: 16px;
}
</style>
