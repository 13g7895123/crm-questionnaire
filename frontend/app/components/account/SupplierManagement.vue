<template>
  <UCard>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          {{ $t('suppliers.management') }}
        </h3>
        <UButton
          icon="i-heroicons-plus"
          color="primary"
          variant="solid"
          :label="$t('suppliers.addSupplier')"
          @click="openCreateModal"
        />
      </div>
    </template>

    <UTable
      :rows="suppliers"
      :columns="columns"
      :loading="loading"
    >
      <template #actions-data="{ row }">
        <div class="flex gap-2">
          <UButton
            icon="i-heroicons-pencil-square"
            color="gray"
            variant="ghost"
            size="xs"
            @click="openEditModal(row)"
          />
          <UButton
            icon="i-heroicons-trash"
            color="red"
            variant="ghost"
            size="xs"
            @click="confirmDelete(row)"
          />
        </div>
      </template>
    </UTable>

    <!-- Create/Edit Modal -->
    <UModal v-model="isModalOpen">
      <UCard>
        <template #header>
          <h3 class="text-lg font-semibold">
            {{ isEditing ? $t('suppliers.editSupplier') : $t('suppliers.addSupplier') }}
          </h3>
        </template>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <UFormGroup :label="$t('suppliers.supplierName')" required>
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

    <!-- Delete Confirmation Modal -->
    <UModal v-model="isDeleteModalOpen">
      <UCard>
        <template #header>
          <h3 class="text-lg font-semibold text-red-600">{{ $t('common.warning') }}</h3>
        </template>
        <p>{{ $t('suppliers.confirmDelete') }}</p>
        <template #footer>
          <div class="flex justify-end gap-3">
            <UButton
              color="gray"
              variant="soft"
              :label="$t('common.cancel')"
              @click="isDeleteModalOpen = false"
            />
            <UButton
              color="red"
              :label="$t('common.delete')"
              :loading="loading"
              @click="handleDelete"
            />
          </div>
        </template>
      </UCard>
    </UModal>
  </UCard>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useSuppliers } from '~/composables/useSuppliers'
import type { Organization } from '~/types/index'

const { suppliers, loading, fetchSuppliers, createSupplier, updateSupplier, deleteSupplier } = useSuppliers()

const isModalOpen = ref(false)
const isDeleteModalOpen = ref(false)
const editingId = ref<string | null>(null)
const supplierToDelete = ref<Organization | null>(null)

const form = ref({
  name: ''
})

const isEditing = computed(() => !!editingId.value)

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'createdAt', label: 'Created At' },
  { key: 'actions', label: 'Actions' }
]

const resetForm = () => {
  form.value = {
    name: ''
  }
  editingId.value = null
}

const openCreateModal = () => {
  resetForm()
  isModalOpen.value = true
}

const openEditModal = (supplier: Organization) => {
  form.value = {
    name: supplier.name
  }
  editingId.value = supplier.id
  isModalOpen.value = true
}

const confirmDelete = (supplier: Organization) => {
  supplierToDelete.value = supplier
  isDeleteModalOpen.value = true
}

const handleSubmit = async () => {
  try {
    if (isEditing.value && editingId.value) {
      await updateSupplier(editingId.value, form.value.name)
    } else {
      await createSupplier(form.value.name)
    }
    isModalOpen.value = false
    await fetchSuppliers()
  } catch (error) {
    console.error('Operation failed:', error)
  }
}

const handleDelete = async () => {
  if (!supplierToDelete.value) return
  try {
    await deleteSupplier(supplierToDelete.value.id)
    isDeleteModalOpen.value = false
    supplierToDelete.value = null
  } catch (error) {
    console.error('Delete failed:', error)
  }
}

onMounted(async () => {
  await fetchSuppliers()
})
</script>
