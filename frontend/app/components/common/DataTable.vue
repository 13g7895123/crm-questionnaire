<template>
  <div class="flex flex-col gap-4">
    <UTable
      v-model="selectedRows"
      :rows="rows"
      :columns="tableColumns"
      :loading="loading"
      :sort="sort"
      class="w-full bg-white shadow-sm rounded-lg border border-gray-200"
      @update:sort="onSortChange"
    >
      <!-- Pass through all slots -->
      <template v-for="(_, name) in $slots" #[name]="slotData">
        <slot :name="name" v-bind="slotData" />
      </template>
    </UTable>

    <!-- Footer -->
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 px-4 py-3 border-t border-gray-200 bg-white rounded-lg shadow-sm">
      <div class="flex items-center gap-2 text-sm text-gray-500">
        <span>{{ $t('table.show') }}</span>
        <USelectMenu
          v-model="localLimit"
          :options="pageSizeOptions"
          class="w-20"
          size="xs"
          @update:model-value="onLimitChange"
        />
        <span>{{ $t('table.entries') }}</span>
        <span class="ml-2 hidden sm:inline">
          {{ showingRange }}
        </span>
      </div>

      <UPagination
        v-model="localPage"
        :page-count="pagination.limit"
        :total="pagination.total"
        :max="5"
        @update:model-value="onPageChange"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'

interface Pagination {
  page: number
  limit: number
  total: number
}

interface Sort {
  column: string
  direction: 'asc' | 'desc'
}

interface Column {
  key: string
  label: string
  sortable?: boolean
  [key: string]: any
}

const props = withDefaults(defineProps<{
  rows: any[]
  columns: Column[]
  loading?: boolean
  selectable?: boolean
  modelValue?: any[] // Selected rows
  pagination: Pagination
  sort?: Sort
  actionsPosition?: 'left' | 'right'
  showActions?: boolean
  pageSizeOptions?: number[]
}>(), {
  loading: false,
  selectable: false,
  modelValue: () => [],
  sort: undefined,
  actionsPosition: 'right',
  showActions: false,
  pageSizeOptions: () => [10, 20, 50, 100]
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: any[]): void
  (e: 'update:page', page: number): void
  (e: 'update:limit', limit: number): void
  (e: 'update:sort', sort: Sort): void
}>()

// Selection
const selectedRows = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

// Pagination
const localPage = ref(props.pagination.page)
const localLimit = ref(props.pagination.limit)

watch(() => props.pagination.page, (newVal) => {
  localPage.value = newVal
})

watch(() => props.pagination.limit, (newVal) => {
  localLimit.value = newVal
})

const onPageChange = (page: number) => {
  emit('update:page', page)
}

const onLimitChange = (limit: number) => {
  emit('update:limit', limit)
  // Reset to page 1 when limit changes usually
  emit('update:page', 1)
}

// i18n
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

const showingRange = computed(() => {
  const start = (props.pagination.page - 1) * props.pagination.limit + 1
  const end = Math.min(props.pagination.page * props.pagination.limit, props.pagination.total)
  return `${start}-${end} ${t('common.of')} ${props.pagination.total}`
})

// Sorting
const onSortChange = (sort: Sort) => {
  emit('update:sort', sort)
}

// Columns
const tableColumns = computed(() => {
  let cols = [...props.columns]
  
  if (props.showActions) {
    const actionCol = {
      key: 'actions',
      label: '',
      sortable: false,
      class: 'w-20'
    }
    
    if (props.actionsPosition === 'left') {
      cols.unshift(actionCol)
    } else {
      cols.push(actionCol)
    }
  }
  
  return cols
})

</script>
