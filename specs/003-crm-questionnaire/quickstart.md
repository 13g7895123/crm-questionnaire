# Quickstart Guide

## 1. Initialize Project (Manual Step)

請依照以下步驟初始化 Nuxt 3 專案：

```bash
# 1. 建立專案
npx nuxi@latest init frontend

# 2. 進入目錄
cd frontend

# 3. 安裝相依套件
npm install
```

## 2. Install Dependencies

安裝本計畫所需的套件：

```bash
# UI & Icons
npm install @nuxt/ui

# State Management
npm install @pinia/nuxt pinia

# Localization
npm install @nuxtjs/i18n

# Testing
npm install --save-dev vitest @nuxt/test-utils
```

## 3. Configure Nuxt

修改 `nuxt.config.ts` 以啟用模組：

```typescript
// nuxt.config.ts
export default defineNuxtConfig({
  modules: [
    '@nuxt/ui',
    '@pinia/nuxt',
    '@nuxtjs/i18n'
  ],
  i18n: {
    locales: ['zh', 'en'],
    defaultLocale: 'zh',
    vueI18n: './i18n.config.ts' // Create this file
  }
})
```

## 4. Create Directory Structure

建立計畫中定義的目錄結構：

```bash
mkdir -p src/components src/composables src/pages src/stores src/layouts src/middleware
mkdir -p docs
```

## 5. Run Development Server

```bash
npm run dev
```

## 6. Documentation

API 需求文件請參閱 `frontend/docs/api-requirements.md` (需自行建立或由後續任務生成)。
