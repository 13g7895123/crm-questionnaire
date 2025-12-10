<template>
  <div class="space-y-8">
    <div class="text-center mb-8">
      <h2 class="text-3xl font-bold text-gray-800 mb-2">評估結果</h2>
      <p class="text-gray-600">根據您的問卷填答，以下是各面向的評估分數</p>
    </div>

    <!-- 雷达图 -->
    <div class="bg-white rounded-lg shadow-md p-8">
      <div class="flex justify-center">
        <canvas ref="radarCanvas" width="600" height="600"></canvas>
      </div>
    </div>

    <!-- 分数表格 -->
    <div class="bg-white rounded-lg shadow-md p-8">
      <h3 class="text-xl font-semibold text-gray-800 mb-4">分數統計</h3>
      <div class="overflow-x-auto">
        <table class="min-w-full">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                評估面向
              </th>
              <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">
                得分
              </th>
              <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">
                等級
              </th>
              <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                評價
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr
              v-for="(score, index) in scores"
              :key="index"
              class="hover:bg-gray-50"
            >
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div
                    class="w-3 h-3 rounded-full mr-3"
                    :style="{ backgroundColor: score.color }"
                  ></div>
                  <span class="font-medium text-gray-900">{{ score.label }}</span>
                </div>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="text-2xl font-bold" :style="{ color: score.color }">
                  {{ score.value }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <span
                  :class="[
                    'px-3 py-1 rounded-full text-xs font-semibold',
                    getGradeClass(score.value),
                  ]"
                >
                  {{ getGrade(score.value) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">
                {{ getRemark(score.value) }}
              </td>
            </tr>
          </tbody>
          <tfoot class="bg-gray-50">
            <tr>
              <td class="px-6 py-4 font-semibold text-gray-900">總分</td>
              <td class="px-6 py-4 text-center">
                <span class="text-2xl font-bold text-blue-600">
                  {{ totalScore }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <span
                  :class="[
                    'px-3 py-1 rounded-full text-xs font-semibold',
                    getGradeClass(totalScore / 5),
                  ]"
                >
                  {{ getGrade(totalScore / 5) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">
                {{ getRemark(totalScore / 5) }}
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- 详细说明 -->
    <div class="bg-blue-50 rounded-lg p-6">
      <h4 class="font-semibold text-blue-900 mb-2">評分說明</h4>
      <ul class="text-sm text-blue-800 space-y-1">
        <li>• 90-100分：優秀 - 完全符合要求，表現卓越</li>
        <li>• 80-89分：良好 - 符合要求，有持續改進空間</li>
        <li>• 70-79分：尚可 - 基本符合要求，需加強改善</li>
        <li>• 60-69分：待改進 - 部分不符合要求，需積極改善</li>
        <li>• 0-59分：不合格 - 嚴重不符合要求，需立即改善</li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  data: any;
}>();

const radarCanvas = ref<HTMLCanvasElement | null>(null);

const scores = ref([
  {
    label: 'A. 勞工',
    value: 0,
    color: '#FF6384',
  },
  {
    label: 'B. 健康與安全',
    value: 0,
    color: '#36A2EB',
  },
  {
    label: 'C. 環境',
    value: 0,
    color: '#FFCE56',
  },
  {
    label: 'D. 道德規範',
    value: 0,
    color: '#4BC0C0',
  },
  {
    label: 'E. 管理系統',
    value: 0,
    color: '#9966FF',
  },
]);

const totalScore = computed(() => {
  return scores.value.reduce((sum, score) => sum + score.value, 0);
});

const getGrade = (score: number): string => {
  if (score >= 90) return '優秀';
  if (score >= 80) return '良好';
  if (score >= 70) return '尚可';
  if (score >= 60) return '待改進';
  return '不合格';
};

const getGradeClass = (score: number): string => {
  if (score >= 90) return 'bg-green-100 text-green-800';
  if (score >= 80) return 'bg-blue-100 text-blue-800';
  if (score >= 70) return 'bg-yellow-100 text-yellow-800';
  if (score >= 60) return 'bg-orange-100 text-orange-800';
  return 'bg-red-100 text-red-800';
};

const getRemark = (score: number): string => {
  if (score >= 90) return '完全符合要求，表現卓越';
  if (score >= 80) return '符合要求，有持續改進空間';
  if (score >= 70) return '基本符合要求，需加強改善';
  if (score >= 60) return '部分不符合要求，需積極改善';
  return '嚴重不符合要求，需立即改善';
};

const calculateScores = () => {
  // 根据问卷答案计算分数（简化示例）
  const step2Answers = props.data.step2 || {};
  const step3Answers = props.data.step3 || {};
  const step4Answers = props.data.step4 || {};
  const step5Answers = props.data.step5 || {};

  // 计算 A. 劳工分数
  const aAnswers = Object.keys(step2Answers).filter(
    (key) => step2Answers[key] === true
  ).length;
  scores.value[0].value = Math.min(100, aAnswers * 20);

  // 计算 B. 健康与安全分数
  const bAnswers = Object.keys(step3Answers).filter(
    (key) => step3Answers[key] === true
  ).length;
  scores.value[1].value = Math.min(100, bAnswers * 25);

  // 计算 C. 环境分数
  const cAnswers = Object.keys(step4Answers).filter(
    (key) => step4Answers[key] === true
  ).length;
  scores.value[2].value = Math.min(100, cAnswers * 25);

  // 计算 D. 道德规范分数
  const dAnswers = Object.keys(step5Answers).filter(
    (key) => step5Answers[key] === true
  ).length;
  scores.value[3].value = Math.min(100, dAnswers * 25);

  // E. 管理系统 - 示例固定值
  scores.value[4].value = 75;
};

const drawRadarChart = () => {
  if (!radarCanvas.value) return;

  const canvas = radarCanvas.value;
  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  const centerX = canvas.width / 2;
  const centerY = canvas.height / 2;
  const radius = 200;
  const levels = 6; // 0-100 分为 6 层

  // 清空画布
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  // 绘制背景网格
  for (let i = levels; i > 0; i--) {
    ctx.beginPath();
    const levelRadius = (radius / levels) * i;

    for (let j = 0; j < 5; j++) {
      const angle = (Math.PI * 2 * j) / 5 - Math.PI / 2;
      const x = centerX + levelRadius * Math.cos(angle);
      const y = centerY + levelRadius * Math.sin(angle);

      if (j === 0) {
        ctx.moveTo(x, y);
      } else {
        ctx.lineTo(x, y);
      }
    }

    ctx.closePath();
    ctx.strokeStyle = '#e5e7eb';
    ctx.lineWidth = 1;
    ctx.stroke();

    // 标注分数
    if (i > 0) {
      ctx.fillStyle = '#9ca3af';
      ctx.font = '12px sans-serif';
      ctx.textAlign = 'center';
      ctx.fillText(
        ((100 / levels) * i).toString(),
        centerX,
        centerY - (levelRadius + 10)
      );
    }
  }

  // 绘制坐标轴线
  for (let i = 0; i < 5; i++) {
    const angle = (Math.PI * 2 * i) / 5 - Math.PI / 2;
    const x = centerX + radius * Math.cos(angle);
    const y = centerY + radius * Math.sin(angle);

    ctx.beginPath();
    ctx.moveTo(centerX, centerY);
    ctx.lineTo(x, y);
    ctx.strokeStyle = '#d1d5db';
    ctx.lineWidth = 1;
    ctx.stroke();
  }

  // 绘制数据区域
  ctx.beginPath();
  for (let i = 0; i < scores.value.length; i++) {
    const score = scores.value[i].value;
    const angle = (Math.PI * 2 * i) / 5 - Math.PI / 2;
    const distance = (radius * score) / 100;
    const x = centerX + distance * Math.cos(angle);
    const y = centerY + distance * Math.sin(angle);

    if (i === 0) {
      ctx.moveTo(x, y);
    } else {
      ctx.lineTo(x, y);
    }
  }
  ctx.closePath();
  ctx.fillStyle = 'rgba(59, 130, 246, 0.2)';
  ctx.fill();
  ctx.strokeStyle = 'rgba(59, 130, 246, 0.8)';
  ctx.lineWidth = 2;
  ctx.stroke();

  // 绘制数据点
  for (let i = 0; i < scores.value.length; i++) {
    const score = scores.value[i].value;
    const angle = (Math.PI * 2 * i) / 5 - Math.PI / 2;
    const distance = (radius * score) / 100;
    const x = centerX + distance * Math.cos(angle);
    const y = centerY + distance * Math.sin(angle);

    ctx.beginPath();
    ctx.arc(x, y, 6, 0, Math.PI * 2);
    ctx.fillStyle = scores.value[i].color;
    ctx.fill();
    ctx.strokeStyle = '#fff';
    ctx.lineWidth = 2;
    ctx.stroke();
  }

  // 绘制标签
  for (let i = 0; i < scores.value.length; i++) {
    const angle = (Math.PI * 2 * i) / 5 - Math.PI / 2;
    const labelDistance = radius + 40;
    const x = centerX + labelDistance * Math.cos(angle);
    const y = centerY + labelDistance * Math.sin(angle);

    ctx.fillStyle = '#1f2937';
    ctx.font = 'bold 14px sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(scores.value[i].label, x, y);
  }
};

onMounted(() => {
  calculateScores();
  nextTick(() => {
    drawRadarChart();
  });
});

watch(
  () => props.data,
  () => {
    calculateScores();
    nextTick(() => {
      drawRadarChart();
    });
  },
  { deep: true }
);
</script>
