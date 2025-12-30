<template>
  <NuxtLayout name="account">
    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('suppliers.management') }}</h1>
          <p class="mt-1 text-sm text-gray-500">管理供應商及其帳戶</p>
        </div>
        <UButton
          icon="i-heroicons-plus"
          color="primary"
          :label="$t('common.add')"
          @click="openCreateModal"
        />
      </div>

      <!-- Filters & Search -->
      <div class="flex items-center gap-2">
        <UInput
          v-model="searchQuery"
          icon="i-heroicons-magnifying-glass"
          :placeholder="$t('common.search')"
          class="w-full sm:w-64"
        />
        <UButton
          icon="i-heroicons-arrow-path"
          color="white"
          variant="ghost"
          :loading="loading"
          @click="fetchSuppliers"
        />
      </div>

      <!-- Table -->
      <DataTable
        v-model="selected"
        :rows="paginatedSuppliers"
        :columns="columns"
        :loading="loading"
        :pagination="pagination"
        show-actions
        @update:page="pagination.page = $event"
        @update:limit="pagination.limit = $event"
      >
        <template #name-data="{ row }">
          <span 
            class="text-primary-600 hover:text-primary-700 cursor-pointer font-medium"
            @click="navigateToMembers(row)"
          >
            {{ row.name }}
          </span>
        </template>

        <template #actions-data="{ row }">
          <div class="flex items-center gap-1 justify-end">
             <UButton
              icon="i-heroicons-users"
              color="gray"
              variant="ghost"
              size="xs"
              :title="$t('users.management')"
              @click.stop="navigateToMembers(row)"
            />
            <UButton
              icon="i-heroicons-pencil-square"
              color="gray"
              variant="ghost"
              size="xs"
              @click.stop="openEditModal(row)"
            />
            <UButton
              icon="i-heroicons-trash"
              color="red"
              variant="ghost"
              size="xs"
              @click.stop="confirmDelete(row)"
            />
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Create/Edit Modal -->
    <UModal v-model="isModalOpen">
      <UCard>
        <template #header>
          <h3 class="text-lg font-semibold">
            {{ isEditing ? $t('common.edit') : $t('common.add') }}
          </h3>
        </template>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <UFormGroup :label="$t('common.name')" required>
            <UInput v-model="form.name" />
          </UFormGroup>

          <div class="flex justify-end gap-3 mt-6">
            <UButton
              color="gray"
              variant="soft"
              :label="$t('common.cancel')"
              @click="isModalOpen = false"
            />
            <UButton
              type="submit"
              color="primary"
              :label="$t('common.save')"
              :loading="loading"
            />
          </div>
        </form>
      </UCard>
    </UModal>
  </NuxtLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useSuppliers } from '~/composables/useSuppliers'
import { useSweetAlert } from '~/composables/useSweetAlert'
import { useBreadcrumbs } from '~/composables/useBreadcrumbs'
import DataTable from '~/components/common/DataTable.vue'
import { useI18n } from 'vue-i18n'
import type { Organization } from '~/types/index'

definePageMeta({ middleware: 'auth' })

const router = useRouter()
const { t } = useI18n()
const { suppliers, loading, fetchSuppliers, createSupplier, updateSupplier, deleteSupplier } = useSuppliers()
const { showConfirmDialog, showSystemAlert, closeAlert, showLoading } = useSweetAlert()
const { setBreadcrumbs } = useBreadcrumbs()

const searchQuery = ref('')
const selected = ref<Organization[]>([])
const isModalOpen = ref(false)
const editingId = ref<string | null>(null)
const form = ref({ name: '' })

const pagination = ref({
  page: 1,
  limit: 10,
  total: 0
})

const columns = computed(() => [
  { key: 'name', label: t('common.name'), sortable: true },
  { key: 'type', label: t('common.type'), sortable: true },
])

// Filtering
const filteredSuppliers = computed(() => {
  if (!searchQuery.value) return suppliers.value
  const query = searchQuery.value.toLowerCase()
  return suppliers.value.filter(s => 
    s.name.toLowerCase().includes(query)
  )
})

// Pagination
const paginatedSuppliers = computed(() => {
  const start = (pagination.value.page - 1) * pagination.value.limit
  const end = start + pagination.value.limit
  return filteredSuppliers.value.slice(start, end)
})

watch(filteredSuppliers, (newVal) => {
  pagination.value.total = newVal.length
  if (pagination.value.page > Math.ceil(newVal.length / pagination.value.limit)) {
    pagination.value.page = 1
  }
}, { immediate: true })

const isEditing = computed(() => !!editingId.value)

const resetForm = () => {
  form.value = { name: '' }
  editingId.value = null
}

const openCreateModal = () => {
  resetForm()
  isModalOpen.value = true
}

const openEditModal = (supplier: Organization) => {
  form.value = { name: supplier.name }
  editingId.value = supplier.id
  isModalOpen.value = true
}

const confirmDelete = async (supplier: Organization) => {
  const confirmed = await showConfirmDialog(t('common.confirmDelete'))
  if (!confirmed) return

  try {
    showLoading()
    await deleteSupplier(supplier.id)
    closeAlert()
    showSystemAlert(t('common.deleteSuccess'), 'success')
  } catch (error: any) {
    closeAlert()
    showSystemAlert(error.message || t('common.deleteFailed'), 'error')
  }
}

const handleSubmit = async () => {
  try {
    if (isEditing.value && editingId.value) {
      await updateSupplier(editingId.value, form.value.name)
    } else {
      await createSupplier(form.value.name)
    }
    isModalOpen.value = false
    showSystemAlert(t('common.success'), 'success')
  } catch (error: any) {
    showSystemAlert(error.message || t('common.error'), 'error')
  }
}

const navigateToMembers = (supplier: Organization) => {
  router.push(`/account/suppliers/${supplier.id}`)
}

onMounted(async () => {
  setBreadcrumbs([
    { label: t('member.account') },
    { label: t('suppliers.management') }
  ])
  await fetchSuppliers()
})
</script>
