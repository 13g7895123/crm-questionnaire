<template>
  <UCard>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          {{ $t('users.management') }}
        </h3>
        <UButton
          icon="i-heroicons-plus"
          color="primary"
          variant="solid"
          :label="$t('users.addUser')"
          @click="openCreateModal"
        />
      </div>
    </template>

    <UTable
      :rows="users"
      :columns="columns"
      :loading="loading"
    >
      <template #role-data="{ row }">
        <UBadge :color="getRoleColor(row.role)" variant="soft">
          {{ row.role }}
        </UBadge>
      </template>

      <template #department-data="{ row }">
        {{ row.department?.name || '-' }}
      </template>

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
            {{ isEditing ? $t('users.editUser') : $t('users.addUser') }}
          </h3>
        </template>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <UFormGroup :label="$t('auth.username')" required>
            <UInput v-model="form.username" :disabled="isEditing" />
          </UFormGroup>

          <UFormGroup :label="$t('auth.email')" required>
            <UInput v-model="form.email" type="email" />
          </UFormGroup>

          <UFormGroup v-if="!isEditing" :label="$t('auth.password')" required>
            <UInput v-model="form.password" type="password" />
          </UFormGroup>

          <UFormGroup :label="$t('common.phone')">
            <UInput v-model="form.phone" />
          </UFormGroup>

          <UFormGroup :label="$t('users.role')" required>
            <USelectMenu
              v-model="form.role"
              :options="['HOST', 'SUPPLIER', 'ADMIN']"
            />
          </UFormGroup>

          <UFormGroup :label="$t('departments.department')" required>
            <USelectMenu
              v-model="form.departmentId"
              :options="departmentOptions"
              option-attribute="label"
              value-attribute="value"
            />
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
        <p>{{ $t('users.confirmDelete') }}</p>
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
import { useUsers } from '~/composables/useUsers'
import { useDepartments } from '~/composables/useDepartments'
import type { User } from '~/types/index'

const { users, loading, fetchUsers, createUser, updateUser, deleteUser } = useUsers()
const { departments, fetchDepartments } = useDepartments()

const isModalOpen = ref(false)
const isDeleteModalOpen = ref(false)
const editingId = ref<string | null>(null)
const userToDelete = ref<User | null>(null)

const form = ref({
  username: '',
  email: '',
  password: '',
  phone: '',
  role: 'SUPPLIER' as 'HOST' | 'SUPPLIER' | 'ADMIN',
  departmentId: ''
})

const isEditing = computed(() => !!editingId.value)

const columns = [
  { key: 'username', label: 'Username' },
  { key: 'email', label: 'Email' },
  { key: 'role', label: 'Role' },
  { key: 'department', label: 'Department' },
  { key: 'actions', label: 'Actions' }
]

const departmentOptions = computed(() => 
  departments.value.map(d => ({
    label: d.name,
    value: d.id
  }))
)

const getRoleColor = (role: string) => {
  switch (role) {
    case 'ADMIN': return 'red'
    case 'HOST': return 'blue'
    case 'SUPPLIER': return 'green'
    default: return 'gray'
  }
}

const resetForm = () => {
  form.value = {
    username: '',
    email: '',
    password: '',
    phone: '',
    role: 'SUPPLIER',
    departmentId: ''
  }
  editingId.value = null
}

const openCreateModal = () => {
  resetForm()
  isModalOpen.value = true
}

const openEditModal = (user: User) => {
  form.value = {
    username: user.username,
    email: user.email,
    password: '', // Password not editable here directly usually
    phone: user.phone,
    role: user.role as any,
    departmentId: user.departmentId
  }
  editingId.value = user.id
  isModalOpen.value = true
}

const confirmDelete = (user: User) => {
  userToDelete.value = user
  isDeleteModalOpen.value = true
}

const handleSubmit = async () => {
  try {
    if (isEditing.value && editingId.value) {
      await updateUser(editingId.value, {
        email: form.value.email,
        phone: form.value.phone,
        role: form.value.role,
        departmentId: form.value.departmentId
      })
    } else {
      await createUser({
        username: form.value.username,
        email: form.value.email,
        password: form.value.password, // Note: API should handle password hashing
        phone: form.value.phone,
        role: form.value.role,
        departmentId: form.value.departmentId,
        organizationId: 'org_host' // Default for now, should be dynamic
      })
    }
    isModalOpen.value = false
    await fetchUsers()
  } catch (error) {
    console.error('Operation failed:', error)
  }
}

const handleDelete = async () => {
  if (!userToDelete.value) return
  try {
    await deleteUser(userToDelete.value.id)
    isDeleteModalOpen.value = false
    userToDelete.value = null
  } catch (error) {
    console.error('Delete failed:', error)
  }
}

onMounted(async () => {
  await Promise.all([
    fetchUsers(),
    fetchDepartments()
  ])
})
</script>
