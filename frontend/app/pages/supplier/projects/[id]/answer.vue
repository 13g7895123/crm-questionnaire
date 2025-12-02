<template>
  <div class="py-8">
    <h1 class="text-3xl font-bold mb-8">{{ $t('questionnaire.questionnaire') }}</h1>
    <div class="bg-white p-6 rounded-lg shadow">
      <p class="text-gray-600">Questionnaire answering form</p>
    </div>
  </div>
</template>
<script setup lang="ts">
definePageMeta({ middleware: 'auth' })
</script>