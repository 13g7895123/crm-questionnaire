<template>
  <div v-if="breadcrumbs.length > 1" class="w-full bg-white border-t border-gray-200 shadow-sm">
    <div class="w-full px-6 py-3">
      <UBreadcrumb :links="breadcrumbs" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useBreadcrumbs } from '~/composables/useBreadcrumbs'

const route = useRoute()
const { t, te } = useI18n()
const { customBreadcrumbs, clearBreadcrumbs } = useBreadcrumbs()

// Clear custom breadcrumbs on route change
watch(() => route.path, () => {
  clearBreadcrumbs()
})

const breadcrumbs = computed(() => {
  // 1. Custom breadcrumbs (set by page)
  if (customBreadcrumbs.value && customBreadcrumbs.value.length > 0) {
    return customBreadcrumbs.value
  }

  // 2. Route meta breadcrumbs
  if (route.meta.breadcrumbs) {
    const metaCrumbs = route.meta.breadcrumbs as any[]
    return metaCrumbs.map(item => ({
      ...item,
      label: te(item.label) ? t(item.label) : item.label
    }))
  }

  // 3. Auto-generated from path
  const path = route.path
  const parts = path.split('/').filter(p => p)
  
  const items = [{
    label: t('common.home') || 'Home',
    to: '/',
    icon: 'i-heroicons-home'
  }]

  let currentPath = ''
  parts.forEach((part, index) => {
    currentPath += `/${part}`
    
    // Skip if it's a dynamic parameter (simple heuristic: looks like an ID or is 'index')
    // But we don't know if it is an ID easily without route matching.
    // However, for auto-generation, we usually just display the segment or try to translate it.
    
    let label = part.charAt(0).toUpperCase() + part.slice(1)
    
    // Try to find translation
    const keys = [
      `apps.${part}`,
      `projects.${part}`,
      `templates.${part}`,
      `suppliers.${part}`,
      `common.${part}`,
      `member.${part}`,
      `${part}.${part}` // e.g. review.review
    ]
    
    for (const key of keys) {
      if (te(key)) {
        label = t(key)
        break
      }
    }

    // If it looks like a UUID or number, maybe don't show it or show "Detail"
    // But for now, let's just show it.
    
    const isHiddenLink = ['/saq', '/conflict'].includes(currentPath)

    items.push({
      label: label,
      to: (index === parts.length - 1 || isHiddenLink) ? undefined : currentPath
    })
  })

  return items
})
</script>
