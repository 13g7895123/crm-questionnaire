# 任務清單：CRM 問卷系統 (CRM Questionnaire System)

**輸入文件**：設計文件來自 `/specs/003-crm-questionnaire/`
**前置條件**：plan.md ✓, spec.md ✓, research.md ✓, data-model.md ✓, contracts/ ✓

**測試策略**：根據 Constitution 規範，測試是**強制且不可妥協**的。所有任務遵循 TDD 原則，測試任務安排在實作任務之前。

**組織方式**：任務按 User Story 分組，以支援獨立實作與測試。

## 格式說明：`[ID] [P?] [Story?] 描述`

- **[P]**：可平行執行（不同檔案、無依賴）
- **[Story]**：所屬使用者故事（例如：US1, US2, US3）
- 描述中包含確切的檔案路徑

## 路徑慣例

根據 plan.md 定義的專案結構：

- **前端源碼**：`frontend/app/`
- **元件**：`frontend/app/components/`
- **Composables**：`frontend/app/composables/`
- **頁面**：`frontend/app/pages/`
- **Store**：`frontend/app/stores/`
- **測試**：`frontend/tests/`

---

## 第一階段：專案設置 (Setup)

**目的**：專案初始化與基礎結構建立

- [x] T001 依照 quickstart.md 初始化 Nuxt 3 專案於 frontend/ 目錄
- [ ] T002 安裝相依套件 (@nuxt/ui, @pinia/nuxt, pinia, @nuxtjs/i18n)
- [ ] T003 [P] 安裝測試框架 (vitest, @nuxt/test-utils, @vue/test-utils)
- [ ] T004 [P] 設定 nuxt.config.ts 啟用 Nuxt UI、Pinia、i18n 模組
- [ ] T005 [P] 設定 vitest.config.ts 測試配置
- [ ] T006 建立 frontend/app/ 目錄結構 (components, composables, pages, stores, layouts, middleware, utils)
- [ ] T007 [P] 建立 frontend/tests/ 目錄結構 (unit, integration)

---

## 第二階段：基礎建設 (Foundational)

**目的**：所有 User Story 都依賴的核心基礎設施，**必須在任何 User Story 開始前完成**

**⚠️ 重要**：此階段完成前，不可開始任何 User Story 的實作

### 基礎設施測試

- [ ] T008 [P] 建立 TypeScript 型別定義測試於 frontend/tests/unit/types/types.spec.ts
- [ ] T009 [P] 建立 API 工具函數測試於 frontend/tests/unit/utils/api.spec.ts
- [ ] T010 [P] 建立認證 Store 單元測試於 frontend/tests/unit/stores/auth.spec.ts

### 基礎設施實作

- [ ] T011 [P] 建立 TypeScript 型別定義於 frontend/app/types/index.ts (User, Project, Template, Question, Answer, ReviewLog)
- [ ] T012 [P] 建立 API 錯誤處理工具於 frontend/app/utils/api-error.ts
- [ ] T013 [P] 建立共用 API Composable 基礎於 frontend/app/composables/useApi.ts
- [ ] T014 建立認證 Store 於 frontend/app/stores/auth.ts (依賴 T011, T013)
- [ ] T015 建立認證 Middleware 於 frontend/app/middleware/auth.ts (依賴 T014)
- [ ] T016 [P] 建立 i18n 配置檔於 frontend/i18n.config.ts
- [ ] T017 [P] 建立繁體中文語系檔於 frontend/app/locales/zh-TW.json
- [ ] T018 [P] 建立英文語系檔於 frontend/app/locales/en.json
- [ ] T019 建立主版面配置於 frontend/app/layouts/default.vue (依賴 T016-T018)

**檢查點**：基礎設施就緒，可開始 User Story 實作

---

## 第三階段：User Story 1 - 會員中心與帳戶管理 (Priority: P1) 🎯 MVP

**目標**：使用者登入後進入會員中心，可瀏覽應用程式列表、修改個人資料與密碼、切換語系

**獨立測試**：測試登入後能否看到 Navbar 與 App 列表，能否切換語系，能否成功修改密碼與資料

### User Story 1 單元測試

> **注意：先撰寫測試，確保測試失敗後再開始實作**

- [ ] T020 [P] [US1] 建立 useAuth Composable 單元測試於 frontend/tests/unit/composables/useAuth.spec.ts
- [ ] T021 [P] [US1] 建立 useUser Composable 單元測試於 frontend/tests/unit/composables/useUser.spec.ts
- [ ] T022 [P] [US1] 建立 Navbar 元件單元測試於 frontend/tests/unit/components/Navbar.spec.ts
- [ ] T023 [P] [US1] 建立 AppCard 元件單元測試於 frontend/tests/unit/components/AppCard.spec.ts
- [ ] T024 [P] [US1] 建立 LanguageSwitcher 元件單元測試於 frontend/tests/unit/components/LanguageSwitcher.spec.ts

### User Story 1 整合測試

- [ ] T025 [P] [US1] 建立登入流程整合測試於 frontend/tests/integration/auth-flow.spec.ts
- [ ] T026 [P] [US1] 建立會員中心頁面整合測試於 frontend/tests/integration/member-center.spec.ts
- [ ] T027 [P] [US1] 建立帳戶管理頁面整合測試於 frontend/tests/integration/account-management.spec.ts

### User Story 1 Composables 實作

- [ ] T028 [US1] 實作 useAuth Composable 於 frontend/app/composables/useAuth.ts (登入、登出、Token 管理)
- [ ] T029 [US1] 實作 useUser Composable 於 frontend/app/composables/useUser.ts (取得/更新個人資料、修改密碼)

### User Story 1 元件實作

- [ ] T030 [P] [US1] 建立 Navbar 元件於 frontend/app/components/common/Navbar.vue
- [ ] T031 [P] [US1] 建立 AppCard 元件於 frontend/app/components/member/AppCard.vue
- [ ] T032 [P] [US1] 建立 LanguageSwitcher 元件於 frontend/app/components/common/LanguageSwitcher.vue
- [ ] T033 [P] [US1] 建立 ProfileForm 元件於 frontend/app/components/account/ProfileForm.vue
- [ ] T034 [P] [US1] 建立 PasswordChangeForm 元件於 frontend/app/components/account/PasswordChangeForm.vue

### User Story 1 頁面實作

- [ ] T035 [US1] 建立登入頁面於 frontend/app/pages/login.vue
- [ ] T036 [US1] 建立會員中心首頁於 frontend/app/pages/index.vue (依賴 T030, T031, T032)
- [ ] T037 [US1] 建立帳戶管理頁面於 frontend/app/pages/account/index.vue (依賴 T033, T034)

### User Story 1 驗證與錯誤處理

- [ ] T038 [US1] 為帳戶管理表單加入表單驗證於 frontend/app/components/account/ProfileForm.vue
- [ ] T039 [US1] 為密碼修改表單加入驗證與錯誤提示於 frontend/app/components/account/PasswordChangeForm.vue

**檢查點**：User Story 1 應完全可運作並可獨立測試 - 使用者可登入、瀏覽會員中心、切換語系、修改個人資料與密碼

---

## 第四階段：User Story 4 - 問卷填寫 (Priority: P1) 🎯 MVP

**目標**：被指派的供應商使用者登入後，進入專案進行回答，支援填寫進度暫存，完成後提交進入審核流程

**獨立測試**：測試供應商登入後僅能看到指派的專案，測試填寫、暫存與提交

**說明**：此 User Story 與 US1 同為 P1 優先級，提供供應商端的核心功能

### User Story 4 單元測試

- [ ] T040 [P] [US4] 建立 useProjects Composable 單元測試於 frontend/tests/unit/composables/useProjects.spec.ts
- [ ] T041 [P] [US4] 建立 useAnswers Composable 單元測試於 frontend/tests/unit/composables/useAnswers.spec.ts
- [ ] T042 [P] [US4] 建立 ProjectList 元件單元測試於 frontend/tests/unit/components/ProjectList.spec.ts
- [ ] T043 [P] [US4] 建立 QuestionRenderer 元件單元測試於 frontend/tests/unit/components/QuestionRenderer.spec.ts
- [ ] T044 [P] [US4] 建立各題型元件單元測試於 frontend/tests/unit/components/question-types/

### User Story 4 整合測試

- [ ] T045 [P] [US4] 建立供應商專案列表整合測試於 frontend/tests/integration/supplier-projects.spec.ts
- [ ] T046 [P] [US4] 建立問卷填寫流程整合測試於 frontend/tests/integration/questionnaire-answering.spec.ts

### User Story 4 Composables 實作

- [ ] T047 [US4] 實作 useProjects Composable 於 frontend/app/composables/useProjects.ts (專案列表、專案詳情)
- [ ] T048 [US4] 實作 useAnswers Composable 於 frontend/app/composables/useAnswers.ts (讀取/儲存答案、暫存、提交)

### User Story 4 元件實作

- [ ] T049 [P] [US4] 建立 ProjectList 元件於 frontend/app/components/project/ProjectList.vue
- [ ] T050 [P] [US4] 建立 ProjectCard 元件於 frontend/app/components/project/ProjectCard.vue
- [ ] T051 [P] [US4] 建立 QuestionRenderer 元件於 frontend/app/components/questionnaire/QuestionRenderer.vue
- [ ] T052 [P] [US4] 建立 TextQuestion 元件於 frontend/app/components/questionnaire/types/TextQuestion.vue
- [ ] T053 [P] [US4] 建立 NumberQuestion 元件於 frontend/app/components/questionnaire/types/NumberQuestion.vue
- [ ] T054 [P] [US4] 建立 DateQuestion 元件於 frontend/app/components/questionnaire/types/DateQuestion.vue
- [ ] T055 [P] [US4] 建立 BooleanQuestion 元件於 frontend/app/components/questionnaire/types/BooleanQuestion.vue
- [ ] T056 [P] [US4] 建立 SingleChoiceQuestion 元件於 frontend/app/components/questionnaire/types/SingleChoiceQuestion.vue
- [ ] T057 [P] [US4] 建立 MultiChoiceQuestion 元件於 frontend/app/components/questionnaire/types/MultiChoiceQuestion.vue
- [ ] T058 [P] [US4] 建立 FileUploadQuestion 元件於 frontend/app/components/questionnaire/types/FileUploadQuestion.vue
- [ ] T059 [P] [US4] 建立 RatingQuestion 元件於 frontend/app/components/questionnaire/types/RatingQuestion.vue
- [ ] T060 [P] [US4] 建立 QuestionnaireProgress 元件於 frontend/app/components/questionnaire/QuestionnaireProgress.vue

### User Story 4 頁面實作

- [ ] T061 [US4] 建立供應商專案列表頁面於 frontend/app/pages/supplier/projects/index.vue
- [ ] T062 [US4] 建立問卷填寫頁面於 frontend/app/pages/supplier/projects/[id]/answer.vue

### User Story 4 驗證與錯誤處理

- [ ] T063 [US4] 為問卷填寫加入必填欄位驗證於 frontend/app/composables/useAnswers.ts
- [ ] T064 [US4] 為提交功能加入完整性檢查與錯誤提示於 frontend/app/pages/supplier/projects/[id]/answer.vue

**檢查點**：User Story 4 應完全可運作並可獨立測試 - 供應商可查看指派專案、填寫問卷、暫存與提交

---

## 第五階段：User Story 2 - SAQ 專案與範本管理 (Priority: P2)

**目標**：製造商使用者可管理 SAQ 專案與範本，包含專案建立、範本編輯、版本控制與供應商指派

**獨立測試**：測試建立範本、新增題目、發布版本，測試建立專案並指派給特定供應商

### User Story 2 單元測試

- [ ] T065 [P] [US2] 建立 useTemplates Composable 單元測試於 frontend/tests/unit/composables/useTemplates.spec.ts
- [ ] T066 [P] [US2] 建立 useSuppliers Composable 單元測試於 frontend/tests/unit/composables/useSuppliers.spec.ts
- [ ] T067 [P] [US2] 建立 TemplateList 元件單元測試於 frontend/tests/unit/components/TemplateList.spec.ts
- [ ] T068 [P] [US2] 建立 TemplateEditor 元件單元測試於 frontend/tests/unit/components/TemplateEditor.spec.ts
- [ ] T069 [P] [US2] 建立 ProjectForm 元件單元測試於 frontend/tests/unit/components/ProjectForm.spec.ts
- [ ] T070 [P] [US2] 建立 SupplierSelector 元件單元測試於 frontend/tests/unit/components/SupplierSelector.spec.ts

### User Story 2 整合測試

- [ ] T071 [P] [US2] 建立 SAQ 範本管理流程整合測試於 frontend/tests/integration/saq-template-management.spec.ts
- [ ] T072 [P] [US2] 建立 SAQ 專案管理流程整合測試於 frontend/tests/integration/saq-project-management.spec.ts

### User Story 2 Composables 實作

- [ ] T073 [US2] 實作 useTemplates Composable 於 frontend/app/composables/useTemplates.ts (範本 CRUD、版本控制)
- [ ] T074 [US2] 實作 useSuppliers Composable 於 frontend/app/composables/useSuppliers.ts (供應商列表查詢)
- [ ] T075 [US2] 擴充 useProjects Composable 加入專案 CRUD 功能於 frontend/app/composables/useProjects.ts

### User Story 2 元件實作

- [ ] T076 [P] [US2] 建立 TemplateList 元件於 frontend/app/components/template/TemplateList.vue
- [ ] T077 [P] [US2] 建立 TemplateCard 元件於 frontend/app/components/template/TemplateCard.vue
- [ ] T078 [P] [US2] 建立 TemplateEditor 元件於 frontend/app/components/template/TemplateEditor.vue
- [ ] T079 [P] [US2] 建立 QuestionEditor 元件於 frontend/app/components/template/QuestionEditor.vue
- [ ] T080 [P] [US2] 建立 ProjectForm 元件於 frontend/app/components/project/ProjectForm.vue (含供應商選擇)
- [ ] T081 [P] [US2] 建立 SupplierSelector 元件於 frontend/app/components/project/SupplierSelector.vue
- [ ] T082 [P] [US2] 建立 ReviewFlowConfig 元件於 frontend/app/components/project/ReviewFlowConfig.vue (審核流程設定)
- [ ] T083 [P] [US2] 建立 VersionHistory 元件於 frontend/app/components/template/VersionHistory.vue

### User Story 2 頁面實作

- [ ] T084 [US2] 建立 SAQ 首頁於 frontend/app/pages/saq/index.vue
- [ ] T085 [US2] 建立 SAQ 專案列表頁面於 frontend/app/pages/saq/projects/index.vue
- [ ] T086 [US2] 建立 SAQ 專案新增/編輯頁面於 frontend/app/pages/saq/projects/[id].vue
- [ ] T087 [US2] 建立 SAQ 範本列表頁面於 frontend/app/pages/saq/templates/index.vue
- [ ] T088 [US2] 建立 SAQ 範本編輯頁面於 frontend/app/pages/saq/templates/[id].vue

**檢查點**：User Story 2 應完全可運作並可獨立測試 - 製造商可管理 SAQ 專案與範本

---

## 第六階段：User Story 5 - 多階段部門審核 (Priority: P2)

**目標**：專案提交後進入多階段審核流程，審核者依部門權限檢視專案並決定核准或退回

**獨立測試**：測試多階段流程流轉，測試不同部門審核者的權限，測試退回流程

### User Story 5 單元測試

- [ ] T089 [P] [US5] 建立 useReview Composable 單元測試於 frontend/tests/unit/composables/useReview.spec.ts
- [ ] T090 [P] [US5] 建立 ReviewPanel 元件單元測試於 frontend/tests/unit/components/ReviewPanel.spec.ts
- [ ] T091 [P] [US5] 建立 ReviewHistory 元件單元測試於 frontend/tests/unit/components/ReviewHistory.spec.ts
- [ ] T092 [P] [US5] 建立 ReviewActionDialog 元件單元測試於 frontend/tests/unit/components/ReviewActionDialog.spec.ts

### User Story 5 整合測試

- [ ] T093 [P] [US5] 建立審核流程整合測試於 frontend/tests/integration/review-flow.spec.ts
- [ ] T094 [P] [US5] 建立退回流程整合測試於 frontend/tests/integration/return-flow.spec.ts

### User Story 5 Composables 實作

- [ ] T095 [US5] 實作 useReview Composable 於 frontend/app/composables/useReview.ts (審核操作、歷程查詢)
- [ ] T096 [US5] 建立審核權限 Store 於 frontend/app/stores/review.ts

### User Story 5 元件實作

- [ ] T097 [P] [US5] 建立 ReviewPanel 元件於 frontend/app/components/review/ReviewPanel.vue
- [ ] T098 [P] [US5] 建立 ReviewHistory 元件於 frontend/app/components/review/ReviewHistory.vue
- [ ] T099 [P] [US5] 建立 ReviewActionDialog 元件於 frontend/app/components/review/ReviewActionDialog.vue
- [ ] T100 [P] [US5] 建立 ProjectStatusBadge 元件於 frontend/app/components/project/ProjectStatusBadge.vue

### User Story 5 頁面實作

- [ ] T101 [US5] 建立審核待辦列表頁面於 frontend/app/pages/review/index.vue
- [ ] T102 [US5] 建立專案審核頁面於 frontend/app/pages/review/[id].vue

**檢查點**：User Story 5 應完全可運作並可獨立測試 - 審核者可檢視、核准或退回專案

---

## 第七階段：User Story 3 - 衝突資產管理 (Priority: P3)

**目標**：製造商使用者可管理衝突資產專案與範本，功能機制與 SAQ 相同但資料獨立

**獨立測試**：測試衝突資產的專案建立、範本管理與供應商指派，確保與 SAQ 獨立但功能一致

### User Story 3 單元測試

- [ ] T103 [P] [US3] 建立衝突資產模組相關元件單元測試於 frontend/tests/unit/components/conflict/

### User Story 3 整合測試

- [ ] T104 [P] [US3] 建立衝突資產專案管理流程整合測試於 frontend/tests/integration/conflict-project-management.spec.ts
- [ ] T105 [P] [US3] 建立衝突資產範本管理流程整合測試於 frontend/tests/integration/conflict-template-management.spec.ts

### User Story 3 頁面實作（複用 SAQ 元件）

- [ ] T106 [US3] 建立衝突資產首頁於 frontend/app/pages/conflict/index.vue
- [ ] T107 [US3] 建立衝突資產專案列表頁面於 frontend/app/pages/conflict/projects/index.vue
- [ ] T108 [US3] 建立衝突資產專案新增/編輯頁面於 frontend/app/pages/conflict/projects/[id].vue
- [ ] T109 [US3] 建立衝突資產範本列表頁面於 frontend/app/pages/conflict/templates/index.vue
- [ ] T110 [US3] 建立衝突資產範本編輯頁面於 frontend/app/pages/conflict/templates/[id].vue

**檢查點**：User Story 3 應完全可運作並可獨立測試 - 製造商可管理衝突資產專案與範本，與 SAQ 資料隔離

---

## 第八階段：優化與橫跨功能 (Polish & Cross-Cutting Concerns)

**目的**：影響多個 User Story 的改進項目

### 效能優化

- [ ] T111 [P] 實作元件懶載入策略於 frontend/app/pages/
- [ ] T112 [P] 實作 API 回應快取於 frontend/app/composables/useApi.ts
- [ ] T113 [P] 最佳化 Bundle 大小，設定適當的 Tree-shaking 於 frontend/nuxt.config.ts

### 無障礙與使用者體驗

- [ ] T114 [P] 為所有表單元件加入 ARIA 標籤於 frontend/app/components/
- [ ] T115 [P] 實作載入狀態與骨架屏 (Skeleton) 於 frontend/app/components/common/
- [ ] T116 [P] 實作全域錯誤提示與成功訊息於 frontend/app/plugins/toast.ts

### 文件更新

- [ ] T117 [P] 更新 README.md 包含開發與部署說明
- [ ] T118 [P] 建立 API 需求文件於 frontend/docs/api-requirements.md

### 最終驗證

- [ ] T119 執行所有單元測試確保通過
- [ ] T120 執行所有整合測試確保通過
- [ ] T121 執行 quickstart.md 驗證流程

---

## 依賴關係與執行順序

### 階段依賴

- **第一階段 (Setup)**：無依賴 - 可立即開始
- **第二階段 (Foundational)**：依賴 Setup 完成 - **阻擋所有 User Story**
- **第三階段+ (User Stories)**：所有依賴 Foundational 完成
  - User Stories 可平行進行（若有多人）
  - 或依優先順序依序執行 (P1 → P2 → P3)
- **最終階段 (Polish)**：依賴所有預期 User Stories 完成

### User Story 依賴

- **User Story 1 (P1)**：Foundational 完成後即可開始 - 無其他 Story 依賴
- **User Story 4 (P1)**：Foundational 完成後即可開始 - 可與 US1 平行
- **User Story 2 (P2)**：Foundational 完成後即可開始 - 可獨立測試
- **User Story 5 (P2)**：Foundational 完成後即可開始 - 可獨立測試
- **User Story 3 (P3)**：依賴 US2 的共用元件 - 複用 SAQ 架構

### 各 User Story 內部順序

1. 單元測試 **必須先撰寫**並確保失敗
2. 整合測試 撰寫並確保失敗
3. Composables 實作
4. 元件實作
5. 頁面實作
6. 驗證與錯誤處理
7. 確認測試通過

### 平行執行機會

- 所有標記 [P] 的 Setup 任務可平行執行
- 所有標記 [P] 的 Foundational 任務可平行執行
- Foundational 完成後，所有 User Stories 可平行開始
- 各 User Story 內標記 [P] 的測試可平行執行
- 各 User Story 內標記 [P] 的元件可平行執行

---

## 平行執行範例

### User Story 1 範例

```bash
# 同時啟動 User Story 1 所有單元測試：
任務: "建立 useAuth Composable 單元測試於 frontend/tests/unit/composables/useAuth.spec.ts"
任務: "建立 useUser Composable 單元測試於 frontend/tests/unit/composables/useUser.spec.ts"
任務: "建立 Navbar 元件單元測試於 frontend/tests/unit/components/Navbar.spec.ts"
任務: "建立 AppCard 元件單元測試於 frontend/tests/unit/components/AppCard.spec.ts"
任務: "建立 LanguageSwitcher 元件單元測試於 frontend/tests/unit/components/LanguageSwitcher.spec.ts"

# 同時啟動 User Story 1 所有元件：
任務: "建立 Navbar 元件於 frontend/app/components/common/Navbar.vue"
任務: "建立 AppCard 元件於 frontend/app/components/member/AppCard.vue"
任務: "建立 LanguageSwitcher 元件於 frontend/app/components/common/LanguageSwitcher.vue"
任務: "建立 ProfileForm 元件於 frontend/app/components/account/ProfileForm.vue"
任務: "建立 PasswordChangeForm 元件於 frontend/app/components/account/PasswordChangeForm.vue"
```

### User Story 4 範例

```bash
# 同時啟動 User Story 4 所有題型元件：
任務: "建立 TextQuestion 元件於 frontend/app/components/questionnaire/types/TextQuestion.vue"
任務: "建立 NumberQuestion 元件於 frontend/app/components/questionnaire/types/NumberQuestion.vue"
任務: "建立 DateQuestion 元件於 frontend/app/components/questionnaire/types/DateQuestion.vue"
任務: "建立 BooleanQuestion 元件於 frontend/app/components/questionnaire/types/BooleanQuestion.vue"
任務: "建立 SingleChoiceQuestion 元件於 frontend/app/components/questionnaire/types/SingleChoiceQuestion.vue"
任務: "建立 MultiChoiceQuestion 元件於 frontend/app/components/questionnaire/types/MultiChoiceQuestion.vue"
任務: "建立 FileUploadQuestion 元件於 frontend/app/components/questionnaire/types/FileUploadQuestion.vue"
任務: "建立 RatingQuestion 元件於 frontend/app/components/questionnaire/types/RatingQuestion.vue"
```

---

## 實作策略

### MVP 優先策略 (User Story 1 + 4)

1. 完成 第一階段：Setup
2. 完成 第二階段：Foundational（**重要** - 阻擋所有 Stories）
3. 完成 第三階段：User Story 1（會員中心與帳戶管理）
4. 完成 第四階段：User Story 4（問卷填寫）
5. **停下並驗證**：獨立測試 User Story 1 與 4
6. 若準備好即可部署/展示 MVP

### 增量交付策略

1. 完成 Setup + Foundational → 基礎就緒
2. 新增 User Story 1 → 獨立測試 → 部署/展示
3. 新增 User Story 4 → 獨立測試 → 部署/展示（MVP 完成！）
4. 新增 User Story 2 → 獨立測試 → 部署/展示
5. 新增 User Story 5 → 獨立測試 → 部署/展示
6. 新增 User Story 3 → 獨立測試 → 部署/展示
7. 每個 Story 都增加價值且不破壞既有功能

### 平行團隊策略

多位開發者時：

1. 團隊共同完成 Setup + Foundational
2. Foundational 完成後：
   - 開發者 A：User Story 1
   - 開發者 B：User Story 4
   - 開發者 C：User Story 2
3. 各 Story 獨立完成並整合

---

## 備註

- [P] 任務 = 不同檔案、無依賴
- [Story] 標籤將任務對應至特定 User Story 以便追蹤
- 每個 User Story 應可獨立完成並測試
- 實作前先確認測試失敗
- 每個任務或邏輯群組完成後提交
- 可於任何檢查點停下來獨立驗證 Story
- 避免：模糊任務、相同檔案衝突、破壞獨立性的跨 Story 依賴

---

## 憲法合規檢查

本任務清單符合 Constitution 規範：

- ✅ **語言與本地化 (V)**：整份文件使用繁體中文 (zh-TW)
- ✅ **測試策略 (II)**：所有任務包含強制測試，遵循 TDD 原則
- ✅ **程式碼品質 (I)**：使用 Nuxt 3 標準結構與 Composables 模式
- ✅ **使用者體驗一致性 (III)**：包含無障礙與 UX 優化任務
- ✅ **效能要求 (IV)**：包含效能優化任務（懶載入、快取）

---

## 摘要統計

| 項目 | 數量 |
|------|------|
| **總任務數** | 121 |
| **Setup 任務** | 7 |
| **Foundational 任務** | 12 |
| **User Story 1 任務** | 20 |
| **User Story 4 任務** | 25 |
| **User Story 2 任務** | 24 |
| **User Story 5 任務** | 14 |
| **User Story 3 任務** | 8 |
| **Polish 任務** | 11 |
| **可平行任務 [P]** | 74 |
| **單元測試任務** | 25 |
| **整合測試任務** | 13 |

### 各 User Story 獨立測試標準

| User Story | 獨立測試標準 |
|------------|-------------|
| US1 | 登入 → 看到 Navbar 與 App 列表 → 切換語系 → 修改密碼與資料 |
| US4 | 供應商登入 → 僅看到指派專案 → 填寫 → 暫存 → 提交 |
| US2 | 建立範本 → 新增題目 → 發布版本 → 建立專案並指派供應商 |
| US5 | 提交專案 → 第一階段審核 → 核准/退回 → 狀態變更 |
| US3 | 衝突資產專案建立 → 範本管理 → 與 SAQ 資料隔離 |

### 建議 MVP 範圍

**最小可行產品**：User Story 1 + User Story 4

- 會員中心與帳戶管理（入口功能）
- 問卷填寫（核心業務流程）
