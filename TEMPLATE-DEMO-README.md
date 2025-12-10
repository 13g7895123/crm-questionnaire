# CRM 問卷系統 - 範本管理 Demo

## 功能概述

這是一個純前端的範本管理與多步驟問卷系統 Demo，包含以下功能：

### ✨ 主要特性

1. **範本管理頁面** (`/templates`)
   - 顯示所有問卷範本
   - 支援不同類型範本（SAQ、CONFLICT）

2. **多步驟問卷填寫** (`/templates/[id]/preview`)
   - **第一步：公司基本資料**
     - 區塊一：公司資訊（公司全名、地址、總營收）
     - 區塊二：廠區資訊（支援多個廠區、員工人數統計、認證資訊）
     - 區塊三：聯絡信息（支援多位聯絡人）
   
   - **第二步到第五步：A-E 評估面向**
     - A. 勞工 (Labor)
     - B. 健康與安全 (Health & Safety)
     - C. 環境 (Environment)
     - D. 道德規範 (Ethics)
     - 每個面向包含多個小節（如 A.1、A.2）
     - 支援條件式問題（回答「是」時顯示後續問題）
     - 支援多種題型：
       - 是非題
       - 文字題
       - 數字題
       - 表格題（二維資料輸入）
   
   - **第六步：評估結果**
     - 五角形雷達圖（Canvas 繪製）
     - 分數統計表格
     - 等級評價系統

## 🚀 快速開始

### 前置需求

- Node.js 18+ 
- npm 或 yarn

### 安裝步驟

```bash
# 進入前端目錄
cd frontend

# 安裝依賴
npm install

# 啟動開發伺服器
npm run dev
```

### 訪問頁面

啟動後，在瀏覽器中訪問：

- **範本列表**: `http://localhost:3000/templates`
- **問卷填寫**: `http://localhost:3000/templates/1/preview`

## 📁 檔案結構

```
frontend/
└── app/
    ├── pages/
    │   └── templates/
    │       ├── index.vue              # 範本列表頁面
    │       └── [id]/
    │           └── preview.vue        # 問卷填寫主頁面（包含步驟控制）
    │
    └── components/
        └── questionnaire/
            ├── StepOneBasicInfo.vue       # 第一步：公司基本資料
            ├── StepDynamicQuestions.vue   # 第二到五步：動態問題
            └── StepResults.vue            # 第六步：結果展示（雷達圖）
```

## 🎯 功能詳解

### 1. 公司基本資料（第一步）

**區塊一：公司資訊**
- 公司全名
- 公司地址
- 公司總營收 (USD)

**區塊二：廠區資訊**（可新增多個廠區）
- 製造廠區全名
- 製造廠區地址
- 廠區員工人數（全職員工）：
  - 本國籍員工 - 男
  - 本國籍員工 - 女
  - 外國籍員工 - 男
  - 外國籍員工 - 女
  - 自動計算總計
- 提供的服務/產品項目
- 管理系統認證（多選）：
  - ISO 9001 (品質管理)
  - ISO 14001 (環境管理)
  - ISO 45001 (職業安全衛生)
  - IATF 16949 (汽車產業)
- RBA-Online System（已註冊/未註冊/規劃中）

**區塊三：聯絡信息**（可新增多位聯絡人）
- 聯絡人員
- 職稱
- Email

### 2. 動態問題（第二到五步）

每一步對應 A-E 的評估面向：

**A. 勞工 (Labor)**
- A.1 勞動管理
  - A.1.1 是否有制定並執行勞動政策？
    - 是 → 請描述勞動政策內容（文字題）
  - A.1.2 過去三年是否有違反勞動法規的紀錄？
    - 是 → 違規詳情（表格：年度、違犯件數、金額、事項、改善措施）
  - A.1.3 是否定期進行員工滿意度調查？
    - 是 → 最近一次調查的滿意度分數（數字題）
- A.2 工作時間
  - A.2.1 每週工作時數是否符合當地法規？
  - A.2.2 是否有加班管理制度？
    - 是 → 每月平均加班時數（數字題）

**B. 健康與安全 (Health & Safety)**
- B.1 職業安全
  - B.1.1 是否具有職業安全衛生管理系統認證（如 ISO 45001）？
  - B.1.2 過去一年是否發生重大工安事故？
    - 是 → 事故詳情（表格）

**C. 環境 (Environment)**
- C.1 環境管理
  - C.1.1 是否具有環境管理系統認證（如 ISO 14001）？
  - C.1.2 是否有溫室氣體排放管理計畫？
    - 是 → 年度碳排放量統計（表格）

**D. 道德規範 (Ethics)**
- D.1 商業誠信
  - D.1.1 是否制定反貪腐與反賄賂政策？
  - D.1.2 是否有揭弊者保護機制？
    - 是 → 請描述揭弊者保護機制的運作方式（文字題）
- D.2 資訊安全
  - D.2.1 是否具有資訊安全管理系統認證（如 ISO 27001）？

### 3. 評估結果（第六步）

**五角形雷達圖**
- 使用原生 Canvas API 繪製
- 最內層為 0 分，最外層為 100 分
- 共六層（0, 20, 40, 60, 80, 100）
- 五個角分別對應 A-E 評估面向
- 每個角使用不同顏色標示

**分數統計表格**
- 顯示各面向的得分、等級、評價
- 總分計算（五個面向的總和）
- 等級系統：
  - 90-100分：優秀（綠色）
  - 80-89分：良好（藍色）
  - 70-79分：尚可（黃色）
  - 60-69分：待改進（橙色）
  - 0-59分：不合格（紅色）

## 🎯 技術特點

### Nuxt 3 組件自動導入
Nuxt 3 會自動導入 `app/components/` 目錄下的組件，組件名稱基於目錄結構：
- `app/components/questionnaire/StepOneBasicInfo.vue` → `<QuestionnaireStepOneBasicInfo>`
- `app/components/questionnaire/StepDynamicQuestions.vue` → `<QuestionnaireStepDynamicQuestions>`
- `app/components/questionnaire/StepResults.vue` → `<QuestionnaireStepResults>`

無需手動 import，直接在模板中使用即可。

### 條件式問題邏輯
當使用者回答「是」時，自動顯示後續問題：
```typescript
// 範例：A.1.2 的後續問題
{
  id: 'A.1.2',
  text: '過去三年是否有違反勞動法規的紀錄？',
  type: 'boolean',
  followUp: {
    condition: true,  // 當答案為 true 時顯示
    questions: [
      {
        id: 'A.1.2.1',
        text: '違規詳情',
        type: 'table',
        columns: ['年度', '違犯件數', '金額(USD)', '違犯事項', '改善措施'],
        rows: ['2023', '2024', '2025']
      }
    ]
  }
}
```

### 表格題型處理
支援二維資料輸入，適合歷史資料記錄：
- 第一欄顯示行標題（如年度）
- 其他欄位可輸入資料
- 資料結構：`answers[questionId][rowIndex][colIndex]`

### 雷達圖繪製
使用原生 Canvas API：
1. 繪製背景網格（6 層同心五邊形）
2. 繪製座標軸線（5 條放射線）
3. 繪製資料區域（半透明填充）
4. 繪製資料點（彩色圓點）
5. 繪製標籤（各面向名稱）

### 分數計算邏輯
```typescript
// 簡化示例：根據「是」的答案數量計算分數
const aAnswers = Object.keys(step2Answers).filter(
  (key) => step2Answers[key] === true
).length;
scores.value[0].value = Math.min(100, aAnswers * 20);
```

## 🔧 自定義與擴展

### 新增評估面向
在 `app/pages/templates/[id]/preview.vue` 的 `sections` 陣列中新增：
```typescript
{
  id: 'E',
  title: 'E. 管理系統',
  subsections: [...]
}
```

### 新增題型
在 `app/components/questionnaire/StepDynamicQuestions.vue` 中擴展：
1. 在 `Question` interface 新增類型
2. 在模板中新增對應的 `v-if` 區塊
3. 處理答案的儲存邏輯

### 修改分數計算
在 `app/components/questionnaire/StepResults.vue` 的 `calculateScores()` 函數中：
```typescript
const calculateScores = () => {
  // 自定義計分邏輯
  // 可根據不同題目類型、權重等進行計算
};
```

## 📝 注意事項

1. **純前端 Demo**：所有資料僅儲存在記憶體中，重新整理後會遺失
2. **範例資料**：分數計算使用簡化邏輯，實際應用需根據業務需求調整
3. **表單驗證**：目前未實作完整驗證，可根據需求新增
4. **RWD 響應式**：已使用 Tailwind CSS 的響應式類別，支援手機、平板、桌面

## 🎯 後續建議

1. **整合後端 API**：連接實際的 API 端點（參考 `backend/docs/API-SPECIFICATION.md`）
2. **表單驗證**：使用 Vee-Validate 或 Zod 進行驗證
3. **狀態管理**：使用 Pinia store 管理問卷狀態
4. **自動儲存**：實作定時自動儲存草稿功能
5. **檔案上傳**：新增檔案上傳題型（File type）
6. **匯出功能**：匯出 PDF 或 Excel 報告
7. **權限控制**：整合角色權限系統

## 📚 相關文件

- [API 規格文件](../backend/docs/API-SPECIFICATION.md)
- [前端 API 需求](./docs/)
  - [專案管理 API](./docs/api-projects.md)
  - [問卷填寫 API](./docs/api-answers.md)
  - [審核流程 API](./docs/api-reviews.md)

## 🐛 問題回報

如有任何問題或建議，請在專案中提出 Issue。
