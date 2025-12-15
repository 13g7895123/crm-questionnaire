# Debug Report: Malformed API URL with "undefined"

**Date:** 2025-12-11
**Issue:** API calls failing with malformed URL `/saq/templates/undefined/api/v1/templates/4/structure`.

## 1. Problem Analysis

The user reported that the application is making a request to:
`http://<host>/saq/templates/undefined/api/v1/templates/4/structure`

This URL is incorrect because it includes the current page path (`/saq/templates/`) followed by `undefined` before the API path.

## 2. Root Cause

The issue stems from how the API URL is constructed in the frontend components, specifically in `frontend/app/pages/saq/templates/[id].vue`.

### Code Snippet
```typescript
// frontend/app/pages/saq/templates/[id].vue

const config = useRuntimeConfig()
// ...
const { data: templateData, error: apiError } = await useFetch(
  `${config.public.apiBase}/api/v1/templates/${templateId.value}/structure`,
  // ...
)
```

### Explanation
1.  The code attempts to access `config.public.apiBase`.
2.  However, `nuxt.config.ts` does **not** define `runtimeConfig`.
3.  Therefore, `config.public.apiBase` is `undefined`.
4.  When used in the template literal `${config.public.apiBase}/api/...`, it is converted to the string `"undefined"`.
5.  The resulting URL string is `"undefined/api/v1/templates/4/structure"`.
6.  Since this string does not start with `/` or `http`, the browser treats it as a **relative path**.
7.  The browser resolves this relative path against the current page URL (`/saq/templates/4`).
8.  **Result:** `/saq/templates/undefined/api/v1/templates/4/structure`.

## 3. Affected Pages

The following pages directly use `config.public.apiBase` and are affected by this issue:

1.  `frontend/app/pages/saq/templates/[id].vue` (The reported page)
2.  `frontend/app/pages/templates/index.vue`
3.  `frontend/app/pages/templates/[id]/preview.vue`

## 4. Solution

To fix this, we need to define the `runtimeConfig` in `nuxt.config.ts` and provide a default value for `apiBase`.

### Recommended Change in `nuxt.config.ts`

```typescript
export default defineNuxtConfig({
  // ...
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || ''
    }
  },
  // ...
})
```

Setting `apiBase` to an empty string `''` by default will ensure that the constructed URL starts with `/api/...` (e.g., `/api/v1/templates/...`), which is an absolute path relative to the domain root. This will correctly route requests to the backend (via the Nitro dev proxy or Nginx in production).
