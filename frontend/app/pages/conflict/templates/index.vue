<template>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="w-full">
      <div class="flex items-center gap-4 mb-8">
        <UButton
          icon="i-heroicons-arrow-left"
          color="gray"
          variant="ghost"
          to="/conflict/projects"
        />
        <h1 class="text-3xl font-bold text-gray-900">範本組管理</h1>
      </div>

      <!-- Toolbar -->
      <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
        <div class="flex items-center gap-2 w-full sm:w-auto">
          <UButton
            icon="i-heroicons-plus"
            color="primary"
            label="新增範本組"
            @click="openCreateModal"
          />
          <UButton
            icon="i-heroicons-pencil-square"
            color="white"
            label="編輯"
            :disabled="!selected.length || selected.length > 1"
            @click="openEditModal"
          />
          <UButton
            icon="i-heroicons-trash"
            color="white"
            class="text-red-600 hover:bg-red-50"
            label="刪除"
            :disabled="!selected.length"
            @click="handleDelete"
          />
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto">
          <UInput
            v-model="searchQuery"
            icon="i-heroicons-magnifying-glass"
            placeholder="搜尋範本組..."
            class="w-full sm:w-64"
          />
          <UButton
            icon="i-heroicons-arrow-path"
            color="white"
            label="重新整理"
            :loading="loading"
            @click="loadData"
          />
        </div>
      </div>

      <!-- Template Sets Grid -->
      <div v-if="loading" class="flex justify-center py-12">
        <UIcon name="i-heroicons-arrow-path" class="w-8 h-8 animate-spin text-primary-500" />
      </div>

      <div v-else-if="filteredTemplateSets.length === 0" class="text-center py-12 bg-gray-50 rounded-lg">
        <p class="text-gray-500">尚無範本組，請點擊「新增範本組」建立</p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="templateSet in paginatedTemplateSets"
          :key="templateSet.id"
          :class="[
            'border-2 rounded-lg p-6 cursor-pointer transition-all',
            selected.some(s => s.id === templateSet.id)
              ? 'border-primary-500 bg-primary-50'
              : 'border-gray-200 hover:border-gray-300 hover:shadow-md'
          ]"
          @click="toggleSelection(templateSet)"
        >
          <!-- Header -->
          <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-gray-900">{{ templateSet.name }}</h3>
              <p class="text-sm text-gray-500 mt-1">{{ templateSet.year }} 年度</p>
            </div>
            <input
              type="checkbox"
              :checked="selected.some(s => s.id === templateSet.id)"
              @click.stop="toggleSelection(templateSet)"
              class="mt-1"
            />
          </div>

          <!-- Description -->
          <p v-if="templateSet.description" class="text-sm text-gray-600 mb-4">
            {{ templateSet.description }}
          </p>

          <!-- Templates -->
          <div class="space-y-2">
            <div
              v-if="templateSet.templates.cmrt?.enabled"
              class="flex items-center gap-2 text-sm"
            >
              <UBadge color="blue" variant="soft">CMRT</UBadge>
              <span class="text-gray-700">v{{ templateSet.templates.cmrt.version }}</span>
            </div>
            <div
              v-if="templateSet.templates.emrt?.enabled"
              class="flex items-center gap-2 text-sm"
            >
              <UBadge color="green" variant="soft">EMRT</UBadge>
              <span class="text-gray-700">v{{ templateSet.templates.emrt.version }}</span>
            </div>
            <div
              v-if="templateSet.templates.amrt?.enabled"
              class="flex items-center gap-2 text-sm"
            >
              <UBadge color="yellow" variant="soft">AMRT</UBadge>
              <span class="text-gray-700">
                v{{ templateSet.templates.amrt.version }}
                ({{ templateSet.templates.amrt.minerals?.length || 0 }} 種礦產)
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-2 mt-4 pt-4 border-t">
            <UButton
              size="xs"
              color="gray"
              variant="soft"
              icon="i-heroicons-pencil-square"
              @click.stop="openEditModal(templateSet)"
            >
              編輯
            </UButton>
            <UButton
              size="xs"
              color="gray"
              variant="soft"
              icon="i-heroicons-eye"
              @click.stop="viewDetails(templateSet)"
            >
              檢視
            </UButton>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="filteredTemplateSets.length > pagination.limit" class="flex justify-center mt-6">
        <UPagination
          v-model="pagination.page"
          :page-count="pagination.limit"
          :total="filteredTemplateSets.length"
        />
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <UModal v-model="isFormOpen" :ui="{ width: 'sm:max-w-3xl' }">
      <UCard>
        <template #header>
          <h3 class="text-lg font-semibold">
            {{ isEditing ? '編輯範本組' : '新增範本組' }}
          </h3>
        </template>

        <form @submit.prevent="handleSubmit" class="space-y-6">
          <!-- 基本資訊 -->
          <div class="space-y-4">
            <h4 class="font-semibold text-gray-900">基本資訊</h4>
            
            <UFormGroup label="範本組名稱" required>
              <UInput 
                v-model="form.name" 
                placeholder="例如：2025 Q1 責任礦產調查"
              />
            </UFormGroup>

            <UFormGroup label="年份" required>
              <UInput 
                v-model.number="form.year" 
                type="number"
                :min="2020"
                :max="2030"
                placeholder="2025"
              />
            </UFormGroup>

            <UFormGroup label="說明" help="選填">
              <UTextarea 
                v-model="form.description" 
                placeholder="此範本組的用途說明..."
                :rows="2"
              />
            </UFormGroup>
          </div>

          <!-- CMRT 範本 -->
          <div class="border rounded-lg p-4">
            <div class="flex items-center justify-between mb-3">
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  v-model="form.templates.cmrt.enabled"
                />
                <span class="font-semibold text-gray-900">CMRT - Conflict Minerals</span>
              </label>
              <UBadge color="blue" variant="soft">衝突礦產（3TG）</UBadge>
            </div>
            
            <div v-if="form.templates.cmrt.enabled" class="space-y-3">
              <p class="text-sm text-gray-600">
                覆蓋礦產：錫 (Sn)、鉭 (Ta)、鎢 (W)、金 (Au)
              </p>
              <UFormGroup label="版本號" required>
                <UInput 
                  v-model="form.templates.cmrt.version" 
                  placeholder="6.5"
                />
                <template #help>
                  建議使用 6.5
                </template>
              </UFormGroup>
            </div>
          </div>

          <!-- EMRT 範本 -->
          <div class="border rounded-lg p-4">
            <div class="flex items-center justify-between mb-3">
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  v-model="form.templates.emrt.enabled"
                />
                <span class="font-semibold text-gray-900">EMRT - Extended Minerals</span>
              </label>
              <UBadge color="green" variant="soft">擴展礦產</UBadge>
            </div>
            
            <div v-if="form.templates.emrt.enabled" class="space-y-3">
              <p class="text-sm text-gray-600">
                覆蓋礦產：鈷 (Co)、雲母 (Mica)、銅 (Cu)、石墨、鋰 (Li)、鎳 (Ni)
              </p>
              <UFormGroup label="版本號" required>
                <UInput 
                  v-model="form.templates.emrt.version" 
                  placeholder="2.1"
                />
                <template #help>
                  建議使用 2.1
                </template>
              </UFormGroup>
            </div>
          </div>

          <!-- AMRT 範本 -->
          <div class="border rounded-lg p-4">
            <div class="flex items-center justify-between mb-3">
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  v-model="form.templates.amrt.enabled"
                />
                <span class="font-semibold text-gray-900">AMRT - Additional Minerals</span>
              </label>
              <UBadge color="yellow" variant="soft">自選礦產（1-10種）</UBadge>
            </div>
            
            <div v-if="form.templates.amrt.enabled" class="space-y-3">
              <UFormGroup label="版本號" required>
                <UInput 
                  v-model="form.templates.amrt.version" 
                  placeholder="1.21"
                />
                <template #help>
                  建議使用 1.21
                </template>
              </UFormGroup>

              <UFormGroup label="選擇礦產（1-10種）" required>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2 max-h-60 overflow-y-auto p-2 border rounded">
                  <label
                    v-for="mineral in availableMinerals"
                    :key="mineral"
                    class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded cursor-pointer"
                  >
                    <input
                      type="checkbox"
                      :value="mineral"
                      v-model="form.templates.amrt.minerals"
                      :disabled="
                        form.templates.amrt.minerals.length >= 10 &&
                        !form.templates.amrt.minerals.includes(mineral)
                      "
                    />
                    <span class="text-sm">{{ mineral }}</span>
                  </label>
                </div>
                <template #help>
                  已選擇: {{ form.templates.amrt.minerals.length }} / 10
                </template>
              </UFormGroup>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <UButton
              color="gray"
              variant="soft"
              label="取消"
              @click="isFormOpen = false"
            />
            <UButton
              type="submit"
              color="primary"
              label="儲存"
              :loading="loading"
              :disabled="!isFormValid"
            />
          </div>
        </form>
      </UCard>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useTemplateSets, type TemplateSet } from '~/composables/useTemplateSets'
import { useSweetAlert } from '~/composables/useSweetAlert'
import { useBreadcrumbs } from '~/composables/useBreadcrumbs'
import { useI18n } from 'vue-i18n'

definePageMeta({ middleware: 'auth' })

const { t } = useI18n()
const {
  templateSets,
  loading,
  fetchTemplateSets,
  createTemplateSet,
  updateTemplateSet,
  deleteTemplateSet
} = useTemplateSets()
const { showConfirmDialog, showLoading, closeAlert, showSystemAlert } = useSweetAlert()
const { setBreadcrumbs } = useBreadcrumbs()

const searchQuery = ref('')
const selected = ref<TemplateSet[]>([])
const isFormOpen = ref(false)
const editingId = ref<string | null>(null)

// 可用的 AMRT 礦產
const availableMinerals = [
  'Silver', 'Platinum', 'Palladium', 'Rhodium',
  'Aluminum', 'Chromium', 'Vanadium', 'Manganese',
  'Iron', 'Zinc', 'Molybdenum', 'Tellurium',
  'Antimony', 'Bismuth', 'Rare Earth Elements'
]

const form = ref({
  name: '',
  year: new Date().getFullYear(),
  description: '',
  templates: {
    cmrt: {
      enabled: false,
      version: '6.5'
    },
    emrt: {
      enabled: false,
      version: '2.1'
    },
    amrt: {
      enabled: false,
      version: '1.21',
      minerals: [] as string[]
    }
  }
})

const pagination = ref({
  page: 1,
  limit: 9
})

const filteredTemplateSets = computed(() => {
  if (!searchQuery.value) return templateSets.value
  const query = searchQuery.value.toLowerCase()
  return templateSets.value.filter(ts =>
    ts.name.toLowerCase().includes(query) ||
    ts.year.toString().includes(query)
  )
})

const paginatedTemplateSets = computed(() => {
  const start = (pagination.value.page - 1) * pagination.value.limit
  const end = start + pagination.value.limit
  return filteredTemplateSets.value.slice(start, end)
})

const isFormValid = computed(() => {
  if (!form.value.name || !form.value.year) return false
  
  const hasTemplate = form.value.templates.cmrt.enabled ||
    form.value.templates.emrt.enabled ||
    form.value.templates.amrt.enabled
  
  if (!hasTemplate) return false
  
  if (form.value.templates.cmrt.enabled && !form.value.templates.cmrt.version) return false
  if (form.value.templates.emrt.enabled && !form.value.templates.emrt.version) return false
  if (form.value.templates.amrt.enabled) {
    if (!form.value.templates.amrt.version) return false
    if (form.value.templates.amrt.minerals.length === 0) return false
  }
  
  return true
})

const isEditing = computed(() => !!editingId.value)

const loadData = async () => {
  try {
    await fetchTemplateSets()
  } catch (e) {
    console.error('Failed to load template sets:', e)
  }
}

const resetForm = () => {
  form.value = {
    name: '',
    year: new Date().getFullYear(),
    description: '',
    templates: {
      cmrt: { enabled: false, version: '6.5' },
      emrt: { enabled: false, version: '2.1' },
      amrt: { enabled: false, version: '1.21', minerals: [] }
    }
  }
  editingId.value = null
}

const openCreateModal = () => {
  resetForm()
  isFormOpen.value = true
}

const openEditModal = (templateSet?: TemplateSet) => {
  const target = templateSet || selected.value[0]
  if (!target) return
  
  form.value = {
    name: target.name,
    year: target.year,
    description: target.description || '',
    templates: {
      cmrt: {
        enabled: target.templates.cmrt?.enabled || false,
        version: target.templates.cmrt?.version || '6.5'
      },
      emrt: {
        enabled: target.templates.emrt?.enabled || false,
        version: target.templates.emrt?.version || '2.1'
      },
      amrt: {
        enabled: target.templates.amrt?.enabled || false,
        version: target.templates.amrt?.version || '1.21',
        minerals: target.templates.amrt?.minerals || []
      }
    }
  }
  editingId.value = target.id
  isFormOpen.value = true
}

const viewDetails = (templateSet: TemplateSet) => {
  // TODO: 顯示詳細資訊 modal 或導航到詳情頁
  console.log('View details:', templateSet)
}

const toggleSelection = (templateSet: TemplateSet) => {
  const index = selected.value.findIndex(s => s.id === templateSet.id)
  if (index >= 0) {
    selected.value.splice(index, 1)
  } else {
    selected.value.push(templateSet)
  }
}

const handleSubmit = async () => {
  try {
    const templateData = {
      name: form.value.name,
      year: form.value.year,
      description: form.value.description,
      templates: {
        ...(form.value.templates.cmrt.enabled && {
          cmrt: {
            enabled: true,
            version: form.value.templates.cmrt.version
          }
        }),
        ...(form.value.templates.emrt.enabled && {
          emrt: {
            enabled: true,
            version: form.value.templates.emrt.version
          }
        }),
        ...(form.value.templates.amrt.enabled && {
          amrt: {
            enabled: true,
            version: form.value.templates.amrt.version,
            minerals: form.value.templates.amrt.minerals
          }
        })
      }
    }

    if (isEditing.value && editingId.value) {
      await updateTemplateSet(editingId.value, templateData)
    } else {
      await createTemplateSet(templateData)
    }
    
    isFormOpen.value = false
    await loadData()
    showSystemAlert('儲存成功', 'success')
  } catch (error) {
    console.error('Operation failed:', error)
    showSystemAlert('操作失敗', 'error')
  }
}

const handleDelete = async () => {
  if (!selected.value.length) return
  
  const confirmed = await showConfirmDialog('確定要刪除選取的範本組嗎？')
  if (!confirmed) return

  try {
    showLoading()
    await Promise.all(selected.value.map(ts => deleteTemplateSet(ts.id)))
    selected.value = []
    await loadData()
    closeAlert()
    showSystemAlert('刪除成功', 'success')
  } catch (e: any) {
    console.error('Failed to delete template sets:', e)
    closeAlert()
    showSystemAlert(e.message || '刪除失敗', 'error')
  }
}

onMounted(() => {
  setBreadcrumbs([
    { label: '首頁', to: '/' },
    { label: '衝突礦產', to: '/conflict' },
    { label: '專案列表', to: '/conflict/projects' },
    { label: '範本組管理' }
  ])
  loadData()
})
</script>
