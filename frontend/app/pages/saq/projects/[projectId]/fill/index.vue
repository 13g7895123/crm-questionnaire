<template>
  <div class="flex items-center justify-center min-h-[400px]">
    <div class="text-center">
      <UIcon name="i-heroicons-arrow-path" class="w-8 h-8 animate-spin text-primary-500 mx-auto mb-4" />
      <p class="text-gray-500">{{ $t('common.loading') }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const router = useRouter()
const { getProject } = useProjects()
const authStore = useAuthStore()

onMounted(async () => {
  const projectId = route.params.projectId as string
  try {
    const res = await getProject(projectId)
    const project = res.data
    
    // If user is a host/admin, they might have more than one supplier or need to choose.
    // But since they didn't provide a supplierId, let's go back to project detail.
    if (project.suppliers && project.suppliers.length > 0) {
      // If there's only one supplier, we can redirect. 
      // Otherwise, project detail is better for choosing.
      router.replace(`/saq/projects/${projectId}/fill/${project.suppliers[0].id}`)
    } else {
      // If it's a supplier, the current getProject doesn't return the ID...
      // We might need to fix the backend or handle it differently.
      router.replace(`/saq/projects/${projectId}`)
    }
  } catch (e) {
    router.replace('/saq/projects')
  }
})
</script>
