// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-12-02',
  app: {
    head: {
      title: 'CRM 問卷系統',
      titleTemplate: '%s - CRM 問卷系統',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' }
      ]
    }
  },
  srcDir: 'app/',
  devtools: { enabled: true },
  modules: [
    '@nuxt/ui',
    '@pinia/nuxt',
    '@nuxtjs/i18n'
  ],
  icon: {
    provider: 'iconify',
    serverBundle: 'local',
    collections: ['heroicons']
  },
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || ''
    }
  },
  colorMode: {
    preference: process.env.COLOR_MODE || 'light',
    fallback: 'light',
    storageKey: 'nuxt-color-mode-config'
  },
  i18n: {
    locales: [
      { code: 'zh-TW', language: 'zh-TW', name: '繁體中文', file: 'zh-TW.json' },
      { code: 'en', language: 'en-US', name: 'English', file: 'en.json' },
      { code: 'zh', language: 'zh-TW', name: '繁體中文', file: 'zh-TW.json' }
    ],
    defaultLocale: 'zh-TW',
    strategy: 'prefix_except_default',
    langDir: 'locales',
    detectBrowserLanguage: {
      useCookie: true,
      cookieKey: 'i18n_redirected',
      redirectOn: 'root',
    }
  },
  devServer: {
    port: 8104
  },
  vite: {
    server: {
      // 開發環境不強制使用特定 host
      // allowedHosts 僅在正式環境需要
      ...(process.env.NODE_ENV === 'production' && {
        allowedHosts: ['crm.l']
      }),
      // HMR 配置：開發環境使用預設 WebSocket，正式環境使用 WSS
      hmr: process.env.NODE_ENV === 'production'
        ? {
          protocol: 'wss',
          host: 'crm.l',
          clientPort: 443
        }
        : true  // 開發環境使用預設配置 (ws://localhost:8104)
    }
  },
})
