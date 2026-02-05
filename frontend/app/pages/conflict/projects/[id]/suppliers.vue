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
            v-for="supplier in filteredSuppliers"
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
            <td class="supplier-name">{{ supplier.supplier_name }}</td>
            <td class="supplier-email">{{ supplier.supplier_email }}</td>
            <td class="text-center">
              <span v-if="supplier.cmrt_required" class="badge badge-success">✓</span>
              <span v-else class="badge badge-gray">✗</span>
            </td>
            <td class="text-center">
              <span v-if="supplier.emrt_required" class="badge badge-success">✓</span>
              <span v-else class="badge badge-gray">✗</span>
            </td>
            <td class="text-center">
              <span v-if="supplier.amrt_required" class="badge badge-success">✓</span>
              <span v-else class="badge badge-gray">✗</span>
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

      <div v-if="filteredSuppliers.length === 0" class="empty-state">
        <p>沒有符合條件的供應商</p>
      </div>
    </div>

    <!-- 編輯供應商範本 Modal -->
    <div v-if="showEditModal" class="modal-overlay" @click="closeEditModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>設定範本 - {{ editingSupplier?.supplier_name }}</h2>
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

const route = useRoute()
const router = useRouter()
const { showSuccess, showError, showConfirm } = useSweetAlert()
const { parseExcel, downloadTemplate: downloadExcelTemplate } = useExcel()
const {
  suppliers,
  fetchSuppliersWithTemplates,
  assignTemplateToSupplier,
  batchAssignTemplates,
  importTemplateAssignments,
  notifySupplier: notifySupplierApi,
  notifyAllSuppliers,
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
      (s.supplier_name?.toLowerCase().includes(query)) ||
      (s.supplier_email?.toLowerCase().includes(query))
    )
  }

  // 狀態過濾
  if (statusFilter.value === 'assigned') {
    result = result.filter(s => isAssigned(s))
  } else if (statusFilter.value === 'not_assigned') {
    result = result.filter(s => !isAssigned(s))
  }

  return result
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
    selectedSuppliers.value = filteredSuppliers.value.map(s => s.id)
  } else {
    selectedSuppliers.value = []
  }
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
      showError('Excel 中沒有有效的數據')
      return
    }

    // 處理每一筆數據
    const updates: any[] = []
    for (const row of dataRows) {
      if (!row[0]) continue // 跳過空行
      
      const supplierName = String(row[0] || '').trim()
      const cmrt = String(row[1] || '').toLowerCase()
      const emrt = String(row[2] || '').toLowerCase()
      const amrt = String(row[3] || '').toLowerCase()
      const amrtMinerals = String(row[4] || '').trim()
      const remark = String(row[5] || '').trim()
      
      // 查找供應商
      const supplier = suppliers.value.find(s => s.supplier_name === supplierName)
      if (!supplier) {
        console.warn(`找不到供應商: ${supplierName}`)
        continue
      }

      // 準備更新數據
      const templates: TemplateType & { amrt_minerals?: string[] } = {
        cmrt_required: cmrt === 'yes' || cmrt === '是',
        emrt_required: emrt === 'yes' || emrt === '是',
        amrt_required: amrt === 'yes' || amrt === '是',
        amrt_minerals: amrtMinerals ? amrtMinerals.split(',').map(m => m.trim()).filter(m => m) : undefined
      }
      
      updates.push({ supplierId: supplier.id, templates })
    }

    // 執行批量更新
    if (updates.length > 0) {
      for (const update of updates) {
        await assignTemplateToSupplier(projectId.value, update.supplierId, update.templates)
      }
      showSuccess(`成功匯入 ${updates.length} 筆供應商範本設定`)
      await loadSuppliers()
    } else {
      showError('沒有找到匹配的供應商')
    }
  } catch (err: any) {
    showError(err.message || '匯入失敗')
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
    
    // 準備數據行（前三筆為示例）
    const exampleRows = [
      ['範例供應商A', 'Yes', 'Yes', 'No', '', '這是示例數據，匯入時會自動跳過'],
      ['範例供應商B', 'Yes', 'No', 'Yes', 'Silver,Platinum', '這是示例數據，匯入時會自動跳過'],
      ['範例供應商C', 'No', 'Yes', 'No', '', '這是示例數據，匯入時會自動跳過']
    ]
    
    // 添加現有供應商數據
    const supplierRows = suppliers.value.map(s => [
      s.supplier_name,
      s.cmrt_required ? 'Yes' : 'No',
      s.emrt_required ? 'Yes' : 'No',
      s.amrt_required ? 'Yes' : 'No',
      (s.amrt_minerals && Array.isArray(s.amrt_minerals)) ? s.amrt_minerals.join(',') : '',
      '' // 備註欄位預設為空
    ])
    
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
  const confirmed = await showConfirm(
    `確定要發送填寫邀請給 ${supplier.supplier_name} 嗎？`,
    '發送通知'
  )
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
  const confirmed = await showConfirm(
    `確定要發送填寫邀請給所有已指派範本的供應商（${assignedCount.value} 位）嗎？`,
    '批量通知'
  )
  if (!confirmed) return

  try {
    await notifyAllSuppliers(projectId.value)
    showSuccess(`已發送通知給 ${assignedCount.value} 位供應商`)
    await loadSuppliers() // 重新載入以更新狀態
  } catch (err: any) {
    showError(err.message || '批量通知失敗')
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
