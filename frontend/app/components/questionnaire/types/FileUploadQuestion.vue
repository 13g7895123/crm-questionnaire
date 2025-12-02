<template>
  <div class="mb-4">
    <label class="block text-sm font-medium text-gray-700">
      {{ question.text }}
      <span v-if="question.required" class="text-red-500">*</span>
    </label>
    <input
      type="text"
      class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md"
      placeholder="FileUploadQuestion"
      @input="$emit('update', $event.target.value)"
    />
  </div>
</template>
<script setup lang="ts">
import type { Question } from '~/types/index'

defineProps<{
  question: Question
}>()

defineEmits<{
  update: [value: any]
}>()
</script>