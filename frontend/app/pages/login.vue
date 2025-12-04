<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100">
    <UCard class="w-full max-w-md bg-white dark:bg-gray-800">
      <template #header>
        <div class="text-center">
          <h1 class="text-3xl font-bold text-gray-900">{{ $t('auth.login') }}</h1>
          <p class="text-gray-600 mt-2">{{ $t('common.appName') }}</p>
        </div>
      </template>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <UFormGroup :label="$t('auth.username')" name="username">
          <UInput
            v-model="form.username"
            type="text"
            :placeholder="$t('auth.username')"
            required
            icon="i-heroicons-user-20-solid"
          />
        </UFormGroup>

        <UFormGroup :label="$t('auth.password')" name="password">
          <UInput
            v-model="form.password"
            type="password"
            :placeholder="$t('auth.password')"
            required
            icon="i-heroicons-lock-closed-20-solid"
          />
        </UFormGroup>

        <div class="flex items-center justify-between text-sm">
          <UCheckbox v-model="form.rememberMe" :label="$t('auth.rememberMe')" />
          <NuxtLink to="#" class="text-blue-600 hover:text-blue-700">
            {{ $t('auth.forgotPassword') }}
          </NuxtLink>
        </div>

        <UButton
          type="submit"
          :loading="isLoading"
          block
          size="lg"
          color="blue"
        >
          {{ isLoading ? $t('common.loading') : $t('auth.login') }}
        </UButton>
      </form>

      <template #footer>
        <div v-if="error" class="mt-4">
          <UAlert
            :title="$t('common.error')"
            :description="error"
            color="red"
            icon="i-heroicons-exclamation-triangle-20-solid"
          />
        </div>
      </template>
    </UCard>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useAuth } from '~/composables/useAuth'

definePageMeta({
  layout: 'blank',
  middleware: []
})

const { login } = useAuth()
const isLoading = ref(false)
const error = ref<string | null>(null)

const form = ref({
  username: '',
  password: '',
  rememberMe: false
})

const handleLogin = async () => {
  try {
    isLoading.value = true
    error.value = null

    const res = await login(form.value.username, form.value.password)
    console.log('Login response:', res)

    // Redirect to home
    await navigateTo('/')
  } catch (err: any) {
    error.value = err.message || '登入失敗'
  } finally {
    isLoading.value = false
  }
}
</script>
