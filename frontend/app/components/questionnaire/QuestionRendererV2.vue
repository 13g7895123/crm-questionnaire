<template>
  <div class="space-y-4" :id="`question-${question.id}`">
    <!-- 主問題 -->
    <div class="bg-white p-5 rounded-lg border border-gray-200">
      <div class="mb-3">
        <div class="block text-sm font-medium text-gray-800 mb-2">
          <span class="text-gray-500">{{ question.id }}.</span>
          {{ question.text }}
          <span v-if="question.required" class="text-red-500 ml-1">*</span>
        </div>
        <p v-if="question.config?.helpText" class="text-xs text-gray-500 mt-1">
          {{ question.config.helpText }}
        </p>
      </div>

      <!-- 根據類型渲染不同的輸入元件 -->
      <fieldset :disabled="mode === 'review'" class="mt-3">
        <BooleanQuestion
          v-if="question.type === 'BOOLEAN'"
          :model-value="getAnswerValue(question.id) as boolean | null"
          @update:model-value="updateAnswer(question.id, $event)"
        />

        <TextQuestion
          v-else-if="question.type === 'TEXT'"
          :model-value="getAnswerValue(question.id) as string"
          :config="question.config"
          @update:model-value="updateAnswer(question.id, $event)"
        />

        <NumberQuestion
          v-else-if="question.type === 'NUMBER'"
          :model-value="getAnswerValue(question.id) as number"
          :config="question.config"
          @update:model-value="updateAnswer(question.id, $event)"
        />

        <RadioQuestion
          v-else-if="question.type === 'RADIO'"
          :model-value="getAnswerValue(question.id) as string"
          :options="question.config?.options"
          @update:model-value="updateAnswer(question.id, $event)"
        />

        <CheckboxQuestion
          v-else-if="question.type === 'CHECKBOX'"
          :model-value="getAnswerValue(question.id) as string[]"
          :options="question.config?.options"
          @update:model-value="updateAnswer(question.id, $event)"
        />

        <SelectQuestion
          v-else-if="question.type === 'SELECT'"
          :model-value="getAnswerValue(question.id) as string"
          :options="question.config?.options"
          @update:model-value="updateAnswer(question.id, $event)"
        />

        <DateQuestion
          v-else-if="question.type === 'DATE'"
          :model-value="getAnswerValue(question.id) as string"
          @update:model-value="updateAnswer(question.id, $event)"
        />

        <FileUploadQuestion
          v-else-if="question.type === 'FILE'"
          :model-value="getAnswerValue(question.id)"
          @update:model-value="updateAnswer(question.id, $event)"
        />

        <TableQuestion
          v-else-if="question.type === 'TABLE'"
          :model-value="getAnswerValue(question.id) as any[]"
          :config="question.tableConfig"
          @update:model-value="updateAnswer(question.id, $event)"
        />

        <RatingQuestion
          v-else-if="question.type === 'RATING'"
          :model-value="getAnswerValue(question.id) as number"
          :config="question.config"
          @update:model-value="updateAnswer(question.id, $event)"
        />
      </fieldset>
      <div v-if="mode === 'review'" class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <div class="flex flex-col gap-3">
          <label class="text-sm font-semibold text-gray-700">審核 (Audit)</label>
          <div class="flex gap-4">
             <UButton
               :color="review?.approved === true ? 'green' : 'white'"
               variant="solid"
               icon="i-heroicons-check"
               label="Yes"
               size="sm"
               @click="updateReview(true)"
               class="flex-1 justify-center"
               :ui="{ color: { white: { solid: 'ring-1 ring-inset ring-gray-300 text-gray-900 bg-white hover:bg-gray-50' } } }"
             />
             <UButton
               :color="review?.approved === false ? 'red' : 'white'"
               variant="solid"
               icon="i-heroicons-x-mark"
               label="No"
               size="sm"
               @click="updateReview(false)"
               class="flex-1 justify-center"
               :ui="{ color: { white: { solid: 'ring-1 ring-inset ring-gray-300 text-gray-900 bg-white hover:bg-gray-50' } } }"
             />
          </div>
          <UTextarea
             :model-value="review?.comment"
             placeholder="請輸入審核意見..."
             :rows="2"
             class="w-full"
             @update:model-value="updateReviewComment"
          />
        </div>
      </div>
    </div>

    <!-- 條件邏輯追問 -->
    <div
      v-if="question.conditionalLogic?.followUpQuestions && shouldShowFollowUps(question)"
      class="ml-6 space-y-4 border-l-4 border-blue-400 pl-4"
    >
      <QuestionRendererV2
        v-for="followUpQuestion in getTriggeredFollowUps(question)"
        :key="followUpQuestion.id"
        :question="followUpQuestion"
        :answers="answers"
        :visible-questions="visibleQuestions"
        :mode="mode"
        :review="review"
        @update:answer="$emit('update:answer', $event)"
        @update:review="$emit('update:review', $event)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
defineOptions({
  name: 'QuestionRendererV2'
})

import type { Question, Answers, AnswerValue, QuestionReview } from '~/types/template-v2'
import BooleanQuestion from './types/BooleanQuestion.vue'
import TextQuestion from './types/TextQuestion.vue'
import NumberQuestion from './types/NumberQuestion.vue'
import RadioQuestion from './types/RadioQuestion.vue'
import CheckboxQuestion from './types/CheckboxQuestion.vue'
import SelectQuestion from './types/SelectQuestion.vue'
import DateQuestion from './types/DateQuestion.vue'
import FileUploadQuestion from './types/FileUploadQuestion.vue'
import TableQuestion from './types/TableQuestion.vue'
import RatingQuestion from './types/RatingQuestion.vue'

const props = defineProps<{
  question: Question
  answers: Answers
  visibleQuestions?: Set<string>
  mode?: 'preview' | 'fill' | 'review'
  review?: QuestionReview
}>()

const emit = defineEmits<{
  'update:answer': [payload: { questionId: string; value: AnswerValue }]
  'update:review': [payload: QuestionReview]
}>()

const getAnswerValue = (questionId: string): AnswerValue => {
  return props.answers[questionId]?.value
}

const updateAnswer = (questionId: string, value: AnswerValue) => {
  emit('update:answer', { questionId, value })
}

const updateReview = (approved: boolean) => {
  emit('update:review', {
    questionId: props.question.id,
    approved,
    comment: props.review?.comment
  })
}

const updateReviewComment = (comment: string) => {
  emit('update:review', {
    questionId: props.question.id,
    approved: props.review?.approved ?? true, // Default to true if not set
    comment
  })
}

// 檢查是否應該顯示追問
const shouldShowFollowUps = (question: Question): boolean => {
  if (!question.conditionalLogic?.followUpQuestions) return false

  const currentAnswer = getAnswerValue(question.id)
  if (currentAnswer === undefined || currentAnswer === null) return false

  return true
}

// 取得符合條件的追問
const getTriggeredFollowUps = (question: Question): Question[] => {
  if (!question.conditionalLogic?.followUpQuestions) return []

  const currentAnswer = getAnswerValue(question.id)
  const triggeredQuestions: Question[] = []

  for (const rule of question.conditionalLogic!.followUpQuestions) {
    if (evaluateCondition(currentAnswer, rule.condition)) {
      triggeredQuestions.push(...rule.questions)
    }
  }

  return triggeredQuestions
}

// 評估條件
const evaluateCondition = (value: AnswerValue, condition: any): boolean => {
  if (!condition) return false

  const { operator, value: conditionValue } = condition

  switch (operator) {
    case 'equals':
      return value === conditionValue

    case 'notEquals':
      return value !== conditionValue

    case 'contains':
      if (typeof value === 'string') {
        return value.includes(conditionValue)
      }
      if (Array.isArray(value)) {
        return value.includes(conditionValue)
      }
      return false

    case 'greaterThan':
      return typeof value === 'number' && value > conditionValue

    case 'lessThan':
      return typeof value === 'number' && value < conditionValue

    case 'greaterThanOrEqual':
      return typeof value === 'number' && value >= conditionValue

    case 'lessThanOrEqual':
      return typeof value === 'number' && value <= conditionValue

    case 'isEmpty':
      return !value || value === '' || (Array.isArray(value) && value.length === 0)

    case 'isNotEmpty':
      return !!value && value !== '' && (!Array.isArray(value) || value.length > 0)

    default:
      return false
  }
}
</script>
