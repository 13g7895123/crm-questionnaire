# 開發環境配置指南

## 📖 概述

本專案支援**開發環境**和**正式環境**的靈活切換，透過環境變數控制 Cookie 安全性和 CORS 設定。

## 🔄 環境配置對照

| 配置項 | 開發環境 | 正式環境 |
|--------|---------|---------|
| **協定** | HTTP | HTTPS |
| **Cookie Secure** | `false` | `true` |
| **Cookie HttpOnly** | `false` | `true` |
| **Cookie Domain** | `localhost` | `crm.l` |
| **前端端口** | 8104 | 443 (Nginx) |
| **後端端口** | 9104 | 9104 |
| **CORS Origins** | `localhost:*` | `https://crm.l` |
| **HMR Protocol** | WS | WSS |

## 🚀 快速開始

### 開發環境

```bash
# 1. 切換到開發環境
./scripts/use-dev-env.sh

# 2. 啟動後端 (Docker)
docker-compose up -d

# 3. 啟動前端 (npm)
cd frontend
npm run dev
```

前端將運行在: `http://localhost:8104`  
後端 API 位於: `http://localhost:9104`

### 正式環境

```bash
# 1. 切換到正式環境
./scripts/use-prod-env.sh

# 2. 執行部署腳本
./scripts/prod.sh
```

## 📝 環境變數說明

### Cookie 安全設定

- **COOKIE_SECURE**: Cookie 是否僅在 HTTPS 傳輸
  - 開發: `false` (允許 HTTP)
  - 正式: `true` (僅 HTTPS)

- **COOKIE_HTTPONLY**: Cookie 是否禁止 JavaScript 存取
  - 開發: `false` (方便除錯)
  - 正式: `true` (提高安全性)

- **COOKIE_SAMESITE**: Cookie SameSite 屬性
  - 建議: `Lax` (相容性佳)

- **COOKIE_DOMAIN**: Cookie 作用域
  - 開發: `localhost`
  - 正式: `crm.l`

### CORS 設定

- **CORS_ALLOWED_ORIGINS**: 允許的跨域來源 (逗號分隔)
  - 開發: `http://localhost:8104,http://localhost:3000,http://localhost:9104`
  - 正式: `https://crm.l`

## 🔧 手動配置

如果不想使用腳本，可以手動複製環境配置：

```bash
# 使用開發環境
cp .env.development .env

# 使用正式環境
cp .env.production .env
```

## 🐛 常見問題

### 問題 1: Cookie 無法傳遞

**症狀**: 前端無法取得後端設定的 Cookie

**解決方案**:
- 確認使用開發環境配置: `COOKIE_SECURE=false`
- 確認 `COOKIE_DOMAIN=localhost`
- 檢查瀏覽器是否使用 `http://localhost` 而非 `http://127.0.0.1`

### 問題 2: CORS 錯誤

**症狀**: 瀏覽器控制台顯示 CORS 錯誤

**解決方案**:
- 確認前端 URL 在 `CORS_ALLOWED_ORIGINS` 中
- 重啟後端容器使環境變數生效: `docker-compose restart backend`

### 問題 3: 前端 HMR 連線失敗

**症狀**: Nuxt 開發伺服器 HMR 無法連線

**解決方案**:
- 確認未設定 `NODE_ENV=production`
- 檢查 `nuxt.config.ts` 中的 HMR 配置

## 📦 檔案結構

```
.
├── .env                        # 當前使用的配置 (gitignored)
├── .env.development            # 開發環境配置
├── .env.production             # 正式環境配置
├── .env.production.example     # 正式環境配置範例
├── .env.example                # 環境變數說明
├── backend/
│   └── app/
│       └── Config/
│           ├── Cookie.php      # Cookie 配置 (讀取環境變數)
│           └── Cors.php        # CORS 配置 (讀取環境變數)
├── frontend/
│   └── nuxt.config.ts          # Nuxt 配置 (環境判斷)
└── scripts/
    ├── use-dev-env.sh          # 切換到開發環境
    └── use-prod-env.sh         # 切換到正式環境
```

## ✅ 最佳實踐

1. **開發時**: 使用 `./scripts/use-dev-env.sh` 切換到開發環境
2. **部署前**: 使用 `./scripts/use-prod-env.sh` 切換到正式環境
3. **敏感資訊**: 將 `.env.production` 加入 `.gitignore`，避免提交敏感資訊
4. **團隊協作**: 更新 `.env.example` 說明新增的環境變數

## 🔐 安全注意事項

- ⚠️ **切勿**將 `.env.production` 提交到版本控制
- ✅ 正式環境必須使用 HTTPS
- ✅ 正式環境必須啟用 `COOKIE_SECURE=true` 和 `COOKIE_HTTPONLY=true`
- ✅ 定期更新資料庫密碼和其他敏感資訊
