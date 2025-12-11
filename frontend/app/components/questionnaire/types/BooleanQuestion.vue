<template>
  <div class="space-y-2">
    <label class="flex items-center space-x-3 cursor-pointer">
      <input
        type="radio"
        :checked="modelValue === true"
        class="w-4 h-4 text-blue-600 focus:ring-blue-500"
        @change="handleChange(true)"
      />
      <span class="text-sm font-medium text-gray-700">是</span>
    </label>
    <label class="flex items-center space-x-3 cursor-pointer">
      <input
        type="radio"
        :checked="modelValue === false"
        class="w-4 h-4 text-blue-600 focus:ring-blue-500"
        @change="handleChange(false)"
      />
      <span class="text-sm font-medium text-gray-700">否</span>
    </label>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  modelValue?: boolean | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const handleChange = (value: boolean) => {
  emit('update:modelValue', value)
}
</script>
