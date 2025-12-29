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

onMounted(async () => {
  const projectId = route.params.projectId as string
  try {
    const res = await getProject(projectId)
    const project = res.data
    
    if (project.suppliers && project.suppliers.length > 0) {
      router.replace(`/saq/projects/${projectId}/review/${project.suppliers[0].id}`)
    } else {
      router.replace(`/saq/projects/${projectId}`)
    }
  } catch (e) {
    router.replace('/saq/projects')
  }
})
</script>
