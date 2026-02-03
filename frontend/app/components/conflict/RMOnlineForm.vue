<script setup lang="ts">
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { useResponsibleMinerals } from '~/composables/useResponsibleMinerals'
import { useSweetAlert } from '~/composables/useSweetAlert'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  assignmentId: number | string
  assignment: any
  type: string
  initialData?: any
}>()

const emit = defineEmits(['back', 'saved', 'submitted'])

const { submitQuestionnaire, saveQuestionnaire } = useResponsibleMinerals()
const { showSuccess, showError, showConfirm } = useSweetAlert()
const { t } = useI18n()

const tabs = computed(() => [
  { label: t('conflict.declaration'), slot: 'declaration' },
  { label: t('conflict.smelterList'), slot: 'smelters' }
])

const formData = reactive({
  declaration: {
    companyName: '',
    companyCountry: '',
    declarationScope: 'Company'
  },
  mineralDeclaration: <Record<string, any>>{},
  smelterList: <any[]>[]
})

// 初始化礦產宣告資料結構
const initMineralDeclaration = () => {
  activeMinerals.value.forEach(m => {
    if (!formData.mineralDeclaration[m]) {
      formData.mineralDeclaration[m] = { used: 'No' }
    }
  })
}

const smelterColumns = [
  { key: 'metal_type', label: t('common.type') },
  { key: 'smelter_name', label: t('common.name') },
  { key: 'smelter_id', label: 'ID' },
  { key: 'smelter_country', label: 'Country' },
  { key: 'actions', label: '' }
]

const addSmelter = () => {
  formData.smelterList.push({
    metal_type: activeMinerals.value[0] || '',
    smelter_name: '',
    smelter_id: '',
    smelter_country: '',
    source_of_smelter_id: 'User Entry'
  })
}

const removeSmelter = (index: number) => {
  formData.smelterList.splice(index, 1)
}

const handleSave = async () => {
  saving.value = true
  try {
    const payload = {
      template_type: props.type,
      declaration: formData.declaration,
      mineralDeclaration: formData.mineralDeclaration,
      smelterList: formData.smelterList
    }
    
    await saveQuestionnaire(Number(props.assignmentId), payload)
    showSuccess(t('questionnaire.saveDraftSuccess'))
    emit('saved')
  } catch (err: any) {
    showError(err.message || t('questionnaire.saveFailed'))
  } finally {
    saving.value = false
  }
}

const handleSubmit = async () => {
  const confirm = await showConfirm(t('questionnaire.confirmSubmit'))
  if (!confirm.isConfirmed) return

  submitting.value = true
  try {
    const payload = {
      template_type: props.type,
      declaration: formData.declaration,
      mineralDeclaration: formData.mineralDeclaration,
      smelterList: formData.smelterList
    }
    await saveQuestionnaire(Number(props.assignmentId), payload)

    await submitQuestionnaire(Number(props.assignmentId), props.type)
    showSuccess(t('questionnaire.submitSuccess'))
    emit('submitted')
  } catch (err: any) {
    showError(err.message || t('questionnaire.submitFailed'))
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  initMineralDeclaration()
  if (props.initialData) {
    if (props.initialData.declaration) {
      Object.assign(formData.declaration, props.initialData.declaration)
    }
    if (props.initialData.mineralDeclaration) {
      Object.assign(formData.mineralDeclaration, props.initialData.mineralDeclaration)
    }
    formData.smelterList = props.initialData.smelters || []
  }
})

const saving = ref(false)
const submitting = ref(false)

/**
 * 根據範本類型決定顯示的礦產
 */
const activeMinerals = computed(() => {
  if (props.type === 'CMRT') {
    return ['Tin', 'Tantalum', 'Tungsten', 'Gold']
  } else if (props.type === 'EMRT') {
    return ['Cobalt', 'Mica']
  } else if (props.type === 'AMRT') {
    return props.assignment?.amrt_minerals || []
  }
  return []
})
</script>

<template>
  <div class="rm-online-form space-y-6 relative pb-20">
    <!-- Sticky Header -->
    <div class="sticky top-0 z-20 bg-white/80 backdrop-blur-md border-b border-gray-100 py-4 -mx-4 px-4 sm:-mx-6 sm:px-6">
      <div class="max-w-7xl mx-auto flex items-center justify-between">
        <div class="flex items-center gap-3">
          <UButton
            icon="i-heroicons-chevron-left"
            color="gray"
            variant="ghost"
            class="rounded-full"
            @click="$emit('back')"
          />
          <div>
            <h1 class="text-xl font-bold text-gray-900 leading-tight">
              {{ $t('conflict.onlineForm') }}
              <span class="text-primary-500 ml-1">[{{ type }}]</span>
            </h1>
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">Manual Entry Portal</p>
          </div>
        </div>
        
        <div class="flex items-center gap-2">
          <UButton
            color="gray"
            variant="soft"
            icon="i-heroicons-archive-box"
            :loading="saving"
            @click="handleSave"
          >
            {{ $t('questionnaire.save') }}
          </UButton>
          <UButton
            color="primary"
            variant="solid"
            icon="i-heroicons-paper-airplane"
            :loading="submitting"
            @click="handleSubmit"
          >
            {{ $t('common.submit') }}
          </UButton>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto">
      <UTabs :items="tabs" class="w-full" :ui="{ wrapper: 'space-y-6', list: { base: 'max-w-xs' } }">
        <!-- 宣告內容標籤頁 -->
        <template #declaration>
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-slide-up">
            <!-- 左側：公司資訊 -->
            <UCard class="lg:col-span-1 border-none shadow-sm ring-1 ring-gray-100 h-fit">
              <template #header>
                <div class="flex items-center gap-2 text-primary-600">
                  <UIcon name="i-heroicons-building-office-2" class="w-5 h-5" />
                  <h3 class="font-bold">{{ $t('projects.basicInfo') }}</h3>
                </div>
              </template>
              
              <div class="space-y-4">
                <UFormGroup :label="$t('suppliers.supplierName')" required>
                  <UInput v-model="formData.declaration.companyName" icon="i-heroicons-user" />
                </UFormGroup>
                
                <UFormGroup :label="$t('common.country')" required>
                  <UInput v-model="formData.declaration.companyCountry" icon="i-heroicons-globe-alt" placeholder="Taiwan" />
                </UFormGroup>
                
                <UFormGroup :label="$t('conflict.declarationScope')" required>
                  <USelectMenu
                    v-model="formData.declaration.declarationScope"
                    :options="['Company', 'Product (or List of Products)', 'User Defined']"
                  />
                </UFormGroup>
              </div>
            </UCard>

            <!-- 右側：礦產宣告 -->
            <UCard class="lg:col-span-2 border-none shadow-sm ring-1 ring-gray-100">
              <template #header>
                <div class="flex items-center gap-2 text-orange-500">
                  <UIcon name="i-heroicons-beaker" class="w-5 h-5" />
                  <h3 class="font-bold">{{ $t('conflict.mineralDeclaration') }}</h3>
                </div>
              </template>
              
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div 
                  v-for="mineral in activeMinerals" 
                  :key="mineral" 
                  class="group p-4 rounded-xl border-2 transition-all duration-200"
                  :class="[
                    formData.mineralDeclaration[mineral]?.used === 'Yes' 
                    ? 'border-primary-100 bg-primary-50/30' 
                    : 'border-gray-50 bg-white hover:border-gray-200'
                  ]"
                >
                  <div class="flex items-center justify-between mb-3">
                    <span class="font-bold text-gray-800 group-hover:text-primary-600 transition-colors">{{ mineral }}</span>
                    <UIcon 
                      v-if="formData.mineralDeclaration[mineral]?.used === 'Yes'" 
                      name="i-heroicons-check-badge" 
                      class="text-primary-500 w-5 h-5" 
                    />
                  </div>
                  
                  <URadioGroup
                    v-if="formData.mineralDeclaration[mineral]"
                    v-model="formData.mineralDeclaration[mineral].used"
                    :options="[{ label: 'Uses this mineral', value: 'Yes' }, { label: 'Not used', value: 'No' }]"
                    class="space-y-1"
                    :ui="{ label: 'text-xs font-semibold' }"
                  />
                </div>
              </div>

              <div class="mt-8 p-4 bg-blue-50/50 rounded-lg flex gap-3">
                <UIcon name="i-heroicons-light-bulb" class="w-5 h-5 text-blue-500 flex-shrink-0" />
                <p class="text-xs text-blue-700 leading-relaxed font-medium">
                  {{ $t('conflict.declarationNote') || '請務必核實每項礦產的使用情況。若選擇 Yes，請確保在下一個標籤頁中提供對應的冶煉廠清單。' }}
                </p>
              </div>
            </UCard>
          </div>
        </template>

        <!-- 冶煉廠列表標籤頁 -->
        <template #smelters>
          <UCard class="border-none shadow-sm ring-1 ring-gray-100 animate-slide-up">
            <template #header>
              <div class="flex justify-between items-center">
                <div class="flex items-center gap-2 text-green-600">
                  <UIcon name="i-heroicons-list-bullet" class="w-5 h-5" />
                  <h3 class="font-bold">{{ $t('conflict.smelterList') }}</h3>
                </div>
                <UButton
                  icon="i-heroicons-plus"
                  size="sm"
                  color="primary"
                  @click="addSmelter"
                >
                  {{ $t('common.add') }}
                </UButton>
              </div>
            </template>

            <div class="overflow-x-auto">
              <UTable
                :columns="smelterColumns"
                :rows="formData.smelterList"
                :ui="{ 
                  td: { base: 'py-2 px-3' },
                  th: { base: 'text-[10px] font-bold text-gray-400 uppercase tracking-widest' }
                }"
              >
                <!-- 金屬類型 -->
                <template #metal_type-data="{ index }">
                  <USelectMenu 
                    v-model="formData.smelterList[index].metal_type" 
                    :options="activeMinerals" 
                    size="xs" 
                    class="w-32"
                  />
                </template>
                
                <!-- 名稱 -->
                <template #smelter_name-data="{ index }">
                  <UInput 
                    v-model="formData.smelterList[index].smelter_name" 
                    size="xs" 
                    :ui="{ base: 'border-b focus-within:border-primary-500 rounded-none w-full' }"
                    placeholder="e.g. Acme Smelting Corp"
                  />
                </template>
                
                <!-- ID -->
                <template #smelter_id-data="{ index }">
                  <UInput 
                    v-model="formData.smelterList[index].smelter_id" 
                    size="xs" 
                    :ui="{ base: 'border-b focus-within:border-primary-500 rounded-none w-full font-mono' }"
                    placeholder="CID001234"
                  />
                </template>
                
                <!-- 國家 -->
                <template #smelter_country-data="{ index }">
                  <UInput 
                    v-model="formData.smelterList[index].smelter_country" 
                    size="xs" 
                    :ui="{ base: 'border-b focus-within:border-primary-500 rounded-none w-full' }"
                    placeholder="China"
                  />
                </template>
                
                <!-- 操作 -->
                <template #actions-data="{ index }">
                  <UButton
                    icon="i-heroicons-trash"
                    color="red"
                    variant="ghost"
                    size="xs"
                    class="hover:bg-red-50"
                    @click="removeSmelter(index)"
                  />
                </template>
              </UTable>
            </div>

            <!-- Empty State -->
            <div v-if="formData.smelterList.length === 0" class="flex flex-col items-center justify-center py-16 bg-gray-50/50 rounded-xl my-4 border border-dashed border-gray-200">
              <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-sm mb-3">
                <UIcon name="i-heroicons-squares-plus" class="w-6 h-6 text-gray-300" />
              </div>
              <p class="text-sm font-bold text-gray-400 uppercase tracking-widest">{{ $t('common.noData') }}</p>
              <UButton
                variant="link"
                color="primary"
                size="xs"
                class="mt-1"
                @click="addSmelter"
              >
                {{ $t('common.addFirstSmelter') || '點擊新增第一筆冶煉廠資料' }}
              </UButton>
            </div>
          </UCard>
        </template>
      </UTabs>
    </div>
  </div>
</template>
