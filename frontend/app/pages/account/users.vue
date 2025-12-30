<template>
  <NuxtLayout name="account">
    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('users.management') }}</h1>
          <p class="mt-1 text-sm text-gray-500">管理所有系統使用者帳戶</p>
        </div>
        <UButton
          icon="i-heroicons-plus"
          color="primary"
          :label="$t('users.addUser')"
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
          @click="fetchUsers"
        />
      </div>

      <!-- Table -->
      <DataTable
        v-model="selected"
        :rows="paginatedUsers"
        :columns="columns"
        :loading="loading"
        :pagination="pagination"
        show-actions
        @update:page="pagination.page = $event"
        @update:limit="pagination.limit = $event"
      >
        <template #role-data="{ row }">
          <UBadge :color="getRoleColor(row.role)" variant="subtle">
            {{ row.role }}
          </UBadge>
        </template>

        <template #department-data="{ row }">
          {{ row.department?.name || '-' }}
        </template>

        <template #actions-data="{ row }">
          <div class="flex items-center gap-1 justify-end">
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
  </NuxtLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useUsers } from '~/composables/useUsers'
import { useDepartments } from '~/composables/useDepartments'
import { useSweetAlert } from '~/composables/useSweetAlert'
import { useBreadcrumbs } from '~/composables/useBreadcrumbs'
import DataTable from '~/components/common/DataTable.vue'
import { useI18n } from 'vue-i18n'
import type { User } from '~/types/index'

definePageMeta({ middleware: 'auth' })

const { t } = useI18n()
const { users, loading, fetchUsers, createUser, updateUser, deleteUser } = useUsers()
const { departments, fetchDepartments } = useDepartments()
const { showConfirmDialog, showSystemAlert, closeAlert, showLoading } = useSweetAlert()
const { setBreadcrumbs } = useBreadcrumbs()

const searchQuery = ref('')
const selected = ref<User[]>([])
const isModalOpen = ref(false)
const editingId = ref<string | null>(null)

const form = ref({
  username: '',
  email: '',
  password: '',
  phone: '',
  role: 'SUPPLIER' as 'HOST' | 'SUPPLIER' | 'ADMIN',
  departmentId: ''
})

const pagination = ref({
  page: 1,
  limit: 10,
  total: 0
})

const columns = computed(() => [
  { key: 'username', label: t('auth.username'), sortable: true },
  { key: 'email', label: t('auth.email'), sortable: true },
  { key: 'role', label: t('users.role'), sortable: true },
  { key: 'department', label: t('member.department') },
])

const departmentOptions = computed(() => 
  departments.value.map(d => ({
    label: d.name,
    value: d.id
  }))
)

// Filtering
const filteredUsers = computed(() => {
  if (!searchQuery.value) return users.value
  const query = searchQuery.value.toLowerCase()
  return users.value.filter(u => 
    u.username.toLowerCase().includes(query) ||
    u.email.toLowerCase().includes(query)
  )
})

// Pagination
const paginatedUsers = computed(() => {
  const start = (pagination.value.page - 1) * pagination.value.limit
  const end = start + pagination.value.limit
  return filteredUsers.value.slice(start, end)
})

watch(filteredUsers, (newVal) => {
  pagination.value.total = newVal.length
  if (pagination.value.page > Math.ceil(newVal.length / pagination.value.limit)) {
    pagination.value.page = 1
  }
}, { immediate: true })

const isEditing = computed(() => !!editingId.value)

const getRoleColor = (role: string) => {
  switch (role) {
    case 'ADMIN': return 'red'
    case 'HOST': return 'primary'
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
    password: '',
    phone: user.phone,
    role: user.role as any,
    departmentId: user.departmentId
  }
  editingId.value = user.id
  isModalOpen.value = true
}

const confirmDelete = async (user: User) => {
  const confirmed = await showConfirmDialog(t('users.confirmDelete'))
  if (!confirmed) return

  try {
    showLoading()
    await deleteUser(user.id)
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
        password: form.value.password,
        phone: form.value.phone,
        role: form.value.role,
        departmentId: form.value.departmentId,
        organizationId: 'org_host' // Default
      })
    }
    isModalOpen.value = false
    showSystemAlert(t('common.success'), 'success')
  } catch (error: any) {
    showSystemAlert(error.message || t('common.error'), 'error')
  }
}

onMounted(async () => {
  setBreadcrumbs([
    { label: t('member.account') },
    { label: t('users.management') }
  ])
  await Promise.all([
    fetchUsers(),
    fetchDepartments()
  ])
})
</script>
