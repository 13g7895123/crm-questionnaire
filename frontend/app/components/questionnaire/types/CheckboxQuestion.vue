<template>
  <div class="space-y-2">
    <label
      v-for="option in options"
      :key="option.value"
      class="flex items-center space-x-3 cursor-pointer"
    >
      <input
        type="checkbox"
        :value="option.value"
        :checked="isChecked(option.value)"
        class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
        @change="handleChange(option.value, $event)"
      />
      <span class="text-sm font-medium text-gray-700">{{ option.label }}</span>
    </label>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  modelValue?: string[]
  options?: Array<{ value: string; label: string }>
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string[]]
}>()

const selectedValues = ref<string[]>(props.modelValue || [])

const isChecked = (value: string) => {
  return selectedValues.value.includes(value)
}

const handleChange = (value: string, event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.checked) {
    if (!selectedValues.value.includes(value)) {
      selectedValues.value.push(value)
    }
  } else {
    selectedValues.value = selectedValues.value.filter((v) => v !== value)
  }
  emit('update:modelValue', selectedValues.value)
}

watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue) {
      selectedValues.value = newValue
    }
  },
  { deep: true }
)
</script>
