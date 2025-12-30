<template>
  <div class="p-4 text-center">
    <UButton
      loading
      variant="ghost"
      color="gray"
    />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'

definePageMeta({ middleware: 'auth' })

const router = useRouter()

onMounted(() => {
  router.replace('/conflict/projects')
})
</script>