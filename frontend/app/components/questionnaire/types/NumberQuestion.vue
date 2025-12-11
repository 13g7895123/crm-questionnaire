<template>
  <input
    type="number"
    :value="modelValue"
    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
    :placeholder="config?.placeholder"
    @input="handleInput"
  />
</template>

<script setup lang="ts">
const props = defineProps<{
  modelValue?: number | string
  config?: any
}>()

const emit = defineEmits<{
  'update:modelValue': [value: number]
}>()

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  const val = target.value === '' ? undefined : Number(target.value)
  if (val !== undefined) {
    emit('update:modelValue', val)
  }
}
</script>
