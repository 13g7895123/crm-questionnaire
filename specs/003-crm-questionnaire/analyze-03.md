# Specification Analysis Report

**Feature**: CRM Questionnaire System (問卷系統CRM)
**Analyzed**: 2025-12-02
**Artifacts**: spec.md, plan.md, tasks.md, constitution.md

---

## Findings Summary

| ID | Category | Severity | Location(s) | Summary | Recommendation |
|----|----------|----------|-------------|---------|----------------|
| A1 | Ambiguity | MEDIUM | plan.md:L15 | TailwindCSS 標記為「待確認」，未明確決定 UI 框架 | 確認使用 @nuxt/ui 或 TailwindCSS，更新 plan.md |
| C1 | Coverage | HIGH | spec.md:FR-018 | 部門管理 (Department CRUD) 無對應任務 | 新增部門管理相關任務於 tasks.md |
| C2 | Coverage | MEDIUM | spec.md:EC-001 | 邊緣案例「刪除已使用範本」無對應任務 | 新增範本刪除保護邏輯任務 |
| C3 | Coverage | MEDIUM | spec.md:EC-002 | 邊緣案例「翻譯缺失回退」無對應任務 | 新增 i18n 回退機制任務 |
| C4 | Coverage | MEDIUM | spec.md:EC-003 | 邊緣案例「網路斷線處理」無對應任務 | 新增離線錯誤處理任務 |
| I1 | Inconsistency | MEDIUM | plan.md vs tasks.md | plan.md 使用 `Axios/Nuxt Fetch`，tasks.md 僅使用 `useApi` Composable | 統一 API 呼叫策略說明 |
| I2 | Inconsistency | LOW | data-model.md vs spec.md | data-model 中 User.department 為 string，但 spec.md FR-018 定義 Department 為獨立管理實體 | 更新 data-model.md 使用 Department 型別 |
| I3 | Inconsistency | MEDIUM | spec.md vs tasks.md | spec.md 定義 Organization 實體區分製造商/供應商，但無對應型別定義任務 | 新增 Organization 型別於 T011 |
| U1 | Underspecification | MEDIUM | spec.md:FR-003 | 「至少包含繁體中文」未明確定義支援哪些語系 | 明確列出支援語系 (zh-TW, en) |
| U2 | Underspecification | LOW | spec.md:FR-012 | 「評分量表」未定義評分範圍 (1-5? 1-10?) | 在 data-model 或 spec 中定義評分配置 |
| U3 | Underspecification | MEDIUM | spec.md:FR-014 | 審核流程「可配置」但未說明配置上限/限制 | 說明審核階段數量限制 |
| D1 | Duplication | LOW | tasks.md:T038-T039 | 表單驗證任務可能與元件實作任務重疊 | 整合驗證邏輯於元件實作任務中 |

---

## Coverage Summary Table

| Requirement Key | Has Task? | Task IDs | Notes |
|-----------------|-----------|----------|-------|
| FR-001 (會員中心首頁) | ✅ | T036 | - |
| FR-002 (App 列表圖片文字) | ✅ | T031 | AppCard 元件 |
| FR-003 (多語系切換) | ✅ | T016-T018, T032 | LanguageSwitcher + i18n |
| FR-004 (帳戶管理) | ✅ | T033, T034, T037 | ProfileForm + PasswordChangeForm |
| FR-005 (SAQ/衝突資產首頁) | ✅ | T084, T106 | - |
| FR-006 (專案 CRUD) | ✅ | T075, T080, T085-T086 | - |
| FR-007 (專案建立含審核設定) | ✅ | T080, T082 | ProjectForm + ReviewFlowConfig |
| FR-008 (範本管理介面) | ✅ | T076-T078, T087-T088 | - |
| FR-009 (範本版本控制) | ✅ | T073, T083 | useTemplates + VersionHistory |
| FR-010 (題目管理) | ✅ | T079 | QuestionEditor |
| FR-011 (SAQ/衝突資料隔離) | ✅ | T106-T110 | 獨立頁面路由 |
| FR-012 (題目類型) | ✅ | T052-T059 | 8 種題型元件 |
| FR-013 (問卷填寫/暫存/提交) | ✅ | T048, T061-T064 | useAnswers + 頁面 |
| FR-014 (多階段審核) | ✅ | T095-T102 | User Story 5 完整覆蓋 |
| FR-015 (專案狀態) | ✅ | T100 | ProjectStatusBadge |
| FR-016 (審核歷程) | ✅ | T098 | ReviewHistory 元件 |
| FR-017 (製造商/供應商權限) | ✅ | T014-T015, T096 | auth store + middleware |
| FR-018 (部門管理 CRUD) | ❌ | - | **缺少任務** |

### Edge Cases Coverage

| Edge Case | Has Task? | Notes |
|-----------|-----------|-------|
| EC-001 (刪除已使用範本保護) | ❌ | 無對應邏輯任務 |
| EC-002 (翻譯缺失回退) | ❌ | 無對應 i18n 回退任務 |
| EC-003 (網路斷線錯誤處理) | ❌ | 無對應離線處理任務 |

---

## Constitution Alignment Issues

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Code Quality | ✅ | Nuxt 3 標準結構，Composables 模式 |
| II. Testing Strategy | ✅ | TDD 原則，測試任務先於實作 |
| III. User Experience | ✅ | T114 包含 ARIA 標籤，T115 載入狀態 |
| IV. Performance | ✅ | T111-T113 效能優化任務 |
| V. Language & Localization | ✅ | 文件使用繁體中文 |
| Security (Token/Validation) | ✅ | T014 認證 Store，T012 錯誤處理 |

**無 Constitution MUST 違規**

---

## Unmapped Tasks

所有任務均已對應至 User Story 或基礎設施需求。

---

## Metrics

| Metric | Value |
|--------|-------|
| **總需求數 (FR)** | 18 |
| **總任務數** | 121 |
| **需求覆蓋率** | 94.4% (17/18) |
| **邊緣案例覆蓋率** | 0% (0/3) |
| **模糊項目數** | 3 |
| **重複項目數** | 1 |
| **Critical Issues** | 0 |
| **High Issues** | 1 |
| **Medium Issues** | 8 |
| **Low Issues** | 3 |

---

## Next Actions

### 建議優先處理 (HIGH)

1. **C1**: 新增部門管理 (Department CRUD) 相關任務
   - 建議：在 User Story 1 或獨立階段新增以下任務：
     - 建立 useDepartments Composable 單元測試
     - 實作 useDepartments Composable (部門 CRUD)
     - 建立部門管理頁面於 frontend/app/pages/admin/departments/index.vue

### 建議次要處理 (MEDIUM)

2. **A1**: 確認 UI 框架決策，移除 plan.md 中的「待確認」
   - 執行：`/speckit.plan` 更新技術決策

3. **C2-C4**: 新增邊緣案例處理任務
   - EC-001: 在 T073 (useTemplates) 加入刪除保護邏輯
   - EC-002: 在 T016 (i18n config) 加入 fallbackLocale 設定
   - EC-003: 在 T012 (api-error) 加入網路錯誤處理

4. **I3**: 擴充 T011 型別定義，加入 Organization 型別

5. **U3**: 在 spec.md 或 plan.md 中明確定義審核階段上限

### 可延後處理 (LOW)

6. **I2**: 更新 data-model.md 中 User.department 型別
7. **D1**: 整合表單驗證任務至元件實作
8. **U2**: 定義評分量表預設範圍

---

## Remediation Summary

**整體評估**: 規格品質良好，主要需補強部門管理功能任務與邊緣案例處理。無 Critical 違規，可在處理 HIGH 問題後繼續進行 `/speckit.implement`。

---

Would you like me to suggest concrete remediation edits for the top 3 issues?
