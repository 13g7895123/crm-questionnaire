# Research & Technology Decisions

## 1. UI Framework & Styling
- **Decision**: **Nuxt UI** (based on Tailwind CSS)
- **Rationale**: 
  - 專為 Nuxt 3 設計，整合度最高。
  - 內建 Tailwind CSS，樣式調整彈性大。
  - 提供現代化、無障礙 (Accessible) 的元件，符合 Constitution 要求。
  - 輕量級，適合 MVP 快速開發。
- **Alternatives Considered**:
  - **Element Plus**: 雖然在企業後台常見，但 Bundle size 較大，且樣式客製化較繁瑣。
  - **Vuetify**: 設定較複雜，對 Nuxt 3 的支援度雖已成熟但學習曲線較高。

## 2. State Management
- **Decision**: **Pinia**
- **Rationale**: 
  - Vue 3 與 Nuxt 3 官方推薦的狀態管理庫。
  - 支援 TypeScript 推斷，開發體驗佳。
  - 模組化設計 (Stores)，易於維護與測試。
- **Alternatives Considered**:
  - **Vuex**: 已過時，不再推薦用於新專案。

## 3. API Management Strategy
- **Decision**: **Composables wrapping `useFetch`**
- **Rationale**: 
  - 符合 Prompt 要求 "Use Composables for all API management"。
  - 利用 Nuxt 3 的 `useFetch` 處理 SSR/CSR 資料獲取與快取。
  - 封裝成 `useAuth`, `useProjects` 等 Composables，業務邏輯與 UI 分離。
  - 統一處理 Error Handling 與 Interceptors。

## 4. Testing Strategy
- **Decision**: **Vitest + @nuxt/test-utils**
- **Rationale**: 
  - Vitest 速度快，與 Vite 整合完美。
  - `@nuxt/test-utils` 提供 Nuxt 環境模擬，方便測試 Composables 與 Components。
- **Alternatives Considered**:
  - **Jest**: 設定繁瑣，速度較慢。

## 5. Localization
- **Decision**: **@nuxtjs/i18n**
- **Rationale**: 
  - Nuxt 官方整合方案。
  - 支援路由前綴 (Route Prefixing) 與 SEO 優化。
  - 支援 Lazy Loading 語言檔。
