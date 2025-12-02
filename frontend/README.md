# CRM Questionnaire System - Frontend

問卷系統 CRM 的前端應用程式，基於 Nuxt 3 框架開發。

## 📚 文件

- **[API Documentation](./docs/)** - 完整的 API 需求文件與 Composables 對應關係
  - [API Requirements](./docs/API_REQUIREMENTS.md) - 詳細的 API 端點需求
  - [API Mapping](./docs/API_MAPPING.md) - Frontend Composables 與 API 對應
  - [Documentation Index](./docs/README.md) - 文件總覽與開發指南

## 技術棧

- **Framework**: Nuxt 3 (Vue 3 + SSR)
- **State Management**: Pinia
- **UI Framework**: Nuxt UI (@nuxt/ui)
- **Internationalization**: @nuxtjs/i18n
- **Testing**: Vitest + @vue/test-utils
- **TypeScript**: Full type safety

---

## 快速開始

Learn more from the [Nuxt documentation](https://nuxt.com/docs/getting-started/introduction).

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

Run tests:

```bash
# npm
npm run test

# Run tests with UI
npm run test:ui
```

## Project Structure

```
frontend/
├── app/
│   ├── components/      # Vue components
│   ├── composables/     # Composable functions (API wrappers)
│   ├── layouts/         # Page layouts
│   ├── pages/           # Route pages
│   ├── stores/          # Pinia stores
│   ├── types/           # TypeScript type definitions
│   └── utils/           # Utility functions
├── docs/                # 📚 API documentation
├── public/              # Static assets
└── tests/               # Test files
```

## Key Features

- 🔐 JWT Authentication
- 👥 User & Department Management
- 📋 Project Management (SAQ & Conflict Minerals)
- 📝 Template & Question Management
- ✍️ Questionnaire Answering
- ✅ Multi-stage Review Process
- 🌍 Multi-language Support (zh-TW, en)

## Learn More

- [API Documentation](./docs/) - Complete API requirements and usage guide
- [Feature Specification](../specs/003-crm-questionnaire/spec.md) - Detailed feature requirements
- [Nuxt 3 Documentation](https://nuxt.com/docs)
- [Vue 3 Documentation](https://vuejs.org/)
