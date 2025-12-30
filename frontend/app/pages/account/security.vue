<template>
  <NuxtLayout name="account">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">{{ $t('auth.password') }}</h1>
      <p class="mt-1 text-sm text-gray-500">
        {{ $t('member.changePassword') }}
      </p>
    </div>
    <div class="max-w-xl">
      <PasswordChangeForm />
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
import PasswordChangeForm from '~/components/account/PasswordChangeForm.vue'
import { useBreadcrumbs } from '~/composables/useBreadcrumbs'
import { useI18n } from 'vue-i18n'

definePageMeta({ middleware: 'auth' })

const { t } = useI18n()
const { setBreadcrumbs } = useBreadcrumbs()

onMounted(() => {
  setBreadcrumbs([
    { label: t('common.home'), to: '/' },
    { label: t('auth.password') }
  ])
})
</script>
