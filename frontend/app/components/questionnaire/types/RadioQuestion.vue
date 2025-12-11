<template>
  <div class="space-y-2">
    <label
      v-for="option in options"
      :key="option.value"
      class="flex items-center space-x-3 cursor-pointer"
    >
      <input
        type="radio"
        :checked="modelValue === option.value"
        class="w-4 h-4 text-blue-600 focus:ring-blue-500"
        @change="handleChange(option.value)"
      />
      <span class="text-sm font-medium text-gray-700">{{ option.label }}</span>
    </label>
  </div>
</template>

<script setup lang="ts">
interface Option {
  label: string
  value: string
}

const props = defineProps<{
  modelValue?: string | null
  options?: Option[]
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const handleChange = (value: string) => {
  emit('update:modelValue', value)
}
</script>
