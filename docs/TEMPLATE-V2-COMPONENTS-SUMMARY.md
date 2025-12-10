# Template v2.0 元件開發完成總結

**完成日期**: 2025-12-10  
**狀態**: ✅ 已完成並整合到 `/saq/templates/[id]` 頁面

---

## 🎯 完成項目

### 1. 新建元件 (8 個)

#### 問題類型元件
| 元件 | 檔案路徑 | 用途 |
|------|---------|------|
| `TableQuestion` | `components/questionnaire/types/TableQuestion.vue` | 表格問題輸入（支援動態行數、多種欄位類型） |
| `RadioQuestion` | `components/questionnaire/types/RadioQuestion.vue` | 單選題（RADIO 類型） |
| `CheckboxQuestion` | `components/questionnaire/types/CheckboxQuestion.vue` | 複選題（CHECKBOX 類型） |
| `SelectQuestion` | `components/questionnaire/types/SelectQuestion.vue` | 下拉選單（SELECT 類型） |

#### 核心渲染元件
| 元件 | 檔案路徑 | 用途 |
|------|---------|------|
| `QuestionRendererV2` | `components/questionnaire/QuestionRendererV2.vue` | v2.0 問題渲染器（支援所有問題類型 + 條件邏輯） |
| `SectionFormV2` | `components/questionnaire/SectionFormV2.vue` | 區段表單（渲染 Section → Subsection → Question 結構） |
| `BasicInfoFormV2` | `components/questionnaire/BasicInfoFormV2.vue` | v2.0 基本資訊表單（公司資料、員工、設施、認證、聯絡人） |

### 2. 更新頁面 (1 個)

| 頁面 | 檔案路徑 | 變更內容 |
|------|---------|----------|
| SAQ 範本預覽 | `pages/saq/templates/[id].vue` | 完整重寫，整合所有 v2.0 元件 |

---

## 📋 元件功能詳細說明

### TableQuestion.vue
**功能**:
- ✅ 動態新增/刪除列
- ✅ 支援 4 種欄位類型（text, number, date, email）
- ✅ 必填欄位標記
- ✅ 行數限制驗證（minRows, maxRows）
- ✅ 即時驗證提示

**API 支援**:
```typescript
interface TableConfig {
  columns: TableColumn[]
  minRows?: number
  maxRows?: number
}
```

### QuestionRendererV2.vue
**功能**:
- ✅ 支援所有 9 種問題類型
- ✅ 條件邏輯評估（9 種運算子）
- ✅ 動態顯示追問（followUpQuestions）
- ✅ 遞迴渲染巢狀問題
- ✅ 必填標記

**支援的條件運算子**:
- `equals` / `notEquals`
- `contains`
- `greaterThan` / `lessThan`
- `greaterThanOrEqual` / `lessThanOrEqual`
- `isEmpty` / `isNotEmpty`

### SectionFormV2.vue
**功能**:
- ✅ 渲染區段標題與描述
- ✅ 渲染子區段結構
- ✅ 過濾可見問題
- ✅ 答案更新事件傳遞

### BasicInfoFormV2.vue
**功能**:
- ✅ 公司基本資訊（名稱、地址）
- ✅ 員工統計（總數、男女、外籍）
- ✅ 設施管理（動態新增/刪除）
- ✅ 認證清單（標籤式輸入）
- ✅ RBA 會員狀態
- ✅ 聯絡人管理（動態新增/刪除）
- ✅ 完整的表單驗證

---

## 🚀 整合到 `/saq/templates/[id]` 頁面

### 頁面功能

#### 1. 範本載入
```typescript
// 從 API 載入範本結構
GET /api/v1/templates/{id}/structure

// 動態生成步驟清單
steps = [基本資訊?, ...區段列表, 預覽完成]
```

#### 2. 步驟導航
- ✅ 進度指示器（1/6, 2/6...）
- ✅ 視覺化步驟流程
- ✅ 上一步/下一步按鈕
- ✅ 完成預覽按鈕

#### 3. 表單渲染
```vue
<!-- 步驟 1: 基本資訊 -->
<BasicInfoFormV2 v-model="formData.basicInfo" />

<!-- 步驟 2-N: 各區段 -->
<SectionFormV2 
  :section="currentSection"
  :answers="formData.answers"
  @update:answer="handleAnswerUpdate"
/>

<!-- 最後一步: 預覽完成 -->
<div>顯示範本統計資訊</div>
```

#### 4. 狀態管理
```typescript
const formData = reactive({
  basicInfo: {},      // 基本資訊
  answers: {},        // 所有答案
})

const visibleQuestions = ref<Set<string>>(new Set())
```

#### 5. 條件邏輯處理
- ✅ 初始化可見問題清單
- ✅ 答案更新時重新計算可見問題
- ✅ 自動隱藏/顯示條件式問題

---

## 📱 使用方式

### 訪問頁面

1. **開發環境**:
   ```
   http://localhost:3000/saq/templates/4
   ```

2. **從範本列表進入**:
   - 訪問 `/saq/templates`
   - 點擊任意範本
   - 自動跳轉到預覽頁面

### 操作流程

1. **步驟 1: 基本資訊**
   - 填寫公司名稱（必填）
   - 填寫員工統計（必填）
   - 新增至少一個設施（必填）
   - 新增至少一位聯絡人（必填）
   - 新增認證（選填）
   - 選擇 RBA 會員狀態（選填）

2. **步驟 2-6: 區段問題**
   - 依序填寫 A-E 五個區段
   - 根據答案自動顯示/隱藏追問
   - 表格問題可動態新增列

3. **最後一步: 預覽完成**
   - 查看範本統計資訊
   - 點擊「完成預覽」返回列表

---

## 🎨 UI/UX 特色

### 視覺設計
- ✅ 現代化的卡片式設計
- ✅ 清晰的步驟指示器
- ✅ 必填欄位紅色星號標記
- ✅ 灰色背景區隔不同區塊
- ✅ 藍色高亮當前步驟

### 互動體驗
- ✅ 平滑的步驟切換
- ✅ 即時表單驗證
- ✅ 條件邏輯自動觸發
- ✅ 表格行數動態管理
- ✅ 標籤式認證輸入
- ✅ 載入狀態提示

### 響應式設計
- ✅ 桌面版：多欄位並排
- ✅ 平板版：適度調整欄位
- ✅ 手機版：單欄顯示

---

## 🔧 技術實作細節

### 1. TypeScript 型別支援
```typescript
import type {
  TemplateStructure,
  Section,
  Subsection,
  Question,
  Answers,
  AnswerValue,
  BasicInfo
} from '~/types/template-v2'
```

### 2. Vue 3 Composition API
- 使用 `<script setup>` 語法
- 使用 `ref` 和 `reactive` 管理狀態
- 使用 `computed` 計算衍生狀態
- 使用 `watch` 監聽變化

### 3. 事件驅動架構
```typescript
// 答案更新事件
emit('update:answer', { questionId, value })

// 雙向綁定
v-model="formData.basicInfo"
```

### 4. 條件邏輯評估
```typescript
const evaluateCondition = (value, condition) => {
  switch (condition.operator) {
    case 'equals': return value === condition.value
    case 'contains': return value.includes(condition.value)
    // ... 9 種運算子
  }
}
```

---

## 📊 元件關係圖

```
pages/saq/templates/[id].vue
├─ BasicInfoFormV2.vue (步驟 1)
├─ SectionFormV2.vue (步驟 2-N)
│   └─ QuestionRendererV2.vue
│       ├─ BooleanQuestion.vue
│       ├─ TextQuestion.vue
│       ├─ NumberQuestion.vue
│       ├─ RadioQuestion.vue
│       ├─ CheckboxQuestion.vue
│       ├─ SelectQuestion.vue
│       ├─ DateQuestion.vue
│       ├─ FileUploadQuestion.vue
│       ├─ TableQuestion.vue
│       ├─ RatingQuestion.vue
│       └─ QuestionRendererV2.vue (遞迴追問)
└─ 預覽完成頁面 (最後一步)
```

---

## ✅ 測試檢查清單

### 功能測試
- [x] 載入範本結構 API
- [x] 動態生成步驟清單
- [x] 基本資訊表單填寫
- [x] 所有問題類型渲染
- [x] 條件邏輯觸發
- [x] 表格動態新增/刪除
- [x] 步驟導航
- [x] 答案狀態管理

### 視覺測試
- [x] 步驟指示器樣式
- [x] 表單佈局
- [x] 必填標記顯示
- [x] 追問縮排樣式
- [x] 按鈕狀態變化
- [x] 載入/錯誤狀態

### 響應式測試
- [ ] 桌面版（1920x1080）
- [ ] 平板版（768x1024）
- [ ] 手機版（375x667）

---

## 🐛 已知限制

1. **條件邏輯計算**: 目前在前端本地計算，實際應用應呼叫後端 API
2. **答案驗證**: 預覽模式下不進行完整驗證
3. **自動儲存**: 預覽模式不儲存答案
4. **分數計算**: 預覽模式不計算分數

---

## 🎯 後續開發建議

### 優先度 1: 完善預覽功能
- [ ] 整合 `/visible-questions` API
- [ ] 整合 `/validate` API
- [ ] 整合 `/calculate-score` API
- [ ] 新增分數預覽功能

### 優先度 2: 實際問卷填寫
- [ ] 建立 `/projects/{id}/suppliers/{supplierId}/questionnaire` 頁面
- [ ] 整合自動儲存功能
- [ ] 整合提交功能
- [ ] 新增草稿復原功能

### 優先度 3: 範本編輯
- [ ] 建立範本編輯器
- [ ] 視覺化條件邏輯編輯
- [ ] 表格欄位配置介面
- [ ] 範本預覽即時更新

---

## 📚 相關文件

- `frontend/app/types/template-v2.ts` - TypeScript 型別定義
- `backend/docs/API-SPECIFICATION.md` - API 文件
- `frontend/docs/template-v2-integration.md` - 整合指南
- `docs/TEMPLATE-PREVIEW-GUIDE.md` - 預覽功能指南

---

**開發人員**: AI Assistant  
**最後更新**: 2025-12-10  
**版本**: v2.0.0
