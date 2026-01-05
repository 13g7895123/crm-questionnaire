# CRM Questionnaire - 部署腳本使用說明

本文件說明開發環境 (`scripts/dev.sh`) 與生產環境 (`scripts/prod.sh`) 的使用方式。

---

## 目錄

- [環境需求](#環境需求)
- [開發環境 (scripts/dev.sh)](#開發環境-devsh)
- [生產環境 (scripts/prod.sh)](#生產環境-prodsh)
- [常見問題](#常見問題)

---

## 環境需求

- Docker Engine 20.10+
- Docker Compose V2 (`docker compose` 指令)
- Bash Shell

---

## 開發環境 (scripts/dev.sh)

### 說明

開發環境腳本用於啟動後端 API 服務、資料庫和 phpMyAdmin。前端開發使用 `npm run dev` 單獨啟動，並串接後端 API。

### 使用方式

```bash
# 1. 複製環境設定檔（首次使用）
cp .env.example .env

# 2. 啟動開發環境
./scripts/dev.sh
```

### 啟動後的服務

| 服務 | 預設網址 | 說明 |
|------|----------|------|
| Backend API | http://localhost:8080 | CodeIgniter 4 後端 |
| phpMyAdmin | http://localhost:8081 | 資料庫管理介面 |
| MariaDB | localhost:3306 | 資料庫服務 |

### 常用指令

```bash
# 查看服務日誌
docker compose logs -f

# 查看特定服務日誌
docker compose logs -f backend

# 停止所有服務
docker compose down

# 停止並清除資料（含資料庫）
docker compose down -v

# 重新建置服務
docker compose up -d --build

# 進入後端容器執行 CLI 指令
docker compose exec backend php spark migrate
docker compose exec backend php spark db:seed
```

### 前端開發

開發環境只啟動後端服務，前端需要另外啟動：

```bash
cd frontend
npm install    # 首次使用
npm run dev    # 啟動開發伺服器
```

前端預設運行在 http://localhost:3000

### 環境變數說明 (.env)

```env
# 服務對外 Port
BACKEND_PORT=8080    # 後端 API Port
DB_PORT=3306         # 資料庫 Port
PMA_PORT=8081        # phpMyAdmin Port

# 資料庫設定
DB_HOST=db                    # Docker 內部主機名稱
DB_ROOT_PASSWORD=root_password
DB_DATABASE=crm_questionnaire
DB_USERNAME=crm_user
DB_PASSWORD=crm_password
```

---

## 生產環境 (scripts/prod.sh)

### 說明

生產環境腳本實現藍綠部署 (Blue-Green Deployment) 策略，確保零停機時間更新。

### 架構說明

```
                    ┌─────────────┐
                    │   Nginx     │
                    │  (Gateway)  │
                    └──────┬──────┘
                           │
           ┌───────────────┼───────────────┐
           │               │               │
    ┌──────▼──────┐ ┌──────▼──────┐ ┌──────▼──────┐
    │  Frontend   │ │ Backend-Blue│ │Backend-Green│
    │  (Static)   │ │   (Active)  │ │  (Standby)  │
    └─────────────┘ └─────────────┘ └─────────────┘
```

### 使用方式

```bash
# 1. 複製生產環境設定檔（首次使用）
cp .env.production.example .env.production

# 2. 修改生產環境設定
vim .env.production  # 設定安全的密碼等

# 3. 執行部署
./scripts/prod.sh deploy
```

### 指令列表

| 指令 | 說明 |
|------|------|
| `./scripts/prod.sh` 或 `./scripts/prod.sh deploy` | 執行藍綠部署 |
| `./scripts/prod.sh rollback` | 回滾到上一個版本 |
| `./scripts/prod.sh switch blue` | 手動切換流量到 Blue |
| `./scripts/prod.sh switch green` | 手動切換流量到 Green |
| `./scripts/prod.sh status` | 查看目前部署狀態 |
| `./scripts/prod.sh stop` | 停止所有生產服務 |
| `./scripts/prod.sh logs` | 查看所有服務日誌 |
| `./scripts/prod.sh logs nginx` | 查看特定服務日誌 |
| `./scripts/prod.sh migrate` | 手動執行資料庫遷移 |
| `./scripts/seed.sh` | 執行所有資料庫 Seeder |
| `./scripts/seed.sh initial` | 只執行初始資料 Seeder |
| `./scripts/seed.sh list` | 列出可用的 Seeder |

### 部署流程

執行 `./scripts/prod.sh deploy` 時，腳本會自動執行以下步驟：

0. **同步配置** - 將 `.env.production` 的資料庫設定同步到 `backend/.env`
1. **啟動服務** - 啟動資料庫、後端和 Nginx 服務
2. **修復權限** - 確保 `writable` 目錄權限正確
3. **執行遷移** - 運行資料庫 migration
4. **建置前端** - 執行 `npm run generate` 產生靜態檔案到新的顏色
5. **切換流量** - 更新 Nginx 環境變數，將流量導向新版本前端

### 回滾操作

如果部署後發現問題，可以立即回滾：

```bash
./scripts/prod.sh rollback
```

回滾操作只需約 1 秒，因為它只是切換 Nginx 的流量指向。

### 環境變數說明 (.env.production)

```env
# 服務對外 Port
PROD_PORT=80         # 生產環境主要 Port
PMA_PORT=8081        # phpMyAdmin Port（生產環境建議關閉或限制存取）

# 資料庫設定（請使用安全的密碼！）
DB_HOST=db
DB_ROOT_PASSWORD=secure_root_password_change_me
DB_DATABASE=crm_questionnaire
DB_USERNAME=crm_user
DB_PASSWORD=secure_password_change_me

# 藍綠部署
ACTIVE_COLOR=blue    # 初始活躍環境
```

### 手動切換流量

在某些情況下，你可能需要手動切換流量：

```bash
# 切換到 Blue
./scripts/prod.sh switch blue

# 切換到 Green
./scripts/prod.sh switch green
```

### 查看部署狀態

```bash
./scripts/prod.sh status
```

輸出範例：
```
Current active backend: blue

Service status:
NAME                 STATUS    PORTS
crm_backend_blue     running   8080/tcp
crm_backend_green    running   8080/tcp
crm_nginx            running   0.0.0.0:80->80/tcp
crm_db_prod          healthy   3306/tcp
```

---

### 資料庫管理

#### 執行資料庫遷移 (Migration)

部署時會自動執行 migration，但您也可以手動執行：

```bash
./scripts/prod.sh migrate
```

#### 執行資料庫 Seeder

使用 `seed.sh` 腳本來建立預設資料：

```bash
# 執行所有 Seeder
./scripts/seed.sh

# 只執行初始資料
./scripts/seed.sh initial

# 只執行範本資料
./scripts/seed.sh template

# 只執行 SAQ 完整範本
./scripts/seed.sh saq

# 執行自訂 Seeder
./scripts/seed.sh custom YourSeederName

# 列出可用的 Seeder
./scripts/seed.sh list
```

**可用的 Seeder**：
- `InitialDataSeeder` - 建立初始系統資料
- `TemplateSectionsSeeder` - 建立範本區段資料
- `CompleteSAQTemplateSeeder` - 建立完整的 SAQ 範本

**注意**：首次部署後，建議執行 `./scripts/seed.sh` 來建立必要的預設資料。


---

## 常見問題

### Q: 開發環境啟動失敗，顯示 Port 被佔用？

修改 `.env` 檔案中的 Port 設定：
```env
BACKEND_PORT=8090  # 改用其他 Port
```

### Q: 資料庫連線失敗？

**腳本會自動同步配置**：
- `dev.sh` 和 `prod.sh` 會自動將環境變數的資料庫設定同步到 `backend/.env`
- 如果手動修改了資料庫設定，重新執行啟動腳本即可

**手動檢查**：
1. 確認資料庫容器已啟動：`docker compose ps`
2. 確認 `.env` 或 `.env.production` 中的資料庫設定正確
3. 等待資料庫完全啟動（約 10-15 秒）

### Q: 如何完全重置開發環境？

```bash
docker compose down -v  # 停止並刪除所有資料
docker compose up -d --build  # 重新建置並啟動
```

### Q: 生產環境如何檢視特定容器的日誌？

```bash
./scripts/prod.sh logs backend-blue
./scripts/prod.sh logs backend-green
./scripts/prod.sh logs nginx
```

### Q: 如何進入容器執行指令？

```bash
# 開發環境
docker compose exec backend bash
docker compose exec backend php spark migrate

# 生產環境
docker compose -f docker-compose.prod.yml exec backend-blue bash
```

### Q: 前端編譯失敗？

1. 確認 `frontend/` 目錄下有 `package.json`
2. 確認已安裝依賴：`cd frontend && npm install`
3. 本地測試編譯：`npm run generate`

---

## 檔案結構

```
.
├── .env                      # 開發環境變數（git 忽略）
├── .env.example              # 開發環境變數範本
├── .env.production           # 生產環境變數（git 忽略）
├── .env.production.example   # 生產環境變數範本
├── docker-compose.yml        # 開發環境 Docker 設定
├── docker-compose.prod.yml   # 生產環境 Docker 設定
├── scripts/
│   ├── dev.sh                # 開發環境啟動腳本
│   ├── prod.sh               # 生產環境部署腳本
│   └── seed.sh               # 資料庫 Seeder 腳本
├── docker/
│   ├── backend/
│   │   └── Dockerfile        # PHP 後端映像檔
│   └── nginx/
│       └── conf.d/
│           └── default.conf  # Nginx 藍綠部署設定
├── backend/                  # CodeIgniter 4 後端
└── frontend/                 # Nuxt 3 前端
```
