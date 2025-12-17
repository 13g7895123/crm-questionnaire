<template>
  <div class="space-y-8">
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-800">{{ section.id }}. {{ section.title }}</h2>
      <p v-if="section.description" class="text-gray-600 mt-2">
        {{ section.description }}
      </p>
    </div>

    <!-- 子區段 -->
    <div
      v-for="subsection in section.subsections"
      :key="subsection.id"
      class="border-b border-gray-200 pb-8 last:border-b-0"
    >
      <div class="mb-6">
        <h3 class="text-xl font-semibold text-blue-700">
          {{ subsection.id }}. {{ subsection.title }}
        </h3>
        <p v-if="subsection.description" class="text-gray-600 text-sm mt-1">
          {{ subsection.description }}
        </p>
      </div>

      <!-- 問題列表 -->
      <div class="space-y-6">
        <QuestionRendererV2
          v-for="question in getVisibleQuestions(subsection.questions)"
          :key="question.id"
          :question="question"
          :answers="answers"
          :visible-questions="visibleQuestions"
          @update:answer="handleAnswerUpdate"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Section, Question, Answers, AnswerValue } from '~/types/template-v2'
import QuestionRendererV2 from './QuestionRendererV2.vue'

const props = defineProps<{
  section: Section
  answers: Answers
  visibleQuestions?: Set<string>
}>()

const emit = defineEmits<{
  'update:answer': [payload: { questionId: string; value: AnswerValue }]
}>()

// 過濾可見問題
const getVisibleQuestions = (questions: Question[]): Question[] => {
  if (!props.visibleQuestions) return questions

  return questions.filter((q) => {
    // 如果問題有 showWhen 條件，檢查是否應該顯示
    if (q.conditionalLogic?.showWhen) {
      return props.visibleQuestions?.has(q.id)
    }
    // 沒有條件的問題預設顯示
    return true
  })
}

const handleAnswerUpdate = (payload: { questionId: string; value: AnswerValue }) => {
  emit('update:answer', payload)
}
</script>
