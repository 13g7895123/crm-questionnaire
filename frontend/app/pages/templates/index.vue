<template>
  <div class="container mx-auto p-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold">範本管理</h1>
      <button
        @click="$router.push('/templates/create')"
        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
      >
        建立新範本
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="template in templates"
        :key="template.id"
        class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow cursor-pointer"
        @click="$router.push(`/templates/${template.id}/preview`)"
      >
        <div class="flex justify-between items-start mb-4">
          <h3 class="text-xl font-semibold">{{ template.name }}</h3>
          <span
            :class="[
              'px-3 py-1 rounded-full text-xs font-medium',
              template.type === 'SAQ'
                ? 'bg-blue-100 text-blue-800'
                : 'bg-purple-100 text-purple-800',
            ]"
          >
            {{ template.type }}
          </span>
        </div>
        <p class="text-gray-600 text-sm mb-4">{{ template.description }}</p>
        <div class="flex justify-between items-center text-sm text-gray-500">
          <span>版本: {{ template.version }}</span>
          <span>{{ template.questionCount }} 題</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const templates = ref([
  {
    id: 1,
    name: '2025 SAQ 供應商評估範本',
    type: 'SAQ',
    description: '完整的供應商自我評估問卷，包含公司基本資料與A-E五大評估面向',
    version: '1.0.0',
    questionCount: 45,
  },
  {
    id: 2,
    name: '利益衝突聲明範本',
    type: 'CONFLICT',
    description: '供應商利益衝突聲明問卷',
    version: '1.0.0',
    questionCount: 12,
  },
]);
</script>
