<template>
  <div class="review-management">
    <div class="placeholder-content">
      <div class="placeholder-icon">🔍</div>
      <h3>審核管理</h3>
      <p>此功能正在開發中...</p>
      <p class="description">
        這裡將顯示供應商填寫的問卷審核功能，包括：
      </p>
      <ul class="feature-list">
        <li>待審核清單</li>
        <li>審核歷程記錄</li>
        <li>核准/退回功能</li>
        <li>審核意見填寫</li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  projectId: number
}

const props = defineProps<Props>()
</script>

<style scoped>
.review-management {
  padding: 60px 20px;
}

.placeholder-content {
  max-width: 600px;
  margin: 0 auto;
  text-align: center;
}

.placeholder-icon {
  font-size: 64px;
  margin-bottom: 24px;
}

.placeholder-content h3 {
  font-size: 24px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 16px 0;
}

.placeholder-content p {
  color: #6c757d;
  margin: 8px 0;
}

.description {
  margin-top: 24px;
  font-weight: 500;
}

.feature-list {
  list-style: none;
  padding: 0;
  margin: 16px 0;
}

.feature-list li {
  padding: 8px 0;
  color: #495057;
}

.feature-list li::before {
  content: '✓ ';
  color: #28a745;
  font-weight: 700;
  margin-right: 8px;
}
</style>
