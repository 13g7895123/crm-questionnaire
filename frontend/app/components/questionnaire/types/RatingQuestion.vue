<template>
  <div class="flex space-x-2">
    <button
      v-for="rating in maxRating"
      :key="rating"
      type="button"
      class="w-10 h-10 rounded-full border focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
      :class="[
        modelValue === rating
          ? 'bg-blue-600 text-white border-blue-600'
          : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
      ]"
      @click="handleClick(rating)"
    >
      {{ rating }}
    </button>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  modelValue?: number
  config?: any
}>()

const emit = defineEmits<{
  'update:modelValue': [value: number]
}>()

const maxRating = props.config?.max || 5

const handleClick = (rating: number) => {
  emit('update:modelValue', rating)
}
</script>
