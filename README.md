# CRM 問卷系統 (CRM Questionnaire System)

一個基於 Nuxt 3 開發的企業級問卷管理系統，專為製造商與供應商之間的供應鏈稽核與合規管理而設計。系統支援 SAQ（自我評估問卷）與衝突礦產管理，並提供完整的多階段審核流程。

[English](#english) | [繁體中文](#繁體中文)

---

## 繁體中文

### 📋 專案簡介

CRM 問卷系統是一個全面的供應鏈管理解決方案，主要功能包括：

- **會員中心與帳戶管理**：使用者可管理個人資料、密碼，並切換多語系介面
- **SAQ 模組**：自我評估問卷的建立、管理與填寫
- **衝突礦產模組**：衝突礦產調查問卷的管理與追蹤
- **多階段審核流程**：支援可配置的部門級審核工作流程
- **範本與版本控制**：問卷範本管理與版本追蹤
- **權限管理**：區分製造商（主辦方）與供應商角色

### 🎯 主要功能

#### 會員中心
- 多語系支援（繁體中文、英文）
- 個人資料管理（姓名、Email、電話、部門）
- 密碼修改
- 應用程式導航（帳戶管理、SAQ、衝突資產）

#### SAQ 管理
- 專案生命週期管理（建立、編輯、刪除）
- 範本管理與版本控制
- 供應商指派機制
- 多階段審核流程配置
- 問卷填寫與進度暫存
- 審核歷程追蹤

#### 衝突礦產管理
- 獨立的專案與範本管理
- 與 SAQ 相同的功能架構
- 資料邏輯隔離

#### 審核系統
- 可配置的多階段審核流程（1-5 個階段）
- 部門級權限控管
- 核准/退回機制
- 完整的審核歷程記錄

### 🏗️ 技術架構

#### 前端技術棧
- **框架**: Nuxt 3
- **UI 庫**: Nuxt UI
- **狀態管理**: Pinia
- **國際化**: @nuxtjs/i18n
- **測試**: Vitest + Vue Test Utils
- **語言**: TypeScript

#### 專案結構
```
crm-questionnaire/
├── frontend/              # Nuxt 3 前端應用
│   ├── app/
│   │   ├── pages/        # 頁面路由
│   │   │   ├── account/  # 帳戶管理
│   │   │   ├── saq/      # SAQ 模組
│   │   │   ├── conflict/ # 衝突礦產模組
│   │   │   ├── review/   # 審核介面
│   │   │   └── supplier/ # 供應商介面
│   │   ├── components/   # Vue 元件
│   │   ├── composables/  # Composition API
│   │   ├── stores/       # Pinia stores
│   │   ├── types/        # TypeScript 類型定義
│   │   ├── locales/      # 語言檔案
│   │   └── middleware/   # 路由中介軟體
│   └── tests/            # 測試檔案
├── specs/                # 需求規格文件
│   └── 003-crm-questionnaire/
│       ├── spec.md       # 功能規格
│       ├── data-model.md # 資料模型
│       ├── plan.md       # 開發計畫
│       └── tasks.md      # 任務清單
└── README.md             # 專案說明（本檔案）
```

### 🚀 快速開始

#### 環境需求
- Node.js 18.x 或更高版本
- npm、pnpm、yarn 或 bun

#### 安裝步驟

1. **克隆專案**
```bash
git clone https://github.com/13g7895123/crm-questionnaire.git
cd crm-questionnaire
```

2. **安裝依賴**
```bash
cd frontend
npm install
```

3. **啟動開發伺服器**
```bash
npm run dev
```

應用程式將在 `http://localhost:3000` 啟動

#### 其他指令

```bash
# 建置生產版本
npm run build

# 預覽生產版本
npm run preview

# 執行測試
npm run test

# 執行測試（UI 模式）
npm run test:ui
```

### 📚 使用者故事

#### 1. 會員中心與帳戶管理
使用者登入後進入會員中心，可以看到應用程式列表（帳戶管理、SAQ、衝突資產），並可修改個人資料與密碼，切換語系。

#### 2. SAQ 專案與範本管理
製造商使用者可以管理 SAQ 專案與範本。建立專案時需選擇年份、範本，並指派給特定的供應商。

#### 3. 衝突資產管理
製造商使用者可以管理衝突資產專案與範本，功能機制與 SAQ 相同但資料獨立。

#### 4. 問卷填寫
被指派的供應商使用者可進入專案進行回答，支援填寫進度暫存，完成後提交進入審核流程。

#### 5. 多階段部門審核
專案提交後進入多階段審核流程，審核者可檢視專案並決定核准或退回。

### 🔑 核心實體

- **Organization（組織）**：區分製造商（Host）與供應商（Supplier）
- **Department（部門）**：組織內的部門單位
- **User（使用者）**：系統使用者，歸屬於特定組織與部門
- **Project（專案）**：SAQ/衝突礦產的執行實體
- **Template（範本）**：問卷結構藍圖
- **TemplateVersion（範本版本）**：範本的特定快照
- **Question（題目）**：問卷中的具體問題
- **Answer（答案）**：專案中針對題目的回答
- **ReviewLog（審核紀錄）**：專案的審核歷程
- **ReviewStage（審核階段）**：審核流程的步驟定義

### 📖 文件

詳細的功能規格與設計文件請參考 `specs/003-crm-questionnaire/` 目錄：

- [功能規格書 (spec.md)](specs/003-crm-questionnaire/spec.md)
- [資料模型 (data-model.md)](specs/003-crm-questionnaire/data-model.md)
- [開發計畫 (plan.md)](specs/003-crm-questionnaire/plan.md)
- [任務清單 (tasks.md)](specs/003-crm-questionnaire/tasks.md)

### 🌍 多語系支援

系統支援以下語言：
- 繁體中文 (zh-TW) - 預設語言
- English (en)

語言檔案位於 `frontend/app/locales/` 目錄

### 🧪 測試

專案使用 Vitest 進行單元測試與元件測試：

```bash
# 執行所有測試
npm run test

# 執行測試並開啟 UI
npm run test:ui
```

測試檔案位於 `frontend/tests/` 目錄

### 📝 開發指南

#### 題目類型
系統支援以下題目類型：
- 基本題型（單選、多選、簡答）
- 日期
- 數字
- 布林值
- 檔案上傳
- 評分量表

#### 專案狀態
- DRAFT（草稿）
- IN_PROGRESS（進行中）
- SUBMITTED（已提交）
- REVIEWING（審核中）
- APPROVED（已核准）
- RETURNED（已退回）

#### 審核流程
- 可於專案層級配置審核階段（1-5 個階段）
- 每個階段由特定部門負責
- 支援核准進入下一階段或退回給供應商

### 🤝 貢獻指南

歡迎提交 Issue 或 Pull Request。在提交 PR 前，請確保：

1. 程式碼符合專案的程式碼風格
2. 所有測試通過
3. 新功能包含適當的測試
4. 更新相關文件

### 📄 授權

此專案的授權資訊請參閱 LICENSE 檔案

### 📧 聯絡資訊

如有任何問題或建議，請透過 GitHub Issues 與我們聯繫。

---

## English

### 📋 Project Overview

The CRM Questionnaire System is a comprehensive supply chain management solution built with Nuxt 3, designed for audit and compliance management between manufacturers and suppliers. The system supports SAQ (Self-Assessment Questionnaire) and Conflict Minerals management with complete multi-stage review workflows.

### 🎯 Key Features

#### Member Center
- Multi-language support (Traditional Chinese, English)
- Personal profile management (Name, Email, Phone, Department)
- Password modification
- Application navigation (Account Management, SAQ, Conflict Minerals)

#### SAQ Management
- Project lifecycle management (Create, Edit, Delete)
- Template management with version control
- Supplier assignment mechanism
- Configurable multi-stage review workflow
- Questionnaire filling with progress saving
- Review history tracking

#### Conflict Minerals Management
- Independent project and template management
- Same functional architecture as SAQ
- Logically isolated data

#### Review System
- Configurable multi-stage review workflow (1-5 stages)
- Department-level permission control
- Approve/Return mechanism
- Complete review history logging

### 🏗️ Technical Architecture

#### Frontend Stack
- **Framework**: Nuxt 3
- **UI Library**: Nuxt UI
- **State Management**: Pinia
- **Internationalization**: @nuxtjs/i18n
- **Testing**: Vitest + Vue Test Utils
- **Language**: TypeScript

### 🚀 Quick Start

#### Prerequisites
- Node.js 18.x or higher
- npm, pnpm, yarn, or bun

#### Installation

1. **Clone the repository**
```bash
git clone https://github.com/13g7895123/crm-questionnaire.git
cd crm-questionnaire
```

2. **Install dependencies**
```bash
cd frontend
npm install
```

3. **Start development server**
```bash
npm run dev
```

The application will be available at `http://localhost:3000`

#### Available Commands

```bash
# Build for production
npm run build

# Preview production build
npm run preview

# Run tests
npm run test

# Run tests with UI
npm run test:ui
```

### 📚 User Stories

#### 1. Member Center & Account Management
After login, users can access the member center with application list (Account Management, SAQ, Conflict Minerals), modify personal information and password, and switch languages.

#### 2. SAQ Project & Template Management
Manufacturer users can manage SAQ projects and templates. When creating a project, they need to select year, template, and assign to specific suppliers.

#### 3. Conflict Minerals Management
Manufacturer users can manage Conflict Minerals projects and templates with the same functionality as SAQ but with isolated data.

#### 4. Questionnaire Answering
Assigned supplier users can enter projects to provide answers, with support for progress saving and submission to the review workflow.

#### 5. Multi-stage Departmental Review
After submission, projects enter a multi-stage review workflow where reviewers can examine projects and approve or return them.

### 🔑 Core Entities

- **Organization**: Distinguishes manufacturers (Host) and suppliers (Supplier)
- **Department**: Departmental units within an organization
- **User**: System users belonging to specific organizations and departments
- **Project**: Execution entities for SAQ/Conflict Minerals
- **Template**: Questionnaire structure blueprint
- **TemplateVersion**: Specific snapshot of a template
- **Question**: Specific questions in the questionnaire
- **Answer**: Responses to questions in projects
- **ReviewLog**: Project review history
- **ReviewStage**: Step definitions in the review workflow

### 📖 Documentation

For detailed specifications and design documents, see the `specs/003-crm-questionnaire/` directory:

- [Feature Specification (spec.md)](specs/003-crm-questionnaire/spec.md)
- [Data Model (data-model.md)](specs/003-crm-questionnaire/data-model.md)
- [Development Plan (plan.md)](specs/003-crm-questionnaire/plan.md)
- [Task List (tasks.md)](specs/003-crm-questionnaire/tasks.md)

### 🌍 Internationalization

Supported languages:
- Traditional Chinese (zh-TW) - Default
- English (en)

Language files are located in `frontend/app/locales/`

### 🧪 Testing

The project uses Vitest for unit and component testing:

```bash
# Run all tests
npm run test

# Run tests with UI
npm run test:ui
```

Test files are located in `frontend/tests/`

### 📝 Development Guide

#### Question Types
The system supports the following question types:
- Basic types (Single choice, Multiple choice, Short answer)
- Date
- Number
- Boolean
- File upload
- Rating scale

#### Project Status
- DRAFT
- IN_PROGRESS
- SUBMITTED
- REVIEWING
- APPROVED
- RETURNED

#### Review Workflow
- Configurable at project level (1-5 stages)
- Each stage is responsible by a specific department
- Supports approval to next stage or return to supplier

### 🤝 Contributing

Issues and Pull Requests are welcome. Before submitting a PR, please ensure:

1. Code follows the project's coding style
2. All tests pass
3. New features include appropriate tests
4. Related documentation is updated

### 📄 License

Please refer to the LICENSE file for licensing information

### 📧 Contact

For any questions or suggestions, please contact us through GitHub Issues.
