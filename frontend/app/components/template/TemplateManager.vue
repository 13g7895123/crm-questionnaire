<template>
  <UModal v-model="isOpen" :ui="{ width: 'sm:max-w-4xl' }">
    <UCard>
      <template #header>
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ $t('templates.templates') }}
          </h3>
          <div class="flex gap-2">
            <UButton
              icon="i-heroicons-plus"
              color="primary"
              variant="solid"
              :label="$t('templates.createTemplate')"
              @click="openCreateModal"
            />
            <UButton
              color="gray"
              variant="ghost"
              icon="i-heroicons-x-mark"
              @click="closeModal"
            />
          </div>
        </div>
      </template>

      <UTable
        :rows="templates"
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

      <!-- Create/Edit Sub-Modal -->
      <UModal v-model="isFormOpen">
        <UCard>
          <template #header>
            <h3 class="text-lg font-semibold">
              {{ isEditing ? $t('templates.editTemplate') : $t('templates.createTemplate') }}
            </h3>
          </template>

          <form @submit.prevent="handleSubmit" class="space-y-4">
            <UFormGroup :label="$t('templates.templateName')" required>
              <UInput v-model="form.name" />
            </UFormGroup>

            <div class="flex justify-end gap-3 mt-6">
              <UButton
                color="gray"
                variant="soft"
                :label="$t('common.cancel')"
                @click="isFormOpen = false"
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

      <!-- Delete Confirmation Sub-Modal -->
      <UModal v-model="isDeleteModalOpen">
        <UCard>
          <template #header>
            <h3 class="text-lg font-semibold text-red-600">{{ $t('common.warning') }}</h3>
          </template>
          <p>{{ $t('common.confirmDelete') }}</p>
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
  </UModal>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useTemplates } from '~/composables/useTemplates'
import type { Template } from '~/types/index'

const props = defineProps<{
  modelValue: boolean
  type: 'SAQ' | 'CONFLICT'
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
}>()

const { templates, loading, fetchTemplates, createTemplate, updateTemplate, deleteTemplate } = useTemplates()

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const isFormOpen = ref(false)
const isDeleteModalOpen = ref(false)
const editingId = ref<string | null>(null)
const templateToDelete = ref<Template | null>(null)

const form = ref({
  name: ''
})

const isEditing = computed(() => !!editingId.value)

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'latestVersion', label: 'Latest Version' },
  { key: 'actions', label: 'Actions' }
]

watch(isOpen, async (val) => {
  if (val) {
    await fetchTemplates(props.type)
  }
})

const closeModal = () => {
  isOpen.value = false
}

const resetForm = () => {
  form.value = {
    name: ''
  }
  editingId.value = null
}

const openCreateModal = () => {
  resetForm()
  isFormOpen.value = true
}

const openEditModal = (template: Template) => {
  form.value = {
    name: template.name
  }
  editingId.value = template.id
  isFormOpen.value = true
}

const confirmDelete = (template: Template) => {
  templateToDelete.value = template
  isDeleteModalOpen.value = true
}

const handleSubmit = async () => {
  try {
    if (isEditing.value && editingId.value) {
      await updateTemplate(editingId.value, { name: form.value.name })
    } else {
      await createTemplate({
        name: form.value.name,
        type: props.type
      })
    }
    isFormOpen.value = false
    await fetchTemplates(props.type)
  } catch (error) {
    console.error('Operation failed:', error)
  }
}

const handleDelete = async () => {
  if (!templateToDelete.value) return
  try {
    await deleteTemplate(templateToDelete.value.id)
    isDeleteModalOpen.value = false
    templateToDelete.value = null
  } catch (error) {
    console.error('Delete failed:', error)
  }
}
</script>
