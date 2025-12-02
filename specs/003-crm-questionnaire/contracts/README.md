# API Contracts Documentation

這個目錄包含 CRM Questionnaire System 的 API 契約文件。

This directory contains API contract documentation for the CRM Questionnaire System.

## 文件說明 (Document Overview)

### 1. API-Requirements.md
**完整的 API 需求文件 (Comprehensive API Requirements Document)**

這是一份詳細的中英雙語 API 需求文件，涵蓋前端應用程式所需的所有 API 端點。

This is a detailed bilingual (Chinese/English) API requirements document covering all API endpoints required by the frontend application.

**包含內容 (Contents):**
- 26 個 API 端點的完整規格
- 請求/回應範例
- 錯誤處理機制
- 資料類型定義
- 認證與授權說明

**適合對象 (Target Audience):**
- 前端開發者
- 後端開發者
- API 設計師
- 專案經理
- QA 測試人員

### 2. openapi.yaml
**OpenAPI 3.0 規格文件 (OpenAPI 3.0 Specification)**

這是標準的 OpenAPI 3.0 格式規格文件，可用於自動化工具和 API 測試。

This is a standard OpenAPI 3.0 format specification that can be used with automation tools and API testing.

**用途 (Use Cases):**
- 使用 Swagger UI 視覺化 API
- 自動生成 API 客戶端程式碼
- API 測試工具整合 (Postman, Insomnia)
- 自動化測試
- API Mock 服務

## 如何使用 (How to Use)

### 查看 API 文件 (View API Documentation)

#### 方法 1: 閱讀 Markdown 文件
直接開啟 `API-Requirements.md` 查看詳細的 API 規格。

#### 方法 2: 使用 Swagger UI
```bash
# 使用 Docker 運行 Swagger UI
docker run -p 8080:8080 -e SWAGGER_JSON=/contracts/openapi.yaml -v $(pwd):/contracts swaggerapi/swagger-ui

# 瀏覽器開啟
open http://localhost:8080
```

#### 方法 3: 使用線上工具
將 `openapi.yaml` 的內容複製到以下任一工具：
- [Swagger Editor](https://editor.swagger.io/)
- [OpenAPI Viewer](https://redocly.github.io/redoc/)

### 匯入 Postman (Import to Postman)

1. 開啟 Postman
2. 點擊 "Import" 按鈕
3. 選擇 `openapi.yaml` 檔案
4. Postman 會自動建立 Collection 和所有請求

### 匯入 Insomnia (Import to Insomnia)

1. 開啟 Insomnia
2. 點擊 Application > Preferences > Data > Import Data
3. 選擇 `openapi.yaml` 檔案
4. Insomnia 會自動建立所有請求

### 生成程式碼 (Generate Code)

使用 OpenAPI Generator 生成客戶端程式碼：

```bash
# 安裝 OpenAPI Generator
npm install @openapitools/openapi-generator-cli -g

# 生成 TypeScript Axios 客戶端
openapi-generator-cli generate \
  -i openapi.yaml \
  -g typescript-axios \
  -o ./generated/api-client

# 生成 Python 客戶端
openapi-generator-cli generate \
  -i openapi.yaml \
  -g python \
  -o ./generated/python-client
```

## API 端點概覽 (API Endpoints Overview)

### 認證 (Authentication)
- `POST /auth/login` - 使用者登入

### 使用者管理 (User Management)
- `PUT /users/{userId}` - 更新使用者資料
- `POST /users/change-password` - 變更密碼

### 專案管理 (Project Management)
- `GET /projects` - 取得專案列表
- `POST /projects` - 建立專案
- `GET /projects/{id}` - 取得單一專案
- `PUT /projects/{id}` - 更新專案
- `DELETE /projects/{id}` - 刪除專案

### 範本管理 (Template Management)
- `GET /templates` - 取得範本列表
- `POST /templates` - 建立範本
- `GET /templates/{id}` - 取得單一範本
- `PUT /templates/{id}` - 更新範本
- `DELETE /templates/{id}` - 刪除範本
- `POST /templates/{id}/publish` - 發布範本版本

### 供應商管理 (Supplier Management)
- `GET /suppliers` - 取得供應商列表

### 部門管理 (Department Management)
- `GET /departments` - 取得部門列表
- `POST /departments` - 建立部門
- `PUT /departments/{id}` - 更新部門
- `DELETE /departments/{id}` - 刪除部門

### 問卷回答 (Answer Management)
- `GET /projects/{projectId}/answers` - 取得專案答案
- `POST /projects/{projectId}/answers` - 儲存答案
- `POST /projects/{projectId}/submit` - 提交答案

### 審核流程 (Review Process)
- `GET /review/pending` - 取得待審核專案
- `GET /projects/{projectId}/review-logs` - 取得審核記錄
- `POST /projects/{projectId}/approve` - 核准專案
- `POST /projects/{projectId}/return` - 退回專案

## 版本控制 (Version Control)

API 文件的版本與專案版本保持同步。

當 API 有重大變更時：
1. 更新 `API-Requirements.md` 文件
2. 更新 `openapi.yaml` 規格
3. 更新 `info.version` 欄位
4. 在變更歷史中記錄變更內容

## 貢獻指南 (Contributing)

修改 API 文件時請確保：
1. 同時更新 `API-Requirements.md` 和 `openapi.yaml`
2. 保持兩份文件的一致性
3. 添加請求/回應範例
4. 記錄所有錯誤情況
5. 更新變更歷史

## 相關資源 (Related Resources)

- [Frontend TypeScript Types](/frontend/app/types/index.ts)
- [Data Model Documentation](../data-model.md)
- [Feature Specification](../spec.md)
- [OpenAPI Specification](https://swagger.io/specification/)

## 聯絡資訊 (Contact)

如有任何問題或建議，請：
- 開啟 GitHub Issue
- 聯繫專案維護者

---

**最後更新 (Last Updated):** 2025-12-02  
**文件版本 (Document Version):** 1.0.0
