<template>
  <div class="py-8">
    <h1 class="text-3xl font-bold mb-8">{{ $t('templates.templates') }}</h1>
  </div>
</template>
<script setup lang="ts">
definePageMeta({ middleware: 'auth' })
</script>