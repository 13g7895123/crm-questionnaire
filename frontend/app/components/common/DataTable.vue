<template>
  <div class="w-full bg-white shadow-sm rounded-lg border border-gray-200">
    <UTable
      v-model="selectedRows"
      :rows="rows"
      :columns="tableColumns"
      :loading="loading"
      :sort="sort"
      class="w-full"
      :ui="{ wrapper: 'border-b border-gray-200' }"
      @update:sort="onSortChange"
    >
      <!-- Pass through all slots -->
      <template v-for="(_, name) in $slots" #[name]="slotData">
        <slot :name="name" v-bind="slotData" />
      </template>
    </UTable>

    <!-- Footer -->
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 px-4 py-3">
      <!-- Left: Showing Range -->
      <div class="text-sm text-gray-500">
        {{ showingRange }}
      </div>

      <!-- Center: Pagination -->
      <div class="flex items-center gap-2">
        <UButton
          icon="i-heroicons-chevron-double-left"
          color="white"
          variant="ghost"
          size="sm"
          :disabled="localPage === 1"
          :ui="{ rounded: 'rounded-full' }"
          @click="toFirstPage"
        />
        <UPagination
          v-model="localPage"
          :page-count="pagination.limit"
          :total="pagination.total"
          :max="5"
          @update:model-value="onPageChange"
        />
        <UButton
          icon="i-heroicons-chevron-double-right"
          color="white"
          variant="ghost"
          size="sm"
          :disabled="localPage >= totalPages"
          :ui="{ rounded: 'rounded-full' }"
          @click="toLastPage"
        />
      </div>

      <!-- Right: Page Size -->
      <div class="flex items-center gap-2 text-sm text-gray-500">
        <span>{{ $t('table.perPage') }}</span>
        <USelectMenu
          v-model="selectedLimit"
          :options="limitOptions"
          option-attribute="label"
          value-attribute="value"
          class="w-24"
          size="xs"
          @update:model-value="onLimitChange"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

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

watch(() => props.pagination.page, (newVal) => {
  localPage.value = newVal
})

const totalPages = computed(() => {
  if (props.pagination.limit <= 0) return 1
  return Math.ceil(props.pagination.total / props.pagination.limit)
})

const limitOptions = computed(() => {
  const opts = props.pageSizeOptions.map(n => ({ label: n.toString(), value: n }))
  opts.push({ label: t('table.all'), value: -1 })
  return opts
})

const selectedLimit = computed({
  get: () => {
    if (props.pageSizeOptions.includes(props.pagination.limit)) {
      return props.pagination.limit
    }
    if (props.pagination.limit === props.pagination.total && props.pagination.total > 0) {
      return -1
    }
    return -1
  },
  set: (val) => {
    // Handled by onLimitChange
  }
})

const onPageChange = (page: number) => {
  emit('update:page', page)
}

const onLimitChange = (limit: number) => {
  if (limit === -1) {
    emit('update:limit', props.pagination.total)
  } else {
    emit('update:limit', limit)
  }
  emit('update:page', 1)
}

const toFirstPage = () => {
  emit('update:page', 1)
}

const toLastPage = () => {
  emit('update:page', totalPages.value)
}

const showingRange = computed(() => {
  if (props.pagination.total === 0) return t('table.showingRange', { start: 0, end: 0, total: 0 })
  
  const start = (props.pagination.page - 1) * props.pagination.limit + 1
  const end = Math.min(props.pagination.page * props.pagination.limit, props.pagination.total)
  
  const formatNumber = (num: number) => new Intl.NumberFormat().format(num)
  
  return t('table.showingRange', {
    start: formatNumber(start),
    end: formatNumber(end),
    total: formatNumber(props.pagination.total)
  })
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
