// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  modules: [
    '@nuxt/ui',
    '@pinia/nuxt',
    '@nuxtjs/i18n'
  ],
  i18n: {
    locales: [
      { code: 'zh-TW', language: 'zh-TW', name: '繁體中文' },
      { code: 'en', language: 'en', name: 'English' }
    ],
    defaultLocale: 'zh-TW',
    fallbackLocale: 'zh-TW',
    strategy: 'prefix_except_default'
  }
})
