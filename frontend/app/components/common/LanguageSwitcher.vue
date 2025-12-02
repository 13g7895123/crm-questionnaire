<template>
  <div class="flex items-center gap-2">
    <select 
      :value="currentLocale" 
      @change="changeLocale"
      class="px-2 py-1 border rounded"
    >
      <option value="zh-TW">繁體中文</option>
      <option value="en">English</option>
    </select>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { ref, watch, onMounted } from 'vue'

const { locale } = useI18n()
const currentLocale = ref('')

onMounted(() => {
  currentLocale.value = locale.value
})

watch(locale, (newVal) => {
  currentLocale.value = newVal
})

const changeLocale = (event: Event) => {
  const target = event.target as HTMLSelectElement
  const val = target.value
  locale.value = val
  if (process.client) {
    localStorage.setItem('locale', val)
  }
}
</script>
