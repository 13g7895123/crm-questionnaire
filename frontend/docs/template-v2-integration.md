# Template v2.0 前端整合指南

**版本**: 2.0.0  
**最後更新**: 2025-12-04

## 概述

Template v2.0 引入了新的階層式問卷架構（Section → Subsection → Question），支援條件邏輯、表格問題、動態分數計算等進階功能。本文件說明前端如何整合這些新功能。

---

## 1. 核心概念

### 1.1 階層架構

```
Template
  └─ Structure
      ├─ Basic Info (基本資訊，可選)
      └─ Sections (區段)
          └─ Subsections (子區段)
              └─ Questions (問題)
                  └─ Follow-up Questions (追問，條件式)
```

### 1.2 問題類型

| 類型 | 說明 | 答案值類型 | 用途 |
|------|------|-----------|------|
| `BOOLEAN` | 是非題 | `boolean` | 簡單的是/否判斷 |
| `TEXT` | 簡答題 | `string` | 開放式文字回答 |
| `NUMBER` | 數字題 | `number` | 數值輸入 |
| `RADIO` | 單選題 | `string` | 單一選項選擇 |
| `CHECKBOX` | 複選題 | `string[]` | 多個選項選擇 |
| `SELECT` | 下拉選單 | `string` | 較多選項的單選 |
| `DATE` | 日期題 | `string` | 日期選擇 |
| `FILE` | 檔案上傳 | `string` | 檔案 URL |
| `TABLE` | 表格題 | `Array<Record<string, any>>` | 結構化多筆資料 |

### 1.3 條件邏輯

條件邏輯允許根據答案動態顯示/隱藏問題：

```typescript
interface ConditionalLogic {
  // 顯示條件：此問題何時顯示
  showWhen?: {
    questionId: string;
    condition: {
      operator: ConditionalOperator;
      value: any;
    };
  };
  
  // 追問規則：回答後觸發的問題
  followUpQuestions?: Array<{
    condition: Condition;
    questions: Question[];
  }>;
}
```

**支援的運算子**：
- `equals` / `notEquals` - 等於/不等於
- `contains` - 包含（用於字串或陣列）
- `greaterThan` / `lessThan` - 大於/小於
- `greaterThanOrEqual` / `lessThanOrEqual` - 大於等於/小於等於
- `isEmpty` / `isNotEmpty` - 為空/不為空

---

## 2. TypeScript 型別定義

已提供完整的型別定義檔案：

```typescript
import type {
  Template,
  TemplateStructure,
  Section,
  Subsection,
  Question,
  BasicInfo,
  Answer,
  Answers,
  ScoreData,
  ValidationResult,
} from '~/types/template-v2';
```

**檔案位置**: `frontend/app/types/template-v2.ts`

---

## 3. API 整合

### 3.1 取得範本結構

```typescript
// Composable: useTemplate.ts
export const useTemplate = () => {
  const getTemplateStructure = async (templateId: number) => {
    const { data } = await useFetch<ApiResponse<GetTemplateStructureResponse>>(
      `/api/v1/templates/${templateId}/structure`
    );
    
    return data.value?.data;
  };
  
  return {
    getTemplateStructure
  };
};
```

### 3.2 基本資訊管理

```typescript
// Composable: useBasicInfo.ts
export const useBasicInfo = (projectSupplierId: number) => {
  const getBasicInfo = async () => {
    const { data } = await useFetch<ApiResponse<GetBasicInfoResponse>>(
      `/api/v1/project-suppliers/${projectSupplierId}/basic-info`
    );
    
    return data.value?.data?.basicInfo;
  };
  
  const saveBasicInfo = async (basicInfo: BasicInfo) => {
    await useFetch(
      `/api/v1/project-suppliers/${projectSupplierId}/basic-info`,
      {
        method: 'PUT',
        body: basicInfo
      }
    );
  };
  
  return {
    getBasicInfo,
    saveBasicInfo
  };
};
```

### 3.3 答案管理

```typescript
// Composable: useAnswers.ts
export const useAnswers = (projectSupplierId: number) => {
  const answers = ref<Answers>({});
  
  const loadAnswers = async () => {
    const { data } = await useFetch<ApiResponse<GetAnswersResponse>>(
      `/api/v1/project-suppliers/${projectSupplierId}/answers`
    );
    
    if (data.value?.data) {
      answers.value = data.value.data.answers;
    }
  };
  
  const saveAnswers = async () => {
    await useFetch(
      `/api/v1/project-suppliers/${projectSupplierId}/answers`,
      {
        method: 'PUT',
        body: { answers: answers.value }
      }
    );
  };
  
  const updateAnswer = (questionId: string, value: AnswerValue) => {
    answers.value[questionId] = { questionId, value };
  };
  
  return {
    answers,
    loadAnswers,
    saveAnswers,
    updateAnswer
  };
};
```

### 3.4 條件邏輯處理

```typescript
// Composable: useConditionalLogic.ts
export const useConditionalLogic = (projectSupplierId: number) => {
  const visibleQuestions = ref<Set<string>>(new Set());
  
  const loadVisibleQuestions = async () => {
    const { data } = await useFetch<ApiResponse<GetVisibleQuestionsResponse>>(
      `/api/v1/project-suppliers/${projectSupplierId}/visible-questions`
    );
    
    if (data.value?.data) {
      visibleQuestions.value = new Set(data.value.data.visibleQuestions);
    }
  };
  
  const isQuestionVisible = (questionId: string) => {
    return visibleQuestions.value.has(questionId);
  };
  
  return {
    visibleQuestions,
    loadVisibleQuestions,
    isQuestionVisible
  };
};
```

### 3.5 分數計算

```typescript
// Composable: useScoring.ts
export const useScoring = (projectSupplierId: number) => {
  const scoreData = ref<ScoreData | null>(null);
  
  const calculateScore = async () => {
    const { data } = await useFetch<ApiResponse<ScoreData>>(
      `/api/v1/project-suppliers/${projectSupplierId}/calculate-score`,
      { method: 'POST' }
    );
    
    if (data.value?.data) {
      scoreData.value = data.value.data;
    }
  };
  
  return {
    scoreData,
    calculateScore
  };
};
```

### 3.6 答案驗證

```typescript
// Composable: useValidation.ts
export const useValidation = (projectSupplierId: number) => {
  const validationResult = ref<ValidationResult | null>(null);
  
  const validateAnswers = async () => {
    const { data } = await useFetch<ApiResponse<ValidateAnswersResponse>>(
      `/api/v1/project-suppliers/${projectSupplierId}/validate`,
      { method: 'POST' }
    );
    
    if (data.value?.data) {
      validationResult.value = {
        valid: data.value.data.valid,
        errors: data.value.data.errors
      };
    }
    
    return validationResult.value;
  };
  
  return {
    validationResult,
    validateAnswers
  };
};
```

---

## 4. 元件設計

### 4.1 問卷主容器

```vue
<!-- components/questionnaire/QuestionnaireContainer.vue -->
<template>
  <div class="questionnaire-container">
    <!-- 進度條 -->
    <QuestionnaireProgress
      :current-step="currentStep"
      :total-steps="totalSteps"
      :sections="sections"
    />
    
    <!-- 步驟內容 -->
    <div v-if="currentStep === 0">
      <BasicInfoForm
        v-model="basicInfo"
        @save="handleSaveBasicInfo"
      />
    </div>
    
    <div v-else-if="currentStep <= sections.length">
      <SectionForm
        :section="currentSection"
        :answers="answers"
        :visible-questions="visibleQuestions"
        @update:answer="handleAnswerUpdate"
        @save="handleSaveAnswers"
      />
    </div>
    
    <div v-else>
      <QuestionnaireResults
        :score-data="scoreData"
        @submit="handleSubmit"
      />
    </div>
    
    <!-- 導航按鈕 -->
    <div class="navigation">
      <button @click="handlePrevious" :disabled="currentStep === 0">
        上一步
      </button>
      <button @click="handleNext" :disabled="!canProceed">
        {{ currentStep === totalSteps ? '提交' : '下一步' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  projectSupplierId: number;
  templateId: number;
}>();

const { getTemplateStructure } = useTemplate();
const { getBasicInfo, saveBasicInfo } = useBasicInfo(props.projectSupplierId);
const { answers, loadAnswers, saveAnswers, updateAnswer } = useAnswers(props.projectSupplierId);
const { visibleQuestions, loadVisibleQuestions } = useConditionalLogic(props.projectSupplierId);
const { scoreData, calculateScore } = useScoring(props.projectSupplierId);

const currentStep = ref(0);
const structure = ref<TemplateStructure | null>(null);
const basicInfo = ref<Partial<BasicInfo>>({});

const sections = computed(() => structure.value?.sections || []);
const totalSteps = computed(() => {
  let steps = sections.value.length;
  if (structure.value?.includeBasicInfo) steps++;
  steps++; // Results page
  return steps;
});

const currentSection = computed(() => {
  const sectionIndex = currentStep.value - (structure.value?.includeBasicInfo ? 1 : 0);
  return sections.value[sectionIndex];
});

const canProceed = computed(() => {
  // 實作進入下一步的條件檢查
  return true;
});

onMounted(async () => {
  const data = await getTemplateStructure(props.templateId);
  if (data) {
    structure.value = data.structure;
  }
  
  await loadAnswers();
  await loadVisibleQuestions();
  
  if (structure.value?.includeBasicInfo) {
    const info = await getBasicInfo();
    if (info) {
      basicInfo.value = info;
    }
  }
});

const handleAnswerUpdate = async (questionId: string, value: AnswerValue) => {
  updateAnswer(questionId, value);
  // 答案更新後重新計算可見問題
  await loadVisibleQuestions();
};

const handleSaveBasicInfo = async () => {
  await saveBasicInfo(basicInfo.value as BasicInfo);
};

const handleSaveAnswers = async () => {
  await saveAnswers();
};

const handleNext = async () => {
  if (currentStep.value === totalSteps.value - 1) {
    // 最後一步，計算分數
    await calculateScore();
  }
  currentStep.value++;
};

const handlePrevious = () => {
  if (currentStep.value > 0) {
    currentStep.value--;
  }
};

const handleSubmit = async () => {
  // 提交問卷
  const validation = await validateAnswers();
  if (validation?.valid) {
    // 執行提交邏輯
  }
};
</script>
```

### 4.2 區段表單

```vue
<!-- components/questionnaire/SectionForm.vue -->
<template>
  <div class="section-form">
    <h2>{{ section.title }}</h2>
    <p v-if="section.description" class="description">
      {{ section.description }}
    </p>
    
    <div v-for="subsection in section.subsections" :key="subsection.id">
      <h3>{{ subsection.title }}</h3>
      <p v-if="subsection.description" class="description">
        {{ subsection.description }}
      </p>
      
      <QuestionField
        v-for="question in visibleQuestionsInSubsection(subsection)"
        :key="question.id"
        :question="question"
        :value="answers[question.id]?.value"
        @update:value="handleUpdate(question.id, $event)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  section: Section;
  answers: Answers;
  visibleQuestions: Set<string>;
}>();

const emit = defineEmits<{
  'update:answer': [questionId: string, value: AnswerValue];
}>();

const visibleQuestionsInSubsection = (subsection: Subsection) => {
  return subsection.questions.filter(q => props.visibleQuestions.has(q.id));
};

const handleUpdate = (questionId: string, value: AnswerValue) => {
  emit('update:answer', questionId, value);
};
</script>
```

### 4.3 問題欄位元件

```vue
<!-- components/questionnaire/QuestionField.vue -->
<template>
  <div class="question-field" :class="{ required: question.required }">
    <label>
      {{ question.text }}
      <span v-if="question.required" class="required-mark">*</span>
    </label>
    
    <!-- 根據類型渲染不同的輸入元件 -->
    <BooleanInput
      v-if="question.type === 'BOOLEAN'"
      :model-value="value"
      @update:model-value="handleUpdate"
    />
    
    <TextInput
      v-else-if="question.type === 'TEXT'"
      :model-value="value"
      :config="question.config"
      @update:model-value="handleUpdate"
    />
    
    <NumberInput
      v-else-if="question.type === 'NUMBER'"
      :model-value="value"
      :config="question.config"
      @update:model-value="handleUpdate"
    />
    
    <RadioInput
      v-else-if="question.type === 'RADIO'"
      :model-value="value"
      :options="question.config?.options"
      @update:model-value="handleUpdate"
    />
    
    <CheckboxInput
      v-else-if="question.type === 'CHECKBOX'"
      :model-value="value"
      :options="question.config?.options"
      @update:model-value="handleUpdate"
    />
    
    <SelectInput
      v-else-if="question.type === 'SELECT'"
      :model-value="value"
      :options="question.config?.options"
      @update:model-value="handleUpdate"
    />
    
    <DateInput
      v-else-if="question.type === 'DATE'"
      :model-value="value"
      @update:model-value="handleUpdate"
    />
    
    <FileInput
      v-else-if="question.type === 'FILE'"
      :model-value="value"
      @update:model-value="handleUpdate"
    />
    
    <TableInput
      v-else-if="question.type === 'TABLE'"
      :model-value="value"
      :config="question.tableConfig"
      @update:model-value="handleUpdate"
    />
    
    <p v-if="question.config?.helpText" class="help-text">
      {{ question.config.helpText }}
    </p>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  question: Question;
  value: AnswerValue;
}>();

const emit = defineEmits<{
  'update:value': [value: AnswerValue];
}>();

const handleUpdate = (value: AnswerValue) => {
  emit('update:value', value);
};
</script>
```

### 4.4 表格輸入元件

```vue
<!-- components/questionnaire/inputs/TableInput.vue -->
<template>
  <div class="table-input">
    <table>
      <thead>
        <tr>
          <th v-for="column in config?.columns" :key="column.id">
            {{ column.label }}
            <span v-if="column.required" class="required-mark">*</span>
          </th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, index) in rows" :key="index">
          <td v-for="column in config?.columns" :key="column.id">
            <input
              v-model="row[column.id]"
              :type="column.type"
              :required="column.required"
              @input="handleRowUpdate"
            />
          </td>
          <td>
            <button @click="removeRow(index)" :disabled="!canRemoveRow">
              刪除
            </button>
          </td>
        </tr>
      </tbody>
    </table>
    
    <button @click="addRow" :disabled="!canAddRow">
      新增列
    </button>
    
    <p class="hint">
      需要 {{ config?.minRows || 0 }} - {{ config?.maxRows || '無限制' }} 筆資料，
      目前 {{ rows.length }} 筆
    </p>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  modelValue: TableAnswer[];
  config?: TableConfig;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: TableAnswer[]];
}>();

const rows = ref<TableAnswer[]>(props.modelValue || []);

const canAddRow = computed(() => {
  if (!props.config?.maxRows) return true;
  return rows.value.length < props.config.maxRows;
});

const canRemoveRow = computed(() => {
  if (!props.config?.minRows) return rows.value.length > 0;
  return rows.value.length > props.config.minRows;
});

const addRow = () => {
  const newRow: TableAnswer = {};
  props.config?.columns.forEach(col => {
    newRow[col.id] = '';
  });
  rows.value.push(newRow);
  handleRowUpdate();
};

const removeRow = (index: number) => {
  rows.value.splice(index, 1);
  handleRowUpdate();
};

const handleRowUpdate = () => {
  emit('update:modelValue', rows.value);
};

watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    rows.value = newValue;
  }
}, { deep: true });
</script>
```

---

## 5. 完整流程範例

### 5.1 問卷填寫流程

```typescript
// pages/projects/[id]/suppliers/[supplierId]/questionnaire.vue
<script setup lang="ts">
const route = useRoute();
const projectSupplierId = Number(route.params.supplierId);
const projectId = Number(route.params.id);

// 1. 載入專案資訊
const { data: project } = await useFetch(`/api/v1/projects/${projectId}`);
const templateId = project.value?.data.templateId;

// 2. 載入範本結構
const { getTemplateStructure } = useTemplate();
const structure = await getTemplateStructure(templateId);

// 3. 初始化表單狀態
const formState = reactive<FormState>({
  currentStep: 0,
  totalSteps: calculateTotalSteps(structure),
  basicInfo: {},
  answers: {},
  visibleQuestions: new Set(),
  isDirty: false,
  isSaving: false
});

// 4. 載入已儲存的資料
const { getBasicInfo } = useBasicInfo(projectSupplierId);
const { loadAnswers } = useAnswers(projectSupplierId);
const { loadVisibleQuestions } = useConditionalLogic(projectSupplierId);

onMounted(async () => {
  if (structure.includeBasicInfo) {
    formState.basicInfo = await getBasicInfo() || {};
  }
  formState.answers = await loadAnswers();
  formState.visibleQuestions = new Set(await loadVisibleQuestions());
});

// 5. 自動儲存
const { saveAnswers } = useAnswers(projectSupplierId);
const debouncedSave = useDebounceFn(async () => {
  if (formState.isDirty) {
    formState.isSaving = true;
    await saveAnswers();
    formState.isDirty = false;
    formState.isSaving = false;
  }
}, 2000);

watch(() => formState.answers, () => {
  formState.isDirty = true;
  debouncedSave();
}, { deep: true });

// 6. 提交前驗證
const { validateAnswers } = useValidation(projectSupplierId);
const handleSubmit = async () => {
  const validation = await validateAnswers();
  
  if (!validation?.valid) {
    // 顯示錯誤訊息
    showValidationErrors(validation.errors);
    return;
  }
  
  // 執行提交
  await useFetch(`/api/v1/project-suppliers/${projectSupplierId}/submit`, {
    method: 'POST'
  });
  
  // 導向結果頁面
  navigateTo(`/projects/${projectId}/suppliers/${projectSupplierId}/results`);
};
</script>
```

---

## 6. 注意事項

### 6.1 效能優化

1. **條件邏輯計算**：
   - 答案更新後立即重新計算可見問題
   - 使用 debounce 避免過於頻繁的 API 呼叫

2. **自動儲存**：
   - 使用 debounce 延遲儲存（建議 2-3 秒）
   - 顯示儲存狀態指示器

3. **資料載入**：
   - 使用骨架屏提升載入體驗
   - 大型範本考慮分段載入

### 6.2 錯誤處理

1. **驗證錯誤**：
   - 在問題旁顯示具體錯誤訊息
   - 自動滾動到第一個錯誤位置

2. **網路錯誤**：
   - 提供重試機制
   - 本地暫存避免資料遺失

3. **條件邏輯錯誤**：
   - 前端應與後端邏輯保持一致
   - 開發模式顯示除錯資訊

### 6.3 使用者體驗

1. **進度指示**：
   - 明確顯示當前步驟和總步驟數
   - 顯示各區段完成度

2. **即時反饋**：
   - 答案變更後立即顯示相關的追問
   - 分數計算提供即時預覽

3. **資料保護**：
   - 離開頁面前提醒未儲存的變更
   - 提供草稿自動復原功能

---

## 7. 測試建議

### 7.1 單元測試

```typescript
// tests/unit/composables/useConditionalLogic.spec.ts
describe('useConditionalLogic', () => {
  it('should correctly evaluate equals condition', () => {
    const { evaluateCondition } = useConditionalLogic(1);
    
    const condition = {
      operator: 'equals' as const,
      value: true
    };
    
    expect(evaluateCondition(true, condition)).toBe(true);
    expect(evaluateCondition(false, condition)).toBe(false);
  });
  
  // 更多測試...
});
```

### 7.2 整合測試

```typescript
// tests/integration/questionnaire-flow.spec.ts
describe('Questionnaire Flow', () => {
  it('should complete full questionnaire flow', async () => {
    // 1. 載入範本
    // 2. 填寫基本資訊
    // 3. 回答所有問題
    // 4. 觸發條件邏輯
    // 5. 計算分數
    // 6. 驗證答案
    // 7. 提交問卷
  });
});
```

### 7.3 E2E 測試

```typescript
// tests/e2e/questionnaire.spec.ts
test('supplier can complete SAQ questionnaire', async ({ page }) => {
  // 登入
  await page.goto('/login');
  await page.fill('input[name="username"]', 'supplier@example.com');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  
  // 進入問卷
  await page.goto('/projects/1/suppliers/1/questionnaire');
  
  // 填寫基本資訊
  await page.fill('input[name="companyName"]', 'Test Company');
  // ...
  
  // 填寫問題
  await page.click('input[type="radio"][value="yes"]');
  // ...
  
  // 提交
  await page.click('button:has-text("提交")');
  
  // 驗證成功
  await expect(page).toHaveURL(/\/results/);
});
```

---

## 8. 常見問題

**Q: 條件邏輯何時重新計算？**  
A: 每次答案更新後應立即呼叫 `loadVisibleQuestions()` API 重新計算。

**Q: 表格問題的資料格式？**  
A: 陣列格式，每個元素是一個物件，鍵為欄位 ID，值為該欄位的答案。

**Q: 如何處理巢狀的追問？**  
A: 後端會遞迴處理，前端只需根據 `visibleQuestions` 清單顯示/隱藏即可。

**Q: 分數計算的時機？**  
A: 建議在最後一步（結果頁面）載入時計算，也可以在每次儲存後提供即時預覽。

**Q: 驗證應該在前端還是後端？**  
A: 兩者都需要。前端提供即時反饋，後端作為最終驗證保障資料完整性。

---

**文件維護者**: CRM Questionnaire Team  
**最後更新**: 2025-12-04
