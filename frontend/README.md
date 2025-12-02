# CRM 問卷系統 - 前端應用程式

CRM 問卷系統的前端應用程式，使用 Nuxt 3 開發，提供會員中心、SAQ 專案管理、衝突資產管理、問卷填寫與多階段審核功能。

## 專案結構

```
frontend/
├── app/                    # Nuxt 3 主要源碼目錄
│   ├── assets/            # 靜態資源（CSS、圖片等）
│   ├── components/        # Vue 元件
│   ├── composables/       # API Composables
│   ├── layouts/           # 頁面佈局
│   ├── middleware/        # 路由中介層
│   ├── pages/             # 頁面路由
│   ├── stores/            # Pinia 狀態管理
│   ├── types/             # TypeScript 型別定義
│   └── utils/             # 工具函數
├── docs/                  # API 需求文件
├── public/                # 公開靜態資源
└── tests/                 # 測試檔案
```

## 📚 API 需求文件

完整的 API 需求文件請參考：

- **[API 需求文件總覽](./docs/API-REQUIREMENTS.md)**

### API 模組文件

- [認證與授權 API](./docs/api/auth.md)
- [會員中心與用戶管理 API](./docs/api/users.md)
- [部門管理 API](./docs/api/departments.md)
- [專案管理 API (SAQ & 衝突資產)](./docs/api/projects.md)
- [範本管理 API](./docs/api/templates.md)
- [問卷填寫與答案 API](./docs/api/answers.md)
- [多階段審核 API](./docs/api/reviews.md)
- [供應商管理 API](./docs/api/suppliers.md)
- [錯誤處理規範](./docs/api/error-handling.md)
- [資料模型定義](./docs/api/data-models.md)

## 技術棧

- **框架**: Nuxt 3 (Vue 3)
- **語言**: TypeScript
- **狀態管理**: Pinia
- **UI 框架**: @nuxt/ui (Tailwind CSS)
- **國際化**: Vue I18n
- **測試**: Vitest, Nuxt Test Utils
- **HTTP 客戶端**: Native Fetch API (wrapped in composables)

## 📖 更多資訊

Look at the [Nuxt documentation](https://nuxt.com/docs/getting-started/introduction) to learn more.

## Setup

Make sure to install dependencies:

```bash
# npm
npm install

# pnpm
pnpm install

# yarn
yarn install

# bun
bun install
```

## Development Server

Start the development server on `http://localhost:3000`:

```bash
# npm
npm run dev

# pnpm
pnpm dev

# yarn
yarn dev

# bun
bun run dev
```

## Production

Build the application for production:

```bash
# npm
npm run build

# pnpm
pnpm build

# yarn
yarn build

# bun
bun run build
```

Locally preview production build:

```bash
# npm
npm run preview

# pnpm
pnpm preview

# yarn
yarn preview

# bun
bun run preview
```

Check out the [deployment documentation](https://nuxt.com/docs/getting-started/deployment) for more information.

## Testing

Run unit tests:

```bash
# npm
npm run test

# pnpm
pnpm test

# yarn
yarn test
```

Run tests with UI:

```bash
npm run test:ui
```

## 功能特色

### 會員中心
- 使用者登入/登出
- 個人資料管理
- 密碼修改
- 多語系切換（繁體中文、英文）

### SAQ 專案管理
- 專案 CRUD 操作
- 範本管理與版本控制
- 供應商指派
- 多階段審核流程設定

### 衝突資產管理
- 與 SAQ 相同的功能架構
- 獨立的資料管理

### 問卷填寫
- 多種題型支援（簡答、數字、日期、是非、單選、多選、檔案上傳、評分）
- 草稿自動儲存
- 答案驗證
- 檔案上傳

### 多階段審核
- 可配置的審核流程（1-5 階段）
- 部門權限控管
- 審核歷程記錄
- 核准/退回機制

## 授權

Copyright © 2025 CRM Questionnaire System
