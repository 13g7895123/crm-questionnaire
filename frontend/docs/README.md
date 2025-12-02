# CRM Questionnaire Frontend Documentation

歡迎來到 CRM 問卷系統前端文件中心。本目錄包含所有與前端開發相關的技術文件。

## 📚 文件索引

### API 相關文件

1. **[API Requirements](./API_REQUIREMENTS.md)** ⭐ 重點文件
   - 完整的 API 端點需求文件
   - 包含所有請求/回應格式、錯誤處理、驗證規範
   - 適合後端開發者參考實作 API
   - 適合前端開發者了解 API 使用方式

2. **[API Mapping](./API_MAPPING.md)**
   - Frontend Composables 與 API 端點的對應關係
   - 包含使用範例與最佳實踐
   - 適合前端開發者快速查找 API 使用方式

## 🎯 快速導覽

### 我是前端開發者

如果你要：
- **呼叫 API**: 查看 [API Mapping](./API_MAPPING.md) 了解如何使用現有的 composables
- **新增功能**: 查看 [API Requirements](./API_REQUIREMENTS.md) 了解完整的 API 規格
- **除錯問題**: 查看 [API Requirements](./API_REQUIREMENTS.md) 的錯誤回應格式

### 我是後端開發者

如果你要：
- **實作 API**: 查看 [API Requirements](./API_REQUIREMENTS.md) 了解完整的端點需求
- **查看前端如何使用**: 查看 [API Mapping](./API_MAPPING.md) 了解前端的使用方式
- **設計資料結構**: 參考 `frontend/app/types/index.ts` 的 TypeScript 型別定義

### 我是專案經理/需求分析師

如果你要：
- **了解系統功能**: 查看 [Feature Specification](../../specs/003-crm-questionnaire/spec.md)
- **了解技術細節**: 查看 [API Requirements](./API_REQUIREMENTS.md)
- **查看進度規劃**: 查看 [Project Tasks](../../specs/003-crm-questionnaire/tasks.md)

## 📖 文件說明

### API Requirements (API_REQUIREMENTS.md)

這是最重要的技術文件，定義了 CRM 問卷系統的所有 API 需求。

**涵蓋範圍**:
- ✅ 認證與授權 (Authentication & Authorization)
- ✅ 使用者與部門管理 (User & Department Management)
- ✅ 供應商管理 (Supplier Management)
- ✅ 專案管理 (Project Management)
- ✅ 範本與題目管理 (Template & Question Management)
- ✅ 問卷填寫 (Questionnaire Answering)
- ✅ 多階段審核流程 (Multi-stage Review Process)
- ✅ 檔案上傳與下載 (File Upload & Download)

**文件結構**:
1. 端點定義 (Endpoint, HTTP Method)
2. 請求格式 (Request Headers, Body, Query Parameters)
3. 成功回應 (Success Response with status code)
4. 錯誤回應 (Error Responses with status codes)
5. 使用範例 (Usage Examples)

**特色**:
- 📝 中文與英文雙語說明
- 🔍 完整的錯誤代碼與處理方式
- 📊 清晰的資料結構範例
- 🎨 統一的命名規範與格式

### API Mapping (API_MAPPING.md)

這份文件說明前端 Composables 與後端 API 的對應關係。

**涵蓋範圍**:
- `useAuth.ts` - 認證相關功能
- `useUser.ts` - 使用者管理
- `useDepartments.ts` - 部門管理
- `useSuppliers.ts` - 供應商管理
- `useProjects.ts` - 專案管理
- `useTemplates.ts` - 範本管理
- `useAnswers.ts` - 問卷填寫
- `useReview.ts` - 審核流程

**每個 Composable 包含**:
- 方法與 API 端點對應表
- 實際使用範例
- 相關 API 文件連結

## 🏗️ 系統架構

### 前端技術棧

- **框架**: Nuxt 3 (Vue 3 + SSR)
- **狀態管理**: Pinia
- **UI 框架**: Nuxt UI (@nuxt/ui)
- **國際化**: @nuxtjs/i18n
- **HTTP 客戶端**: Native Fetch API (封裝於 useApi composable)
- **測試**: Vitest + @vue/test-utils

### 資料夾結構

```
frontend/
├── app/
│   ├── components/      # Vue 元件
│   ├── composables/     # Composable 函數 (API 封裝)
│   ├── layouts/         # 頁面佈局
│   ├── pages/           # 路由頁面
│   ├── stores/          # Pinia 狀態管理
│   ├── types/           # TypeScript 型別定義
│   └── utils/           # 工具函數
├── docs/                # 📚 你現在在這裡
├── public/              # 靜態資源
└── tests/               # 測試檔案
```

### Composables 架構

```
useApi (基礎層)
  ├── useAuth (認證)
  ├── useUser (使用者)
  ├── useDepartments (部門)
  ├── useSuppliers (供應商)
  ├── useProjects (專案)
  ├── useTemplates (範本)
  ├── useAnswers (問卷填寫)
  └── useReview (審核)
```

## 🔐 認證機制

系統使用 JWT (JSON Web Token) 進行認證:

1. 使用者登入後，後端返回 JWT token
2. Token 儲存於 Pinia Store (authStore)
3. 所有後續 API 請求自動注入 `Authorization: Bearer {token}` 標頭
4. Token 過期時，使用 refresh token 取得新的 access token

## 🌍 多語系支援

系統支援以下語言:
- 繁體中文 (zh-TW) - 預設語言
- 英文 (en)

語系檔案位於: `frontend/app/locales/`

## 🧪 測試

執行測試:
```bash
npm run test
```

執行測試 UI:
```bash
npm run test:ui
```

測試覆蓋率:
```bash
npm run test -- --coverage
```

## 📝 開發規範

### 命名規範

- **Composable**: use + PascalCase (例如: `useProjects`)
- **Component**: PascalCase (例如: `ProjectList.vue`)
- **API Endpoint**: kebab-case (例如: `/projects/{project-id}`)
- **變數/函數**: camelCase (例如: `fetchProjects`)

### 程式碼風格

- 使用 TypeScript
- 遵循 Vue 3 Composition API 風格
- 優先使用 `<script setup>` 語法
- 所有 API 呼叫必須有錯誤處理

### Git Commit 規範

使用 Conventional Commits 格式:
```
feat: 新增功能
fix: 修復錯誤
docs: 文件更新
style: 程式碼格式調整
refactor: 重構
test: 測試相關
chore: 其他雜項
```

## 🚀 開發流程

### 新增功能流程

1. **查看需求**: 閱讀 [Feature Specification](../../specs/003-crm-questionnaire/spec.md)
2. **設計 API**: 參考或更新 [API Requirements](./API_REQUIREMENTS.md)
3. **實作 Composable**: 在 `app/composables/` 新增或修改
4. **建立頁面/元件**: 在 `app/pages/` 或 `app/components/` 實作
5. **撰寫測試**: 在 `tests/` 新增測試案例
6. **更新文件**: 更新 [API Mapping](./API_MAPPING.md)

### 除錯流程

1. **檢查錯誤訊息**: 查看瀏覽器 Console
2. **確認 API 狀態**: 使用瀏覽器 Network 面板檢查請求/回應
3. **查看 API 文件**: 對照 [API Requirements](./API_REQUIREMENTS.md) 確認格式
4. **檢查權限**: 確認使用者角色與權限是否正確
5. **查看狀態**: 使用 Vue DevTools 檢查 Pinia Store 狀態

## 🔗 相關連結

### 內部文件
- [Feature Specification](../../specs/003-crm-questionnaire/spec.md)
- [Project Plan](../../specs/003-crm-questionnaire/plan.md)
- [Tasks](../../specs/003-crm-questionnaire/tasks.md)
- [OpenAPI Contract](../../specs/003-crm-questionnaire/contracts/openapi.yaml)

### 外部資源
- [Nuxt 3 Documentation](https://nuxt.com/docs)
- [Vue 3 Documentation](https://vuejs.org/)
- [Pinia Documentation](https://pinia.vuejs.org/)
- [Nuxt UI Documentation](https://ui.nuxt.com/)

## 📞 支援

如有問題或建議，請：
1. 查看本文件索引尋找相關資訊
2. 查看 [API Requirements](./API_REQUIREMENTS.md) 或 [API Mapping](./API_MAPPING.md)
3. 聯繫專案團隊成員

## 📅 更新歷史

| 日期 | 版本 | 更新內容 |
|------|------|----------|
| 2024-12-02 | 1.0.0 | 初始版本，包含 API Requirements 與 API Mapping |

---

最後更新: 2024-12-02
