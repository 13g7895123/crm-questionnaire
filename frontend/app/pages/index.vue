<template>
  <div class="py-8">
    <h1 class="text-3xl font-bold mb-8">{{ $t('member.memberCenter') }}</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <AppCard
        title="SAQ"
        description="Supply Chain Assessment Questionnaire"
        icon="📋"
        href="/saq"
      />
      <AppCard
        title="Conflict Minerals"
        description="衝突資產管理系統"
        icon="⚠️"
        href="/conflict"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import AppCard from '~/components/member/AppCard.vue'

definePageMeta({
  middleware: 'auth'
})
</script>
