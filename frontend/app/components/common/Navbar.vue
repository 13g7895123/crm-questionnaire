<template>
  <nav class="bg-white shadow w-full">
    <div class="w-full px-6 py-4">
      <div class="flex justify-between items-center">
        <!-- Left: Logo/App Name -->
        <NuxtLink :to="currentApp.link" class="flex items-center gap-2">
          <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">
            <span :class="currentApp.iconClass">{{ currentApp.icon }}</span>
          </div>
          <div>
            <div class="text-xl font-bold text-gray-900">{{ currentApp.title }}</div>
            <div class="text-xs text-gray-500">{{ currentApp.subtitle }}</div>
          </div>
        </NuxtLink>

        <!-- Center: Navigation Links -->
        <div class="flex-1 flex justify-center">
          <NuxtLink
            v-if="currentApp.link !== '/'"
            to="/"
            class="flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition-colors font-medium"
          >
            <UIcon name="i-heroicons-squares-2x2" class="w-5 h-5" />
            <span>{{ $t('member.memberCenter') }}</span>
          </NuxtLink>
        </div>

        <!-- Right: User Info & Actions -->
        <div class="flex items-center gap-6">
          <!-- Notification -->
          <button class="text-gray-600 hover:text-gray-900">
            <UIcon name="i-heroicons-bell" class="w-6 h-6" />
          </button>

          <!-- User Profile -->
          <UDropdown :items="items" :popper="{ placement: 'bottom-end' }">
            <div class="flex items-center gap-2 cursor-pointer">
              <UAvatar
                :alt="user?.username || 'User'"
                size="sm"
                class="bg-gray-200 text-gray-600"
              />
              <div class="flex items-center gap-1 text-sm font-medium text-gray-700">
                {{ user?.username || 'User' }}
                <UIcon name="i-heroicons-chevron-down" class="w-4 h-4" />
              </div>
            </div>
          </UDropdown>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '~/stores/auth'
import { useAuth } from '~/composables/useAuth'
import { useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'

const authStore = useAuthStore()
const { logout } = useAuth()
const router = useRouter()
const route = useRoute()
const { t } = useI18n()
const { user } = storeToRefs(authStore)

const currentApp = computed(() => {
  if (route.path.startsWith('/saq')) {
    return {
      title: t('apps.saq'),
      subtitle: 'SAQ Questionnaire',
      icon: '📋',
      iconClass: 'text-xl',
      link: '/saq/projects'
    }
  }
  return {
    title: t('member.memberCenter'),
    subtitle: 'Member Center',
    icon: 'C',
    iconClass: 'text-xl',
    link: '/'
  }
})

const handleLogout = async () => {
  await logout()
  router.push('/login')
}

const items = computed(() => [
  [{
    label: t('auth.logout'),
    icon: 'i-heroicons-arrow-left-on-rectangle',
    click: handleLogout
  }]
])
</script>
