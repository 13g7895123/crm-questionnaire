<template>
  <div class="py-8 max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold mb-8">{{ $t('member.account') }}</h1>

    <div class="space-y-8">
      <ProfileForm />
      <PasswordChangeForm />
    </div>
  </div>
</template>

<script setup lang="ts">
import ProfileForm from '~/components/account/ProfileForm.vue'
import PasswordChangeForm from '~/components/account/PasswordChangeForm.vue'

definePageMeta({
  middleware: 'auth'
})
</script>
