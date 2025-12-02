<template>
  <div class="py-8 max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold mb-8">{{ $t('member.account') }}</h1>

    <UTabs :items="tabs" class="w-full">
      <template #profile="{ item }">
        <div class="mt-4">
          <ProfileForm />
        </div>
      </template>

      <template #security="{ item }">
        <div class="mt-4 max-w-2xl">
          <PasswordChangeForm />
        </div>
      </template>

      <template #members="{ item }">
        <div class="mt-4">
          <UserManagement />
        </div>
      </template>

      <template #suppliers="{ item }">
        <div class="mt-4">
          <SupplierManagement />
        </div>
      </template>

      <template #departments="{ item }">
        <div class="mt-4">
          <DepartmentManagement />
        </div>
      </template>
    </UTabs>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '~/stores/auth'
import ProfileForm from '~/components/account/ProfileForm.vue'
import PasswordChangeForm from '~/components/account/PasswordChangeForm.vue'
import UserManagement from '~/components/account/UserManagement.vue'
import SupplierManagement from '~/components/account/SupplierManagement.vue'
import DepartmentManagement from '~/components/account/DepartmentManagement.vue'

definePageMeta({
  middleware: 'auth'
})

const authStore = useAuthStore()
const { t } = useI18n()

const tabs = computed(() => {
  const items = [
    {
      slot: 'profile',
      label: t('member.profile'),
      icon: 'i-heroicons-user-circle'
    },
    {
      slot: 'security',
      label: t('auth.password'),
      icon: 'i-heroicons-lock-closed'
    }
  ]

  // Only HOST and ADMIN can see Member Management
  if (authStore.user?.role === 'HOST' || authStore.user?.role === 'ADMIN') {
    items.push({
      slot: 'members',
      label: t('users.management'),
      icon: 'i-heroicons-users'
    })
    items.push({
      slot: 'suppliers',
      label: t('suppliers.management'),
      icon: 'i-heroicons-building-office-2'
    })
    items.push({
      slot: 'departments',
      label: t('departments.management'),
      icon: 'i-heroicons-building-office'
    })
  }

  return items
})
</script>
