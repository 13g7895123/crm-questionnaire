<template>
  <div class="rm-suppliers-page">
    <div class="action-toolbar">
      <div class="action-group">
        <button
          class="btn btn-primary"
          @click="openBatchModal"
          :disabled="selectedSuppliers.length === 0"
        >
          <UIcon name="i-heroicons-clipboard-document-list" />
          批量設定範本 ({{ selectedSuppliers.length }})
        </button>
        <button
          class="btn btn-danger"
          @click="handleBatchDelete"
          :disabled="selectedSuppliers.length === 0"
        >
          <UIcon name="i-heroicons-trash" />
          批量刪除 ({{ selectedSuppliers.length }})
        </button>
        <button class="btn btn-secondary" @click="handleExcelImport">
          <UIcon name="i-heroicons-arrow-up-tray" />
          匯入
        </button>
        <button class="btn btn-secondary" @click="downloadTemplate">
          <UIcon name="i-heroicons-arrow-down-tray" />
          下載範本(現有供應商清單)
        </button>
      </div>
      
      <button
        class="btn btn-success"
        @click="handleNotifyAll"
        :disabled="assignedCount === 0"
      >
        <UIcon name="i-heroicons-envelope" />
        全部通知 ({{ assignedCount }})
      </button>
    </div>

    <div class="filters-bar">
      <input
        v-model="searchQuery"
        type="text"
        class="search-input"
        placeholder="搜尋供應商名稱..."
      />
      
      <select v-model="statusFilter" class="filter-select">
        <option value="all">全部狀態</option>
        <option value="assigned">已指派</option>
        <option value="not_assigned">未指派</option>
      </select>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>載入中...</p>
    </div>

    <div v-else-if="error" class="error-state">
      <p class="error-message">{{ error }}</p>
      <button class="btn btn-primary" @click="loadSuppliers">重試</button>
    </div>

    <div v-else class="suppliers-table-container">
      <table class="suppliers-table">
        <thead>
          <tr>
            <th class="checkbox-col">
              <input
                type="checkbox"
                v-model="selectAll"
                @change="handleSelectAll"
              />
            </th>
            <th>供應商名稱</th>
            <th>Email</th>
            <th class="text-center">CMRT</th>
            <th class="text-center">EMRT</th>
            <th class="text-center">AMRT</th>
            <th class="text-center">AMRT 礦產</th>
            <th class="text-center">狀態</th>
            <th class="text-center">操作</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="supplier in paginatedSuppliers"
            :key="supplier.id"
            :class="{ 'row-selected': selectedSuppliers.includes(supplier.id) }"
          >
            <td class="checkbox-col">
              <input
                type="checkbox"
                :value="supplier.id"
                v-model="selectedSuppliers"
              />
            </td>
            <td class="supplier-name">{{ supplier.supplierName }}</td>
            <td class="supplier-email">{{ supplier.supplierEmail }}</td>
            <td class="text-center">
              <span v-if="supplier.cmrt_required" class="badge badge-success">Yes</span>
              <span v-else class="badge badge-gray">No</span>
            </td>
            <td class="text-center">
              <span v-if="supplier.emrt_required" class="badge badge-success">Yes</span>
              <span v-else class="badge badge-gray">No</span>
            </td>
            <td class="text-center">
              <span v-if="supplier.amrt_required" class="badge badge-success">Yes</span>
              <span v-else class="badge badge-gray">No</span>
            </td>
            <td class="minerals-col">
              <span v-if="supplier.amrt_minerals && supplier.amrt_minerals.length > 0" class="minerals-list">
                {{ supplier.amrt_minerals.join(', ') }}
              </span>
              <span v-else class="text-muted">-</span>
            </td>
            <td class="text-center">
              <span
                :class="['status-badge', `status-${supplier.status}`]"
              >
                {{ getStatusText(supplier.status) }}
              </span>
            </td>
            <td class="actions-col">
              <div class="actions-inner">
                <button
                  class="btn-icon btn-edit"
                  @click="editSupplier(supplier)"
                  title="編輯範本"
                >
                  <UIcon name="i-heroicons-pencil-square" />
                </button>
                <button
                  v-if="isAssigned(supplier)"
                  class="btn-icon btn-fill"
                  @click="goToAnswer(supplier)"
                  title="填寫問卷"
                >
                  <UIcon name="i-heroicons-pencil" />
                </button>
                <button
                  v-if="isAssigned(supplier)"
                  class="btn-icon btn-notify"
                  @click="notifySupplier(supplier)"
                  title="發送通知"
                >
                  <UIcon name="i-heroicons-bell" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="paginatedSuppliers.length === 0" class="empty-state">
        <p>沒有符合條件的供應商</p>
      </div>

      <!-- 分頁控制 -->
      <div v-if="totalPages > 1" class="pagination-container">
        <div class="pagination-info">
          顯示 {{ (currentPage - 1) * pageSize + 1 }} - {{ Math.min(currentPage * pageSize, totalRecords) }} / 共 {{ totalRecords }} 筆
        </div>
        <div class="pagination-controls">
          <button
            class="btn btn-pagination"
            :disabled="currentPage === 1"
            @click="currentPage = 1"
          >
            <UIcon name="i-heroicons-chevron-double-left" />
          </button>
          <button
            class="btn btn-pagination"
            :disabled="currentPage === 1"
            @click="currentPage--"
          >
            <UIcon name="i-heroicons-chevron-left" />
          </button>
          
          <div class="page-numbers">
            <template v-for="page in getPageNumbers()" :key="page">
              <button
                v-if="page !== '...'"
                :class="['btn btn-pagination', { 'active': currentPage === page }]"
                @click="currentPage = Number(page)"
              >
                {{ page }}
              </button>
              <span v-else class="page-ellipsis">...</span>
            </template>
          </div>

          <button
            class="btn btn-pagination"
            :disabled="currentPage === totalPages"
            @click="currentPage++"
          >
            <UIcon name="i-heroicons-chevron-right" />
          </button>
          <button
            class="btn btn-pagination"
            :disabled="currentPage === totalPages"
            @click="currentPage = totalPages"
          >
            <UIcon name="i-heroicons-chevron-double-right" />
          </button>
        </div>
        <div class="page-size-selector">
          <label>每頁顯示:</label>
          <select v-model.number="pageSize" @change="currentPage = 1">
            <option :value="10">10</option>
            <option :value="20">20</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
      </div>
    </div>

    <!-- 編輯供應商範本 Modal -->
    <div v-if="showEditModal" class="modal-overlay" @click="closeEditModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>設定範本 - {{ editingSupplier?.supplierName }}</h2>
          <button class="modal-close" @click="closeEditModal">&times;</button>
        </div>
        
        <div class="modal-body">
          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="editForm.cmrt_required" />
              <span class="label-text">
                <strong>CMRT</strong> - Conflict Minerals (3TG: 錫、鉭、鎢、金)
              </span>
            </label>
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="editForm.emrt_required" />
              <span class="label-text">
                <strong>EMRT</strong> - Extended Minerals (鈷、雲母、銅、石墨、鋰、鎳)
              </span>
            </label>
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="editForm.amrt_required" />
              <span class="label-text">
                <strong>AMRT</strong> - Additional Minerals (自選礦產)
              </span>
            </label>
          </div>

          <div v-if="editForm.amrt_required" class="form-group minerals-selection">
            <label class="form-label">選擇 AMRT 礦產（可多選，最多10種）</label>
            <div class="minerals-grid">
              <label
                v-for="mineral in availableMinerals"
                :key="mineral"
                class="mineral-checkbox"
              >
                <input
                  type="checkbox"
                  :value="mineral"
                  v-model="editForm.amrt_minerals"
                  :disabled="
                    editForm.amrt_minerals.length >= 10 &&
                    !editForm.amrt_minerals.includes(mineral)
                  "
                />
                <span>{{ mineral }}</span>
              </label>
            </div>
            <p class="help-text">
              已選擇: {{ editForm.amrt_minerals.length }} / 10
            </p>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" @click="closeEditModal">取消</button>
          <button
            class="btn btn-primary"
            @click="saveTemplateAssignment"
            :disabled="!isFormValid"
          >
            儲存
          </button>
        </div>
      </div>
    </div>

    <!-- 批量設定範本 Modal -->
    <div v-if="showBatchAssignModal" class="modal-overlay" @click="closeBatchModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>批量設定範本 ({{ selectedSuppliers.length }} 個供應商)</h2>
          <button class="modal-close" @click="closeBatchModal">&times;</button>
        </div>
        
        <div class="modal-body">
          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="batchForm.cmrt_required" />
              <span class="label-text"><strong>CMRT</strong></span>
            </label>
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="batchForm.emrt_required" />
              <span class="label-text"><strong>EMRT</strong></span>
            </label>
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="batchForm.amrt_required" />
              <span class="label-text"><strong>AMRT</strong></span>
            </label>
          </div>

          <div v-if="batchForm.amrt_required" class="form-group minerals-selection">
            <label class="form-label">選擇 AMRT 礦產</label>
            <div class="minerals-grid">
              <label
                v-for="mineral in availableMinerals"
                :key="mineral"
                class="mineral-checkbox"
              >
                <input
                  type="checkbox"
                  :value="mineral"
                  v-model="batchForm.amrt_minerals"
                />
                <span>{{ mineral }}</span>
              </label>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" @click="closeBatchModal">取消</button>
          <button
            class="btn btn-primary"
            @click="saveBatchAssignment"
            :disabled="!isBatchFormValid"
          >
            批量設定
          </button>
        </div>
      </div>
    </div>

    <!-- Excel 匯入 Modal -->
    <input
      ref="excelFileInput"
      type="file"
      accept=".xlsx,.xls,.csv"
      style="display: none"
      @change="handleFileSelected"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useResponsibleMinerals, type SupplierAssignment, type TemplateType } from '~/composables/useResponsibleMinerals'
import { useSweetAlert } from '~/composables/useSweetAlert'
import { useExcel } from '~/composables/useExcel'
import { useSuppliers } from '~/composables/useSuppliers'

const route = useRoute()
const router = useRouter()
const { showSuccess, showError, showErrorWithConfirm, showConfirm } = useSweetAlert()
const { parseExcel, downloadTemplate: downloadExcelTemplate } = useExcel()
const { fetchSuppliers: fetchAllSuppliers } = useSuppliers()
const {
  suppliers,
  fetchSuppliersWithTemplates,
  assignTemplateToSupplier,
  batchAssignTemplates,
  addSuppliersToProject,
  importTemplateAssignments,
  notifySupplier: notifySupplierApi,
  notifyAllSuppliers,
  batchDeleteSuppliers,
  downloadTemplateAssignmentTemplate
} = useResponsibleMinerals()

const projectId = computed(() => Number(route.params.id))

// 狀態
const loading = ref(false)
const error = ref('')
const searchQuery = ref('')
const statusFilter = ref('all')
const selectedSuppliers = ref<number[]>([])
const selectAll = ref(false)

// 分頁狀態
const currentPage = ref(1)
const pageSize = ref(20)
const totalRecords = ref(0)

// Modal 狀態
const showEditModal = ref(false)
const showBatchAssignModal = ref(false)
const editingSupplier = ref<SupplierAssignment | null>(null)
const excelFileInput = ref<HTMLInputElement | null>(null)

// 表單資料
const editForm = ref<TemplateType & { amrt_minerals: string[] }>({
  cmrt_required: false,
  emrt_required: false,
  amrt_required: false,
  amrt_minerals: []
})

const batchForm = ref<TemplateType & { amrt_minerals: string[] }>({
  cmrt_required: false,
  emrt_required: false,
  amrt_required: false,
  amrt_minerals: []
})

// 可用的礦產選項
const availableMinerals = [
  'Silver', 'Platinum', 'Palladium', 'Rhodium',
  'Aluminum', 'Chromium', 'Vanadium', 'Manganese',
  'Iron', 'Zinc', 'Molybdenum', 'Tellurium',
  'Antimony', 'Bismuth', 'Rare Earth Elements'
]

// 計算屬性
const filteredSuppliers = computed(() => {
  let result = suppliers.value

  // 搜尋過濾
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(s =>
      (s.supplierName?.toLowerCase().includes(query)) ||
      (s.supplierEmail?.toLowerCase().includes(query))
    )
  }

  // 狀態過濾
  if (statusFilter.value === 'assigned') {
    result = result.filter(s => isAssigned(s))
  } else if (statusFilter.value === 'not_assigned') {
    result = result.filter(s => !isAssigned(s))
  }

  // 更新總記錄數
  totalRecords.value = result.length

  return result
})

// 分頁後的供應商列表
const paginatedSuppliers = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  const end = start + pageSize.value
  return filteredSuppliers.value.slice(start, end)
})

// 總頁數
const totalPages = computed(() => {
  return Math.ceil(totalRecords.value / pageSize.value)
})

const assignedCount = computed(() => {
  return suppliers.value.filter(s => isAssigned(s)).length
})

const isFormValid = computed(() => {
  const form = editForm.value
  if (!form.cmrt_required && !form.emrt_required && !form.amrt_required) {
    return false
  }
  if (form.amrt_required && form.amrt_minerals.length === 0) {
    return false
  }
  return true
})

const isBatchFormValid = computed(() => {
  const form = batchForm.value
  if (!form.cmrt_required && !form.emrt_required && !form.amrt_required) {
    return false
  }
  if (form.amrt_required && form.amrt_minerals.length === 0) {
    return false
  }
  return true
})

// 方法
const loadSuppliers = async () => {
  loading.value = true
  error.value = ''
  try {
    await fetchSuppliersWithTemplates(projectId.value)
  } catch (err: any) {
    error.value = err.message || '載入失敗'
  } finally {
    loading.value = false
  }
}

const isAssigned = (supplier: SupplierAssignment) => {
  return supplier.cmrt_required || supplier.emrt_required || supplier.amrt_required
}

const getStatusText = (status: string) => {
  const statusMap: Record<string, string> = {
    not_assigned: '未指派',
    assigned: '已指派',
    in_progress: '進行中',
    completed: '已完成'
  }
  return statusMap[status] || status
}

const handleSelectAll = () => {
  if (selectAll.value) {
    selectedSuppliers.value = paginatedSuppliers.value.map(s => s.id)
  } else {
    selectedSuppliers.value = []
  }
}

// 分頁輔助函數
const getPageNumbers = () => {
  const pages: (number | string)[] = []
  const total = totalPages.value
  const current = currentPage.value
  
  if (total <= 7) {
    // 如果總頁數不超過7，全部顯示
    for (let i = 1; i <= total; i++) {
      pages.push(i)
    }
  } else {
    // 總是顯示第一頁
    pages.push(1)
    
    if (current > 3) {
      pages.push('...')
    }
    
    // 顯示當前頁附近的頁碼
    const start = Math.max(2, current - 1)
    const end = Math.min(total - 1, current + 1)
    
    for (let i = start; i <= end; i++) {
      pages.push(i)
    }
    
    if (current < total - 2) {
      pages.push('...')
    }
    
    // 總是顯示最後一頁
    pages.push(total)
  }
  
  return pages
}

const editSupplier = (supplier: SupplierAssignment) => {
  editingSupplier.value = supplier
  editForm.value = {
    cmrt_required: supplier.cmrt_required,
    emrt_required: supplier.emrt_required,
    amrt_required: supplier.amrt_required,
    amrt_minerals: supplier.amrt_minerals || []
  }
  showEditModal.value = true
}

const openBatchModal = () => {
  if (selectedSuppliers.value.length === 0) return

  const selectedData = suppliers.value.filter(s => selectedSuppliers.value.includes(s.id))
  if (selectedData.length > 0) {
    const first = selectedData[0]
    
    // 檢查是否所有選中的供應商設定都相同，若不同則預設為 false
    const allCmrtSame = selectedData.every(s => s.cmrt_required === first.cmrt_required)
    const allEmrtSame = selectedData.every(s => s.emrt_required === first.emrt_required)
    const allAmrtSame = selectedData.every(s => s.amrt_required === first.amrt_required)
    
    // 檢查礦產清單是否相同 (轉為字串比較)
    const firstMineralsStr = JSON.stringify([...(first.amrt_minerals || [])].sort())
    const allMineralsSame = selectedData.every(s => 
      JSON.stringify([...(s.amrt_minerals || [])].sort()) === firstMineralsStr
    )

    batchForm.value = {
      cmrt_required: allCmrtSame ? first.cmrt_required : false,
      emrt_required: allEmrtSame ? first.emrt_required : false,
      amrt_required: allAmrtSame ? first.amrt_required : false,
      amrt_minerals: allMineralsSame ? [...(first.amrt_minerals || [])] : []
    }
  }

  showBatchAssignModal.value = true
}

const closeEditModal = () => {
  showEditModal.value = false
  editingSupplier.value = null
}

const closeBatchModal = () => {
  showBatchAssignModal.value = false
  batchForm.value = {
    cmrt_required: false,
    emrt_required: false,
    amrt_required: false,
    amrt_minerals: []
  }
}

const saveTemplateAssignment = async () => {
  if (!editingSupplier.value) return

  try {
    await assignTemplateToSupplier(
      projectId.value,
      editingSupplier.value.id,
      {
        cmrt_required: editForm.value.cmrt_required,
        emrt_required: editForm.value.emrt_required,
        amrt_required: editForm.value.amrt_required,
        amrt_minerals: editForm.value.amrt_required ? editForm.value.amrt_minerals : undefined
      }
    )
    showSuccess('範本設定成功')
    closeEditModal()
    await loadSuppliers()
  } catch (err: any) {
    showError(err.message || '設定失敗')
  }
}

const saveBatchAssignment = async () => {
  try {
    await batchAssignTemplates(
      projectId.value,
      selectedSuppliers.value,
      {
        cmrt_required: batchForm.value.cmrt_required,
        emrt_required: batchForm.value.emrt_required,
        amrt_required: batchForm.value.amrt_required,
        amrt_minerals: batchForm.value.amrt_required ? batchForm.value.amrt_minerals : undefined
      }
    )
    showSuccess(`已為 ${selectedSuppliers.value.length} 個供應商設定範本`)
    closeBatchModal()
    selectedSuppliers.value = []
    selectAll.value = false
    await loadSuppliers()
  } catch (err: any) {
    showError(err.message || '批量設定失敗')
  }
}

const handleExcelImport = () => {
  excelFileInput.value?.click()
}

const handleFileSelected = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  try {
    // 解析 Excel
    const rows = await parseExcel(file)
    
    // 跳過前3筆示例數據（從第4筆開始，索引3）
    // 第0筆是標題行，第1-3筆是示例
    const dataRows = rows.slice(4)
    
    if (dataRows.length === 0) {
      showErrorWithConfirm('Excel 中沒有有效的數據')
      return
    }

    console.log('開始處理匯入，共', dataRows.length, '筆數據')
    console.log('當前項目供應商數量:', suppliers.value.length)

    // 獲取系統中所有供應商（用於匹配）
    const allSuppliers = await fetchAllSuppliers()
    console.log('系統中所有供應商數量:', allSuppliers.length)
    
    // 建立系統供應商映射表（trim 後的名稱 -> 供應商對象）
    const systemSupplierMap = new Map(
      allSuppliers
        .filter(s => s.name) // 先過濾
        .map(s => [s.name.trim(), s]) // 再映射
    )
    
    // 建立項目供應商映射表（trim 後的名稱 -> 供應商對象）
    const projectSupplierMap = new Map(
      suppliers.value
        .filter(s => s.supplierName) // 過濾掉沒有名稱的供應商
        .map(s => [s.supplierName.trim(), s])
    )
    
    // 處理每一筆數據，並去重
    const processedSuppliers = new Map<string, any>() // 供應商名稱 -> 處理資料
    const duplicateSuppliers: string[] = [] // 記錄重複的供應商
    const validationErrors: string[] = [] // 驗證錯誤訊息
    
    for (const row of dataRows) {
      if (!row[0]) continue // 跳過空行
      
      const supplierName = String(row[0] || '').trim()
      if (!supplierName) continue // 跳過空名稱
      
      // 檢查是否重複
      if (processedSuppliers.has(supplierName)) {
        if (!duplicateSuppliers.includes(supplierName)) {
          duplicateSuppliers.push(supplierName)
        }
        console.log(`  -> 跳過重複的供應商: ${supplierName}`)
        continue
      }
      
      const cmrt = String(row[1] || '').toLowerCase()
      const emrt = String(row[2] || '').toLowerCase()
      const amrt = String(row[3] || '').toLowerCase()
      const amrtMinerals = String(row[4] || '').trim()
      
      const cmrt_required = cmrt === 'yes' || cmrt === '是'
      const emrt_required = emrt === 'yes' || emrt === '是'
      const amrt_required = amrt === 'yes' || amrt === '是'
      const minerals = amrtMinerals ? amrtMinerals.split(',').map(m => m.trim()).filter(m => m) : []
      
      // 驗證：如果 AMRT 為 Yes，則必須要有至少一種礦產
      if (amrt_required && (!minerals || minerals.length === 0)) {
        validationErrors.push(`供應商「${supplierName}」的 AMRT 設為 Yes，但未指定任何礦產`)
      }
      
      // 準備範本數據
      const templates: TemplateType & { amrt_minerals?: string[] } = {
        cmrt_required,
        emrt_required,
        amrt_required,
        amrt_minerals: minerals.length > 0 ? minerals : undefined
      }
      
      // 記錄已處理
      processedSuppliers.set(supplierName, { templates })
      
      console.log(`處理供應商: ${supplierName}, CMRT=${cmrt}, EMRT=${emrt}, AMRT=${amrt}, Minerals=${minerals.join(',')}`)
    }
    
    // 如果有驗證錯誤，阻止匯入
    if (validationErrors.length > 0) {
      const errorMessage = `匯入驗證失敗，請修正以下問題：\n\n${validationErrors.join('\n')}\n\n請修正後重新匯入。`
      showErrorWithConfirm(errorMessage, '驗證失敗')
      return
    }
    
    // 分類處理結果
    const updates: any[] = [] // 可以更新的（項目中的供應商）
    const suppliersToAdd: Array<{ name: string; id: number; templates: any }> = [] // 系統中有但項目中沒有的
    const notFoundSuppliers: string[] = [] // 系統中不存在的
    const skippedSuppliers: string[] = [] // 無啟用範本而跳過的
    const existingSuppliersNeedUpdate: string[] = [] // 已存在於項目中且需要更新的供應商
    const existingSuppliersNoChange: string[] = [] // 已存在於項目中但數據相同的供應商
    
    // 輔助函數：比較範本數據是否相同
    const isTemplateDataSame = (existingSupplier: any, newTemplates: any) => {
      // 比較 cmrt, emrt, amrt 是否相同
      if (existingSupplier.cmrt_required !== newTemplates.cmrt_required) return false
      if (existingSupplier.emrt_required !== newTemplates.emrt_required) return false
      if (existingSupplier.amrt_required !== newTemplates.amrt_required) return false
      
      // 比較 amrt_minerals 陣列是否相同
      const existingMinerals = (existingSupplier.amrt_minerals || []).sort()
      const newMinerals = (newTemplates.amrt_minerals || []).sort()
      
      if (existingMinerals.length !== newMinerals.length) return false
      for (let i = 0; i < existingMinerals.length; i++) {
        if (existingMinerals[i] !== newMinerals[i]) return false
      }
      
      return true
    }
    
    for (const [supplierName, data] of processedSuppliers) {
      const { templates } = data
      
      // 檢查是否有任何範本被啟用
      const hasTemplateEnabled = templates.cmrt_required || templates.emrt_required || templates.amrt_required
      
      // 1. 先檢查是否存在於系統中
      const systemSupplier = systemSupplierMap.get(supplierName)
      
      if (!systemSupplier) {
        // 系統中不存在此供應商
        notFoundSuppliers.push(supplierName)
        console.log(`  -> 系統中不存在: ${supplierName}`)
        continue
      }
      
      // 2. 系統中存在，檢查是否在項目中
      const projectSupplier = projectSupplierMap.get(supplierName)
      
      if (projectSupplier) {
        // 在項目中，檢查數據是否相同
        const isSame = isTemplateDataSame(projectSupplier, templates)
        
        if (isSame) {
          // 數據相同，跳過更新
          existingSuppliersNoChange.push(supplierName)
          console.log(`  -> 在項目中，數據相同，跳過: ${supplierName}`)
        } else {
          // 數據不同，記錄為需要更新
          existingSuppliersNeedUpdate.push(supplierName)
          console.log(`  -> 在項目中，數據不同，準備更新: ${supplierName} (ID: ${projectSupplier.id})`)
          updates.push({ supplierId: projectSupplier.id, supplierName, templates })
        }
      } else {
        // 不在項目中
        if (hasTemplateEnabled) {
          // 有啟用範本，準備添加到項目
          console.log(`  -> 系統中有但項目中沒有，準備添加: ${supplierName} (System ID: ${systemSupplier.id})`)
          suppliersToAdd.push({ name: supplierName, id: Number(systemSupplier.id), templates })
        } else {
          // 無啟用範本，跳過
          console.log(`  -> 無啟用範本，跳過: ${supplierName}`)
          skippedSuppliers.push(supplierName)
        }
      }
    }
    
    // 如果有供應商已存在於項目中且數據不同，詢問是否覆蓋
    if (existingSuppliersNeedUpdate.length > 0) {
      const confirmed = await showConfirm({
        title: '覆蓋確認',
        text: `以下 ${existingSuppliersNeedUpdate.length} 個供應商已存在於此項目中，且範本設定不同：\n\n${existingSuppliersNeedUpdate.join(', ')}\n\n確定要覆蓋現有的範本設定嗎？`,
        icon: 'warning',
        confirmButtonText: '覆蓋',
        cancelButtonText: '取消',
        confirmButtonColor: '#f59e0b'
      })
      
      if (!confirmed) {
        console.log('用戶取消了覆蓋操作')
        return
      }
    }

    console.log('準備更新的供應商:', updates.length)
    console.log('準備添加到項目的供應商:', suppliersToAdd.length)
    console.log('系統中不存在的供應商:', notFoundSuppliers.length)
    console.log('重複的供應商:', duplicateSuppliers.length)
    console.log('跳過的供應商（無範本）:', skippedSuppliers.length)
    console.log('跳過的供應商（數據相同）:', existingSuppliersNoChange.length)

    let importCount = 0
    let addedToProjectCount = 0
    
    // 第一步：將系統中存在但項目中沒有的供應商添加到項目
    if (suppliersToAdd.length > 0) {
      try {
        const supplierIdsToAdd = suppliersToAdd.map(s => s.id)
        console.log('正在添加供應商到項目:', supplierIdsToAdd)
        
        const addResult = await addSuppliersToProject(projectId.value, supplierIdsToAdd)
        console.log('添加供應商結果:', addResult)
        
        addedToProjectCount = addResult.data?.added || 0
        
        // 重新載入供應商列表以獲取新的 assignment IDs
        await loadSuppliers()
        
        // 更新 projectSupplierMap 以便後續設定範本
        const updatedProjectSupplierMap = new Map(
          suppliers.value
            .filter(s => s.supplierName)
            .map(s => [s.supplierName.trim(), s])
        )
        
        // 將新添加的供應商加入待更新列表
        for (const supplier of suppliersToAdd) {
          const projectSupplier = updatedProjectSupplierMap.get(supplier.name)
          if (projectSupplier) {
            updates.push({ 
              supplierId: projectSupplier.id, 
              supplierName: supplier.name, 
              templates: supplier.templates 
            })
          }
        }
      } catch (err: any) {
        console.error('添加供應商到項目失敗:', err)
        showError(`添加供應商到項目失敗: ${err.message || '未知錯誤'}`)
        return
      }
    }
    
    // 第二步：執行更新已存在的供應商（包括剛添加的）
    if (updates.length > 0) {
      for (const update of updates) {
        console.log(`更新供應商: ${update.supplierName}, ID: ${update.supplierId}`, update.templates)
        try {
          await assignTemplateToSupplier(projectId.value, update.supplierId, update.templates)
          importCount++
        } catch (err: any) {
          console.error(`更新供應商 ${update.supplierName} 失敗:`, err)
        }
      }
    }
    
    // 構建更清楚的結果訊息
    const messages: string[] = []
    
    // 1. 成功匯入的數量
    if (importCount > 0) {
      messages.push(`成功匯入 ${importCount} 筆供應商範本設定`)
    }
    
    // 2. 新增到項目的供應商
    if (addedToProjectCount > 0) {
      messages.push(`自動新增 ${addedToProjectCount} 個供應商到此項目`)
    }
    
    // 3. 數據相同跳過的供應商
    if (existingSuppliersNoChange.length > 0) {
      messages.push(`\n有 ${existingSuppliersNoChange.length} 個供應商因範本設定相同而跳過更新`)
    }
    
    // 4. 重複跳過的供應商
    if (duplicateSuppliers.length > 0) {
      messages.push(`\nExcel中有 ${duplicateSuppliers.length} 個重複的供應商（已自動跳過重複項）：\n${duplicateSuppliers.join(', ')}`)
    }
    
    // 5. 系統中不存在的供應商
    if (notFoundSuppliers.length > 0) {
      messages.push(`\n有 ${notFoundSuppliers.length} 個供應商在系統中不存在：\n${notFoundSuppliers.join(', ')}\n請先在「會員中心 > 供應商管理」新增這些供應商`)
    }
    
    // 6. 因無啟用範本而跳過的供應商（數量提示即可，不列出名稱）
    if (skippedSuppliers.length > 0) {
      messages.push(`\n有 ${skippedSuppliers.length} 個供應商因未啟用任何範本而跳過`)
    }
    
    const finalMessage = messages.join('\n')
    
    // 顯示結果
    if (importCount > 0 || addedToProjectCount > 0) {
      await loadSuppliers()
      if (notFoundSuppliers.length > 0 || duplicateSuppliers.length > 0) {
        // 有部分問題，顯示帶確認的警告
        showErrorWithConfirm(finalMessage, '匯入結果')
      } else {
        // 全部成功
        showSuccess(finalMessage)
      }
    } else {
      // 沒有成功匯入任何資料
      if (finalMessage) {
        showErrorWithConfirm(finalMessage, '匯入失敗')
      } else {
        showErrorWithConfirm('沒有可以匯入的資料', '匯入失敗')
      }
    }
  } catch (err: any) {
    console.error('匯入錯誤:', err)
    showErrorWithConfirm(err.message || '匯入失敗')
  } finally {
    // 清空 file input
    if (excelFileInput.value) {
      excelFileInput.value.value = ''
    }
  }
}

const downloadTemplate = async () => {
  try {
    // 準備標題行
    const headers = ['供應商名稱', 'CMRT', 'EMRT', 'AMRT', 'AMRT礦產', '備註']
    
    // 準備數據行（前三筆為示例 - 使用英文供應商名稱）
    const exampleRows = [
      ['Example Supplier A', 'Yes', 'Yes', 'No', '', 'This is sample data, will be skipped during import'],
      ['Example Supplier B', 'Yes', 'No', 'Yes', 'Silver,Platinum', 'This is sample data, will be skipped during import'],
      ['Example Supplier C', 'No', 'Yes', 'No', '', 'This is sample data, will be skipped during import']
    ]
    
    // 獲取所有供應商（會員中心的所有供應商）
    const allSuppliers = await fetchAllSuppliers()
    
    // 建立當前專案供應商的映射（用於設定預設值）
    // 使用 trim() 確保名稱匹配
    const projectSupplierMap = new Map(
      suppliers.value
        .filter(s => s.supplierName) // 過濾掉沒有名稱的供應商
        .map(s => [s.supplierName.trim(), s])
    )
    
    // 添加所有供應商數據
    const supplierRows = allSuppliers.map(supplier => {
      // 檢查該供應商是否在當前專案中（使用 trim 後的名稱）
      const supplierName = (supplier.name || '').trim()
      const projectSupplier = projectSupplierMap.get(supplierName)
      
      return [
        supplierName,  // 使用 trim 後的名稱，確保與匯入時一致
        projectSupplier?.cmrt_required ? 'Yes' : 'No',
        projectSupplier?.emrt_required ? 'Yes' : 'No',
        projectSupplier?.amrt_required ? 'Yes' : 'No',
        (projectSupplier?.amrt_minerals && Array.isArray(projectSupplier.amrt_minerals)) 
          ? projectSupplier.amrt_minerals.join(',') 
          : '',
        '' // 備註欄位預設為空
      ]
    })
    
    // 合併示例數據和實際數據
    const allRows = [...exampleRows, ...supplierRows]
    
    // 下載 Excel
    await downloadExcelTemplate(
      `conflict_minerals_suppliers_${projectId.value}`,
      headers,
      allRows
    )
    
    showSuccess('供應商清單下載成功')
  } catch (err: any) {
    showError(err.message || '下載失敗')
  }
}

const notifySupplier = async (supplier: SupplierAssignment) => {
  const confirmed = await showConfirm({
    text: `確定要發送填寫邀請給 ${supplier.supplierName} 嗎？`,
    title: '發送通知'
  })
  if (!confirmed) return

  try {
    await notifySupplierApi(projectId.value, supplier.id)
    showSuccess('通知發送成功')
    await loadSuppliers() // 重新載入以更新狀態
  } catch (err: any) {
    showError(err.message || '通知發送失敗')
  }
}

const handleNotifyAll = async () => {
  const confirmed = await showConfirm({
    text: `確定要發送填寫邀請給所有已指派範本的供應商（${assignedCount.value} 位）嗎？`,
    title: '批量通知'
  })
  if (!confirmed) return

  try {
    await notifyAllSuppliers(projectId.value)
    showSuccess(`已發送通知給 ${assignedCount.value} 位供應商`)
    await loadSuppliers() // 重新載入以更新狀態
  } catch (err: any) {
    showError(err.message || '批量通知失敗')
  }
}

const handleBatchDelete = async () => {
  if (selectedSuppliers.value.length === 0) return

  // 獲取被選中供應商的名稱
  const selectedNames = suppliers.value
    .filter(s => selectedSuppliers.value.includes(s.id))
    .map(s => s.supplierName)
    .join('、')

  const confirmed = await showConfirm({
    title: '批量刪除確認',
    text: `確定要從此專案中移除以下 ${selectedSuppliers.value.length} 個供應商嗎？\n\n${selectedNames}\n\n此操作無法復原。`,
    icon: 'warning',
    confirmButtonText: '確定刪除',
    cancelButtonText: '取消',
    confirmButtonColor: '#dc2626'
  })
  
  if (!confirmed) return

  try {
    await batchDeleteSuppliers(projectId.value, selectedSuppliers.value)
    showSuccess(`已成功移除 ${selectedSuppliers.value.length} 個供應商`)
    selectedSuppliers.value = []
    selectAll.value = false
    await loadSuppliers()
  } catch (err: any) {
    showError(err.message || '批量刪除失敗')
  }
}

const goToAnswer = (supplier: SupplierAssignment) => {
  router.push(`/supplier/projects/${supplier.id}/answer`)
}

onMounted(() => {
  loadSuppliers()
})
</script>

<style scoped>
.rm-suppliers-page {
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

.action-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
}

.action-group {
  display: flex;
  gap: 12px;
}

.filters-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
}

.search-input {
  flex: 1;
  padding: 10px 16px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
}

.filter-select {
  padding: 10px 16px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  min-width: 150px;
}

.suppliers-table-container {
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.suppliers-table {
  width: 100%;
  border-collapse: collapse;
}

.suppliers-table th {
  background: #f8f9fa;
  padding: 12px 16px;
  text-align: left;
  font-weight: 600;
  font-size: 13px;
  color: #495057;
  border-bottom: 2px solid #dee2e6;
}

.suppliers-table td {
  padding: 12px 16px;
  border-bottom: 1px solid #dee2e6;
  font-size: 14px;
}

.suppliers-table tr:hover {
  background: #f8f9fa;
}

.row-selected {
  background: #e7f3ff !important;
}

.checkbox-col {
  width: 40px;
}

.text-center {
  text-align: center !important;
}

.badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}

.badge-success {
  background: #d4edda;
  color: #155724;
}

.badge-gray {
  background: #e9ecef;
  color: #6c757d;
}

.status-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.status-not_assigned {
  background: #f8d7da;
  color: #721c24;
}

.status-assigned {
  background: #d1ecf1;
  color: #0c5460;
}

.status-in_progress {
  background: #fff3cd;
  color: #856404;
}

.status-completed {
  background: #d4edda;
  color: #155724;
}

.minerals-col {
  max-width: 200px;
}

.minerals-list {
  font-size: 12px;
  color: #666;
}

.actions-col {
  width: 120px;
  padding: 8px 12px !important;
  vertical-align: middle;
}

.actions-inner {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
}

.btn-icon {
  padding: 4px;
  border: none;
  background: none;
  cursor: pointer;
  color: #007bff;
  font-size: 1.25rem;
  border-radius: 4px;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}

.btn-icon.btn-fill {
  color: #28a745;
}

.btn-icon.btn-notify {
  color: #fd7e14;
}

.btn-icon:hover {
  background: #f8f9fa;
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

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  background: #007bff;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #0056b3;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-success {
  background: #28a745;
  color: white;
}

.btn-danger {
  background: #dc2626;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: #b91c1c;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.modal-header {
  padding: 20px 24px;
  border-bottom: 1px solid #dee2e6;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.modal-close {
  background: none;
  border: none;
  font-size: 28px;
  cursor: pointer;
  color: #999;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
}

.modal-close:hover {
  background: #f8f9fa;
  color: #333;
}

.modal-body {
  padding: 24px;
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid #dee2e6;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.form-group {
  margin-bottom: 20px;
}

.checkbox-label {
  display: flex;
  align-items: start;
  gap: 12px;
  cursor: pointer;
  padding: 12px;
  border-radius: 6px;
  transition: background 0.2s;
}

.checkbox-label:hover {
  background: #f8f9fa;
}

.checkbox-label input[type="checkbox"] {
  margin-top: 2px;
  flex-shrink: 0;
}

.label-text {
  flex: 1;
}

.form-label {
  display: block;
  font-weight: 600;
  margin-bottom: 12px;
  color: #333;
}

.minerals-selection {
  background: #f8f9fa;
  padding: 16px;
  border-radius: 8px;
}

.minerals-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 8px;
  margin-bottom: 12px;
}

/* 分頁樣式 */
.pagination-container {
  margin-top: 24px;
  padding: 16px;
  background: white;
  border-radius: 8px;
  border: 1px solid #dee2e6;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
}

.pagination-info {
  font-size: 14px;
  color: #666;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 4px;
}

.btn-pagination {
  min-width: 36px;
  height: 36px;
  padding: 0 12px;
  border: 1px solid #dee2e6;
  background: white;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.btn-pagination:hover:not(:disabled) {
  background: #f8f9fa;
  border-color: #007bff;
  color: #007bff;
}

.btn-pagination:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-pagination.active {
  background: #007bff;
  color: white;
  border-color: #007bff;
  font-weight: 600;
}

.page-numbers {
  display: flex;
  align-items: center;
  gap: 4px;
}

.page-ellipsis {
  padding: 0 8px;
  color: #999;
}

.page-size-selector {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
}

.page-size-selector label {
  color: #666;
}

.page-size-selector select {
  padding: 6px 32px 6px 12px;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  background: white;
  cursor: pointer;
  font-size: 14px;
}

.page-size-selector select:focus {
  outline: none;
  border-color: #007bff;
}

.mineral-checkbox {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  font-size: 13px;
}

.mineral-checkbox:hover {
  background: #e7f3ff;
}

.mineral-checkbox input:disabled {
  cursor: not-allowed;
}

.help-text {
  margin: 0;
  font-size: 13px;
  color: #666;
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

.text-muted {
  color: #6c757d;
}
</style>
