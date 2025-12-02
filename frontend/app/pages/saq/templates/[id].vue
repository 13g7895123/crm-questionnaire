<template>
  <div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="w-full">
      <h1 class="text-3xl font-bold mb-8">{{ $t('templates.editTemplate') }}</h1>
    </div>
  </div>
</template>
<script setup lang="ts">
definePageMeta({ middleware: 'auth' })
</script>