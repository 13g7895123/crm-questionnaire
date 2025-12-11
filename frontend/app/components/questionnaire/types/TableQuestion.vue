<template>
  <div class="space-y-3">
    <div class="overflow-x-auto">
      <table class="min-w-full border border-gray-300 rounded-lg">
        <thead class="bg-gray-100">
          <tr>
            <th
              v-for="column in config?.columns"
              :key="column.id"
              class="px-4 py-2 text-left text-sm font-semibold text-gray-700 border-b"
            >
              {{ column.label }}
              <span v-if="column.required" class="text-red-500">*</span>
            </th>
            <th class="px-4 py-2 text-center text-sm font-semibold text-gray-700 border-b w-24">
              操作
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(row, rowIndex) in rows"
            :key="rowIndex"
            class="hover:bg-gray-50"
          >
            <td
              v-for="column in config?.columns"
              :key="column.id"
              class="px-4 py-2 border-b"
            >
              <input
                v-if="column.type === 'text' || !column.type"
                v-model="row[column.id]"
                type="text"
                class="w-full px-2 py-1 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm"
                :required="column.required"
                @input="emitUpdate"
              />
              <input
                v-else-if="column.type === 'number'"
                v-model.number="row[column.id]"
                type="number"
                class="w-full px-2 py-1 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm"
                :required="column.required"
                @input="emitUpdate"
              />
              <input
                v-else-if="column.type === 'date'"
                v-model="row[column.id]"
                type="date"
                class="w-full px-2 py-1 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm"
                :required="column.required"
                @input="emitUpdate"
              />
              <input
                v-else-if="column.type === 'email'"
                v-model="row[column.id]"
                type="email"
                class="w-full px-2 py-1 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm"
                :required="column.required"
                @input="emitUpdate"
              />
            </td>
            <td class="px-4 py-2 border-b text-center">
              <button
                type="button"
                class="text-red-600 hover:text-red-800 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="!canRemoveRow"
                @click="removeRow(rowIndex)"
              >
                刪除
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex items-center justify-between">
      <button
        type="button"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
        :disabled="!canAddRow"
        @click="addRow"
      >
        + 新增列
      </button>
      <p class="text-sm text-gray-600">
        <span v-if="config?.minRows || config?.maxRows">
          需要 {{ config?.minRows || 0 }} - {{ config?.maxRows || '無限制' }} 筆資料，
        </span>
        目前 <span class="font-semibold">{{ rows.length }}</span> 筆
      </p>
    </div>

    <p v-if="validationError" class="text-sm text-red-600 mt-2">
      {{ validationError }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import type { TableConfig, TableAnswer } from '~/types/template-v2'

const props = defineProps<{
  modelValue?: TableAnswer[]
  config?: TableConfig
}>()

const emit = defineEmits<{
  'update:modelValue': [value: TableAnswer[]]
}>()

const rows = ref<TableAnswer[]>(props.modelValue || [])
const validationError = ref<string>('')


const canAddRow = computed(() => {
  if (!props.config?.maxRows) return true
  return rows.value.length < props.config.maxRows
})

const canRemoveRow = computed(() => {
  const minRows = props.config?.minRows || 1
  return rows.value.length > minRows
})

const addRow = () => {
  if (!canAddRow.value) {
    validationError.value = `最多只能新增 ${props.config?.maxRows} 筆資料`
    return
  }

  const newRow: TableAnswer = {}
  props.config?.columns.forEach((col) => {
    newRow[col.id] = ''
  })
  rows.value.push(newRow)
  emitUpdate()
  validationError.value = ''
}

const removeRow = (index: number) => {
  if (!canRemoveRow.value) {
    validationError.value = `至少需要 ${props.config?.minRows} 筆資料`
    return
  }

  rows.value.splice(index, 1)
  emitUpdate()
  validationError.value = ''
}

const emitUpdate = () => {
  emit('update:modelValue', rows.value)
}

// 初始化至少一行
if (rows.value.length === 0 && props.config?.minRows) {
  for (let i = 0; i < Math.min(props.config!.minRows, 1); i++) {
    addRow()
  }
}

watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue && JSON.stringify(newValue) !== JSON.stringify(rows.value)) {
      rows.value = newValue
    }
  },
  { deep: true }
)
</script>
