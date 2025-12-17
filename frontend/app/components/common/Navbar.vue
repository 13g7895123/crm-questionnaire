<template>
  <nav class="sticky top-0 z-50 w-full bg-white/80 backdrop-blur-md border-b border-gray-200/50 transition-all duration-300">
    
    <div class="w-full px-6 py-3">
      <div class="flex justify-between items-center">
        <!-- Left: Logo/App Name -->
        <NuxtLink :to="currentApp.link" class="group flex items-center gap-3">
          <div class="relative w-10 h-10 flex items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white shadow-lg shadow-blue-500/20 group-hover:shadow-blue-500/40 transition-all duration-300 group-hover:scale-105">
            <span :class="[currentApp.iconClass, 'relative z-10']">{{ currentApp.icon }}</span>
            <!-- Inner Glow -->
            <div class="absolute inset-0 rounded-xl bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
          </div>
          <div class="flex flex-col">
            <div class="text-lg font-bold text-slate-800 tracking-tight leading-none group-hover:text-blue-700 transition-colors">
              {{ currentApp.title }}
            </div>
            <div class="text-[10px] font-medium uppercase tracking-wider text-slate-500 mt-1">
              {{ currentApp.subtitle }}
            </div>
          </div>
        </NuxtLink>

        <!-- Center: Navigation Links -->
        <div class="hidden md:flex flex-1 justify-center">
          <NuxtLink
            v-if="currentApp.link !== '/'"
            to="/"
            class="group relative flex items-center gap-2 px-5 py-2 text-slate-600 hover:text-blue-600 transition-colors"
          >
            <div class="absolute inset-0 bg-blue-50/50 rounded-lg scale-95 opacity-0 group-hover:opacity-100 group-hover:scale-100 transition-all duration-200"></div>
            <UIcon name="i-heroicons-squares-2x2" class="w-5 h-5 relative z-10" />
            <span class="text-sm font-medium relative z-10">{{ $t('member.memberCenter') }}</span>
          </NuxtLink>
        </div>

        <!-- Right: User Info & Actions -->
        <div class="flex items-center gap-3 sm:gap-5">
          <!-- Language Switcher -->
          <UDropdown :items="languageItems" :popper="{ placement: 'bottom-end' }">
            <UButton
              color="gray"
              variant="ghost"
              size="sm"
              class="flex items-center gap-1 text-slate-600 hover:text-slate-900 transition-colors hover:bg-slate-100/50 rounded-lg px-2"
            >
              <UIcon name="i-heroicons-language" class="w-5 h-5" />
              <span class="text-xs font-semibold">{{ currentLanguageLabel }}</span>
            </UButton>
          </UDropdown>

          <!-- Divider -->
          <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

          <!-- Notification -->
          <button class="relative text-slate-500 hover:text-blue-600 transition-colors p-1.5 rounded-lg hover:bg-blue-50/50">
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white"></span>
            <UIcon name="i-heroicons-bell" class="w-6 h-6" />
          </button>

          <!-- User Profile -->
          <UDropdown :items="items" :popper="{ placement: 'bottom-end' }">
            <div class="flex items-center gap-3 cursor-pointer pl-2 py-1 rounded-full hover:bg-slate-50/80 transition-colors border border-transparent hover:border-slate-100">
              <div class="hidden md:flex flex-col items-end">
                <div class="flex items-center gap-2">
                  <span class="text-sm font-bold text-slate-700 tracking-tight">
                    {{ user?.username || 'User' }}
                  </span>
                  <UBadge 
                    size="xs" 
                    :color="user?.role === 'HOST' ? 'primary' : 'gray'"
                    variant="subtle"
                    class="font-mono text-[10px] px-1.5 py-0.5 rounded-md ring-1 ring-inset ring-opacity-20"
                    :class="user?.role === 'HOST' ? 'ring-primary-500' : 'ring-gray-500'"
                  >
                    {{ user?.role }}
                  </UBadge>
                </div>
              </div>
              <div class="relative">
                <UAvatar
                  :alt="user?.username || 'User'"
                  size="sm"
                  class="ring-2 ring-white shadow-sm"
                  :ui="{ background: 'bg-slate-100 text-slate-600' }"
                />
                <div class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-500 rounded-full ring-2 ring-white"></div>
              </div>
              <UIcon name="i-heroicons-chevron-down" class="w-4 h-4 text-slate-400" />
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
const { t, locale } = useI18n()
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

// Language switcher
const currentLanguageLabel = computed(() => {
  return locale.value === 'zh-TW' ? '繁中' : 'EN'
})

const setLanguage = (lang: string) => {
  locale.value = lang
  // Persist to localStorage
  localStorage.setItem('locale', lang)
}

const languageItems = computed(() => [
  [
    {
      label: '繁體中文',
      icon: locale.value === 'zh-TW' ? 'i-heroicons-check' : undefined,
      click: () => setLanguage('zh-TW')
    },
    {
      label: 'English',
      icon: locale.value === 'en' ? 'i-heroicons-check' : undefined,
      click: () => setLanguage('en')
    }
  ]
])

const items = computed(() => [
  [{
    label: t('auth.logout'),
    icon: 'i-heroicons-arrow-left-on-rectangle',
    click: handleLogout
  }]
])
</script>

