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
import { computed } from 'vue'

const { locale } = useI18n()

const currentLocale = computed({
  get: () => locale.value,
  set: (val) => {
    locale.value = val
    if (process.client) {
      localStorage.setItem('locale', val)
    }
  }
})

const changeLocale = (event: Event) => {
  const target = event.target as HTMLSelectElement
  currentLocale.value = target.value
}
</script>
