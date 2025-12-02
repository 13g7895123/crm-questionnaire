<template>
  <div class="bg-white p-6 rounded-lg shadow">
    <h2 class="text-2xl font-bold mb-4">{{ $t('member.changePassword') }}</h2>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">{{ $t('member.currentPassword') }}</label>
        <input
          v-model="form.currentPassword"
          type="password"
          class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md"
          required
        />
        <div v-if="errors.currentPassword" class="mt-1 text-sm text-red-600">
          {{ errors.currentPassword }}
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">{{ $t('member.newPassword') }}</label>
        <input
          v-model="form.newPassword"
          type="password"
          class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md"
          required
        />
        <div v-if="errors.newPassword" class="mt-1 text-sm text-red-600">
          {{ errors.newPassword }}
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">{{ $t('member.confirmPassword') }}</label>
        <input
          v-model="form.confirmPassword"
          type="password"
          class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md"
          required
        />
        <div v-if="errors.confirmPassword" class="mt-1 text-sm text-red-600">
          {{ errors.confirmPassword }}
        </div>
      </div>

      <button
        type="submit"
        :disabled="isLoading"
        class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 disabled:bg-gray-400"
      >
        {{ isLoading ? $t('common.loading') : $t('common.save') }}
      </button>
    </form>

    <div v-if="success" class="mt-4 p-3 bg-green-100 text-green-700 rounded">
      {{ $t('member.passwordChanged') }}
    </div>

    <div v-if="error" class="mt-4 p-3 bg-red-100 text-red-700 rounded">
      {{ error }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const isLoading = ref(false)
const success = ref(false)
const error = ref<string | null>(null)

const form = ref({
  currentPassword: '',
  newPassword: '',
  confirmPassword: ''
})

const errors = ref({
  currentPassword: '',
  newPassword: '',
  confirmPassword: ''
})

const validate = (): boolean => {
  errors.value = {
    currentPassword: '',
    newPassword: '',
    confirmPassword: ''
  }

  if (!form.value.currentPassword) {
    errors.value.currentPassword = 'Current password is required'
  }

  if (!form.value.newPassword) {
    errors.value.newPassword = 'New password is required'
  } else if (form.value.newPassword.length < 8) {
    errors.value.newPassword = 'Password must be at least 8 characters'
  }

  if (form.value.newPassword !== form.value.confirmPassword) {
    errors.value.confirmPassword = 'Passwords do not match'
  }

  return !Object.values(errors.value).some(e => e)
}

const handleSubmit = async () => {
  error.value = null

  if (!validate()) {
    return
  }

  try {
    isLoading.value = true
    success.value = false

    // Mock password change
    await new Promise(resolve => setTimeout(resolve, 500))

    success.value = true
    form.value = {
      currentPassword: '',
      newPassword: '',
      confirmPassword: ''
    }

    setTimeout(() => {
      success.value = false
    }, 3000)
  } catch (err: any) {
    error.value = err.message || 'Failed to change password'
  } finally {
    isLoading.value = false
  }
}
</script>
