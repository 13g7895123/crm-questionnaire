<template>
  <div class="space-y-1">
    <UVerticalNavigation :links="links" />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const authStore = useAuthStore()

const links = computed(() => {
  const items: any[] = []

  if (authStore.user?.role === 'HOST' || authStore.user?.role === 'ADMIN') {
    items.push({
      label: t('users.management'),
      icon: 'i-heroicons-users',
      to: '/account/users'
    })
    items.push({
      label: t('suppliers.management'),
      icon: 'i-heroicons-building-office-2',
      to: '/account/suppliers'
    })
    items.push({
      label: t('departments.management'),
      icon: 'i-heroicons-building-office',
      to: '/account/departments'
    })
  }

  return items
})
</script>
