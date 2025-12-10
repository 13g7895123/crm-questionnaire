<template>
  <div class="space-y-6">
    <h2 class="text-2xl font-bold text-gray-800">{{ section.title }}</h2>

    <div
      v-for="subsection in section.subsections"
      :key="subsection.id"
      class="border-b pb-6 last:border-b-0"
    >
      <h3 class="text-xl font-semibold text-blue-700 mb-4">
        {{ subsection.title }}
      </h3>

      <div
        v-for="(question, qIndex) in subsection.questions"
        :key="question.id"
        class="mb-6 bg-gray-50 p-4 rounded-lg"
      >
        <!-- 主题 -->
        <div class="mb-3">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ question.id }}. {{ question.text }}
            <span v-if="question.required" class="text-red-500">*</span>
          </label>

          <!-- 是非题 -->
          <div v-if="question.type === 'boolean'" class="space-y-2">
            <label class="flex items-center">
              <input
                v-model="answers[question.id]"
                type="radio"
                :value="true"
                class="mr-2"
                @change="handleAnswerChange(question)"
              />
              是
            </label>
            <label class="flex items-center">
              <input
                v-model="answers[question.id]"
                type="radio"
                :value="false"
                class="mr-2"
                @change="handleAnswerChange(question)"
              />
              否
            </label>
          </div>

          <!-- 文本题 -->
          <textarea
            v-else-if="question.type === 'text'"
            v-model="answers[question.id]"
            rows="3"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :placeholder="'請輸入答案'"
          ></textarea>

          <!-- 数字题 -->
          <input
            v-else-if="question.type === 'number'"
            v-model.number="answers[question.id]"
            type="number"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="請輸入數字"
          />
        </div>

        <!-- 后续问题（根据条件显示） -->
        <div
          v-if="
            question.followUp &&
            shouldShowFollowUp(question, answers[question.id])
          "
          class="ml-6 mt-4 space-y-4 border-l-4 border-blue-300 pl-4"
        >
          <div
            v-for="followUpQ in question.followUp.questions"
            :key="followUpQ.id"
          >
            <!-- 文本后续问题 -->
            <div v-if="followUpQ.type === 'text'">
              <label class="block text-sm font-medium text-gray-600 mb-2">
                {{ followUpQ.text }}
              </label>
              <textarea
                v-model="answers[followUpQ.id]"
                rows="2"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              ></textarea>
            </div>

            <!-- 数字后续问题 -->
            <div v-else-if="followUpQ.type === 'number'">
              <label class="block text-sm font-medium text-gray-600 mb-2">
                {{ followUpQ.text }}
              </label>
              <input
                v-model.number="answers[followUpQ.id]"
                type="number"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <!-- 表格后续问题 -->
            <div v-else-if="followUpQ.type === 'table'">
              <label class="block text-sm font-medium text-gray-600 mb-3">
                {{ followUpQ.text }}
              </label>
              <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-300">
                  <thead class="bg-gray-100">
                    <tr>
                      <th
                        v-for="col in followUpQ.columns"
                        :key="col"
                        class="px-4 py-2 border-b text-left text-xs font-semibold text-gray-700"
                      >
                        {{ col }}
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="(row, rowIndex) in followUpQ.rows"
                      :key="rowIndex"
                      class="hover:bg-gray-50"
                    >
                      <td
                        v-for="(col, colIndex) in followUpQ.columns"
                        :key="colIndex"
                        class="px-4 py-2 border-b"
                      >
                        <span v-if="colIndex === 0" class="font-medium text-gray-700">
                          {{ row }}
                        </span>
                        <input
                          v-else
                          :value="getTableAnswer(followUpQ.id, rowIndex, colIndex)"
                          @input="setTableAnswer(followUpQ.id, rowIndex, colIndex, ($event.target as HTMLInputElement).value)"
                          type="text"
                          class="w-full px-2 py-1 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-transparent text-sm"
                          :placeholder="`請輸入${col}`"
                        />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Question {
  id: string;
  text: string;
  type: 'boolean' | 'text' | 'number' | 'table';
  required?: boolean;
  followUp?: {
    condition: boolean;
    questions: Array<{
      id: string;
      text: string;
      type: 'text' | 'number' | 'table';
      columns?: string[];
      rows?: string[];
    }>;
  };
}

interface Subsection {
  id: string;
  title: string;
  questions: Question[];
}

interface Section {
  id: string;
  title: string;
  subsections: Subsection[];
}

const props = defineProps<{
  section: Section;
}>();

const answers = defineModel<Record<string, any>>({
  default: {},
});

// 初始化表格答案结构
const initializeTableAnswers = () => {
  props.section.subsections.forEach((subsection) => {
    subsection.questions.forEach((question) => {
      if (question.followUp) {
        question.followUp.questions.forEach((followUpQ) => {
          if (followUpQ.type === 'table' && !answers.value[followUpQ.id]) {
            answers.value[followUpQ.id] = followUpQ.rows?.map(() => ({})) || [];
          }
        });
      }
    });
  });
};

onMounted(() => {
  initializeTableAnswers();
});

const shouldShowFollowUp = (question: Question, answer: any) => {
  if (!question.followUp) return false;
  return answer === question.followUp.condition;
};

const getTableAnswer = (questionId: string, rowIndex: number, colIndex: number) => {
  if (!answers.value[questionId]) {
    answers.value[questionId] = [];
  }
  if (!answers.value[questionId][rowIndex]) {
    answers.value[questionId][rowIndex] = {};
  }
  return answers.value[questionId][rowIndex][colIndex];
};

const setTableAnswer = (
  questionId: string,
  rowIndex: number,
  colIndex: number,
  value: string
) => {
  if (!answers.value[questionId]) {
    answers.value[questionId] = [];
  }
  if (!answers.value[questionId][rowIndex]) {
    answers.value[questionId][rowIndex] = {};
  }
  answers.value[questionId][rowIndex][colIndex] = value;
};

const handleAnswerChange = (question: Question) => {
  // 当答案改变时，如果不满足后续问题条件，清空后续答案
  if (question.followUp && answers.value[question.id] !== question.followUp.condition) {
    question.followUp.questions.forEach((followUpQ) => {
      delete answers.value[followUpQ.id];
    });
  }
};
</script>
