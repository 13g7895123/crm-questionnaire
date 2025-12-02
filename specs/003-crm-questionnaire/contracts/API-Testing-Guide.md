# API 測試指南 (API Testing Guide)

本指南提供 CRM Questionnaire System API 的測試方法和範例。

This guide provides testing methods and examples for the CRM Questionnaire System API.

## 目錄 (Table of Contents)

1. [環境設定 (Environment Setup)](#環境設定-environment-setup)
2. [認證測試 (Authentication Testing)](#認證測試-authentication-testing)
3. [基本測試流程 (Basic Testing Flow)](#基本測試流程-basic-testing-flow)
4. [測試場景 (Testing Scenarios)](#測試場景-testing-scenarios)
5. [錯誤測試 (Error Testing)](#錯誤測試-error-testing)
6. [自動化測試 (Automated Testing)](#自動化測試-automated-testing)

---

## 環境設定 (Environment Setup)

### 必要工具 (Required Tools)

選擇以下任一工具：
- **Postman** - 圖形化介面，適合手動測試
- **curl** - 命令列工具，適合腳本化測試
- **HTTPie** - 使用者友善的命令列工具
- **REST Client (VS Code Extension)** - VS Code 內建測試

### 環境變數 (Environment Variables)

建立以下環境變數：

**Development:**
```
API_BASE_URL=http://localhost:3000/api
```

**Production:**
```
API_BASE_URL=https://your-production-domain.com/api
```

---

## 認證測試 (Authentication Testing)

### 1. 登入並取得 Token

**使用 curl:**
```bash
curl -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "john.doe",
    "password": "SecurePassword123"
  }'
```

**使用 HTTPie:**
```bash
http POST http://localhost:3000/api/auth/login \
  username=john.doe \
  password=SecurePassword123
```

**預期回應:**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": "user-123",
    "username": "john.doe",
    "email": "john.doe@example.com",
    "role": "HOST"
  }
}
```

### 2. 儲存 Token

將返回的 token 儲存為環境變數：

**Bash:**
```bash
export TOKEN="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
```

**PowerShell:**
```powershell
$env:TOKEN="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
```

### 3. 使用 Token 進行請求

**curl:**
```bash
curl -X GET http://localhost:3000/api/projects \
  -H "Authorization: Bearer $TOKEN"
```

**HTTPie:**
```bash
http GET http://localhost:3000/api/projects \
  "Authorization: Bearer $TOKEN"
```

---

## 基本測試流程 (Basic Testing Flow)

### 完整測試流程範例

以下是一個完整的 SAQ 專案測試流程：

#### Step 1: 登入
```bash
# 注意：請替換為實際的使用者帳號密碼
# Note: Replace with actual user credentials
TOKEN=$(curl -s -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"YOUR_USERNAME","password":"YOUR_PASSWORD"}' \
  | jq -r '.token')

echo "Token: $TOKEN"
```

#### Step 2: 取得範本列表
```bash
curl -X GET "http://localhost:3000/api/templates?type=SAQ" \
  -H "Authorization: Bearer $TOKEN" \
  | jq '.'
```

#### Step 3: 取得供應商列表
```bash
curl -X GET http://localhost:3000/api/suppliers \
  -H "Authorization: Bearer $TOKEN" \
  | jq '.'
```

#### Step 4: 建立專案
```bash
PROJECT_ID=$(curl -s -X POST http://localhost:3000/api/projects \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "2025 年度 SAQ 專案",
    "year": 2025,
    "type": "SAQ",
    "templateId": "template-001",
    "supplierId": "supplier-001",
    "reviewConfig": [
      {
        "stageOrder": 1,
        "departmentId": "dept-001"
      }
    ]
  }' | jq -r '.id')

echo "Project ID: $PROJECT_ID"
```

#### Step 5: 取得專案詳情
```bash
curl -X GET "http://localhost:3000/api/projects/$PROJECT_ID" \
  -H "Authorization: Bearer $TOKEN" \
  | jq '.'
```

#### Step 6: 儲存答案（供應商）
```bash
# 先以供應商身份登入
# 注意：請替換為實際的供應商帳號密碼
SUPPLIER_TOKEN=$(curl -s -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"SUPPLIER_USERNAME","password":"SUPPLIER_PASSWORD"}' \
  | jq -r '.token')

# 儲存答案
curl -X POST "http://localhost:3000/api/projects/$PROJECT_ID/answers" \
  -H "Authorization: Bearer $SUPPLIER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "answers": {
      "q1": "我們公司採用 ISO 9001 品質管理系統",
      "q2": 95
    }
  }' | jq '.'
```

#### Step 7: 提交專案
```bash
curl -X POST "http://localhost:3000/api/projects/$PROJECT_ID/submit" \
  -H "Authorization: Bearer $SUPPLIER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "answers": {
      "q1": "我們公司採用 ISO 9001 品質管理系統",
      "q2": 95,
      "q3": "2025-01-15",
      "q4": true
    }
  }' | jq '.'
```

#### Step 8: 審核專案（審核者）
```bash
# 以審核者身份登入
# 注意：請替換為實際的審核者帳號密碼
REVIEWER_TOKEN=$(curl -s -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"REVIEWER_USERNAME","password":"REVIEWER_PASSWORD"}' \
  | jq -r '.token')

# 核准專案
curl -X POST "http://localhost:3000/api/projects/$PROJECT_ID/approve" \
  -H "Authorization: Bearer $REVIEWER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "comment": "資料完整，核准通過"
  }' | jq '.'
```

---

## 測試場景 (Testing Scenarios)

### 場景 1: 範本管理流程

#### 1.1 建立範本
```bash
TEMPLATE_ID=$(curl -s -X POST http://localhost:3000/api/templates \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "新 SAQ 範本",
    "type": "SAQ",
    "questions": [
      {
        "id": "q1",
        "text": "請描述貴公司的品質管理系統",
        "type": "TEXT",
        "required": true,
        "config": {
          "maxLength": 1000
        }
      }
    ]
  }' | jq -r '.id')
```

#### 1.2 更新範本
```bash
curl -X PUT "http://localhost:3000/api/templates/$TEMPLATE_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "更新的 SAQ 範本",
    "questions": [
      {
        "id": "q1",
        "text": "請描述貴公司的品質管理系統",
        "type": "TEXT",
        "required": true
      },
      {
        "id": "q2",
        "text": "請評分貴公司的品質管理成熟度",
        "type": "RATING",
        "required": true,
        "config": {
          "ratingMin": 1,
          "ratingMax": 5,
          "ratingStep": 1
        }
      }
    ]
  }' | jq '.'
```

#### 1.3 發布範本版本
```bash
curl -X POST "http://localhost:3000/api/templates/$TEMPLATE_ID/publish" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{}' | jq '.'
```

### 場景 2: 多階段審核流程

#### 2.1 建立多階段專案
```bash
PROJECT_ID=$(curl -s -X POST http://localhost:3000/api/projects \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "多階段審核專案",
    "year": 2025,
    "type": "SAQ",
    "templateId": "template-001",
    "supplierId": "supplier-001",
    "reviewConfig": [
      {
        "stageOrder": 1,
        "departmentId": "dept-001"
      },
      {
        "stageOrder": 2,
        "departmentId": "dept-002"
      },
      {
        "stageOrder": 3,
        "departmentId": "dept-003"
      }
    ]
  }' | jq -r '.id')
```

#### 2.2 第一階段核准
```bash
curl -X POST "http://localhost:3000/api/projects/$PROJECT_ID/approve" \
  -H "Authorization: Bearer $STAGE1_REVIEWER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "comment": "第一階段核准"
  }' | jq '.'
```

#### 2.3 第二階段退回
```bash
curl -X POST "http://localhost:3000/api/projects/$PROJECT_ID/return" \
  -H "Authorization: Bearer $STAGE2_REVIEWER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "comment": "需要補充更多資料"
  }' | jq '.'
```

#### 2.4 查看審核記錄
```bash
curl -X GET "http://localhost:3000/api/projects/$PROJECT_ID/review-logs" \
  -H "Authorization: Bearer $TOKEN" \
  | jq '.'
```

### 場景 3: 部門管理

#### 3.1 建立部門
```bash
DEPT_ID=$(curl -s -X POST http://localhost:3000/api/departments \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "研發部"
  }' | jq -r '.id')
```

#### 3.2 更新部門
```bash
curl -X PUT "http://localhost:3000/api/departments/$DEPT_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "研發創新部"
  }' | jq '.'
```

#### 3.3 刪除部門
```bash
curl -X DELETE "http://localhost:3000/api/departments/$DEPT_ID" \
  -H "Authorization: Bearer $TOKEN"
```

---

## 錯誤測試 (Error Testing)

### 測試未認證請求
```bash
# 應返回 401 Unauthorized
curl -X GET http://localhost:3000/api/projects
```

### 測試 Token 過期
```bash
# 使用過期的 token
curl -X GET http://localhost:3000/api/projects \
  -H "Authorization: Bearer expired_token_here"
```

### 測試無效參數
```bash
# 缺少必要欄位
curl -X POST http://localhost:3000/api/projects \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "測試專案"
  }'
# 應返回 400 Bad Request
```

### 測試權限不足
```bash
# 供應商嘗試建立專案（應失敗）
curl -X POST http://localhost:3000/api/projects \
  -H "Authorization: Bearer $SUPPLIER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "測試專案",
    "year": 2025,
    "type": "SAQ",
    "templateId": "template-001",
    "supplierId": "supplier-001",
    "reviewConfig": []
  }'
# 應返回 403 Forbidden
```

### 測試資源不存在
```bash
# 查詢不存在的專案
curl -X GET http://localhost:3000/api/projects/non-existent-id \
  -H "Authorization: Bearer $TOKEN"
# 應返回 404 Not Found
```

### 測試資源衝突
```bash
# 嘗試提交已提交的專案
curl -X POST "http://localhost:3000/api/projects/$SUBMITTED_PROJECT_ID/submit" \
  -H "Authorization: Bearer $SUPPLIER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "answers": {}
  }'
# 應返回 409 Conflict
```

---

## 自動化測試 (Automated Testing)

### 使用 Newman (Postman CLI)

#### 安裝 Newman
```bash
npm install -g newman
```

#### 匯出 Postman Collection
1. 在 Postman 中匯出 Collection 為 JSON 檔案
2. 儲存為 `crm-api-tests.json`

#### 執行測試
```bash
newman run crm-api-tests.json \
  --environment dev-environment.json \
  --reporters cli,json \
  --reporter-json-export results.json
```

### 使用 Jest 進行單元測試

```javascript
// api.test.js
const axios = require('axios');

const API_BASE_URL = 'http://localhost:3000/api';
let authToken;

describe('CRM API Tests', () => {
  beforeAll(async () => {
    // 登入並取得 token
    const response = await axios.post(`${API_BASE_URL}/auth/login`, {
      username: 'test.user',
      password: 'password123'
    });
    authToken = response.data.token;
  });

  test('should list projects', async () => {
    const response = await axios.get(`${API_BASE_URL}/projects`, {
      headers: {
        Authorization: `Bearer ${authToken}`
      }
    });
    expect(response.status).toBe(200);
    expect(response.data).toHaveProperty('data');
    expect(Array.isArray(response.data.data)).toBe(true);
  });

  test('should create project', async () => {
    const projectData = {
      name: 'Test Project',
      year: 2025,
      type: 'SAQ',
      templateId: 'template-001',
      supplierId: 'supplier-001',
      reviewConfig: [
        {
          stageOrder: 1,
          departmentId: 'dept-001'
        }
      ]
    };

    const response = await axios.post(`${API_BASE_URL}/projects`, projectData, {
      headers: {
        Authorization: `Bearer ${authToken}`,
        'Content-Type': 'application/json'
      }
    });
    expect(response.status).toBe(201);
    expect(response.data).toHaveProperty('id');
  });

  test('should handle authentication error', async () => {
    try {
      await axios.get(`${API_BASE_URL}/projects`);
    } catch (error) {
      expect(error.response.status).toBe(401);
    }
  });
});
```

### 執行 Jest 測試
```bash
npm test
```

---

## Postman Collection 範例

### 建立 Collection

1. **建立新 Collection**: "CRM Questionnaire API"
2. **設定 Collection 變數**:
   - `base_url`: `http://localhost:3000/api`
   - `token`: (空白，由登入測試設定)

3. **建立資料夾結構**:
   - Authentication
   - User Management
   - Project Management
   - Template Management
   - Supplier Management
   - Department Management
   - Answer Management
   - Review Process

### Pre-request Script (Collection 層級)

```javascript
// 檢查 token 是否存在
if (!pm.collectionVariables.get("token")) {
    console.log("No token found, please run login request first");
}
```

### Tests Script (登入請求)

```javascript
// 儲存 token 到 Collection 變數
if (pm.response.code === 200) {
    const response = pm.response.json();
    pm.collectionVariables.set("token", response.token);
    pm.collectionVariables.set("userId", response.user.id);
    console.log("Token saved successfully");
}

// 測試回應
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has token", function () {
    const response = pm.response.json();
    pm.expect(response).to.have.property('token');
    pm.expect(response).to.have.property('user');
});
```

---

## 效能測試 (Performance Testing)

### 使用 Apache Bench

```bash
# 測試登入端點的效能
ab -n 1000 -c 10 -p login.json -T application/json \
  http://localhost:3000/api/auth/login
```

### 使用 k6

```javascript
// load-test.js
import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
  stages: [
    { duration: '30s', target: 20 },
    { duration: '1m', target: 20 },
    { duration: '30s', target: 0 },
  ],
};

const BASE_URL = 'http://localhost:3000/api';

export default function () {
  // 登入
  let loginRes = http.post(`${BASE_URL}/auth/login`, JSON.stringify({
    username: 'test.user',
    password: 'password123'
  }), {
    headers: { 'Content-Type': 'application/json' },
  });

  check(loginRes, {
    'login successful': (r) => r.status === 200,
  });

  let token = loginRes.json('token');

  // 取得專案列表
  let projectsRes = http.get(`${BASE_URL}/projects`, {
    headers: { 'Authorization': `Bearer ${token}` },
  });

  check(projectsRes, {
    'projects retrieved': (r) => r.status === 200,
  });

  sleep(1);
}
```

執行測試：
```bash
k6 run load-test.js
```

---

## 疑難排解 (Troubleshooting)

### 常見問題

#### 1. 401 Unauthorized
- 檢查 token 是否正確
- 檢查 token 是否過期
- 確認 Authorization header 格式正確

#### 2. 403 Forbidden
- 檢查使用者角色權限
- 確認使用者有權限執行該操作

#### 3. 404 Not Found
- 檢查 API 端點 URL 是否正確
- 確認資源 ID 存在

#### 4. 409 Conflict
- 檢查資源狀態
- 確認操作是否被允許

### 除錯技巧

#### 啟用詳細輸出
```bash
# curl 詳細模式
curl -v -X GET http://localhost:3000/api/projects

# HTTPie 詳細模式
http -v GET http://localhost:3000/api/projects
```

#### 檢查回應 Header
```bash
curl -I http://localhost:3000/api/projects
```

#### 使用 jq 格式化 JSON
```bash
curl http://localhost:3000/api/projects | jq '.'
```

---

## 參考資源 (Reference Resources)

- [API Requirements Documentation](./API-Requirements.md)
- [OpenAPI Specification](./openapi.yaml)
- [Postman Documentation](https://learning.postman.com/)
- [curl Documentation](https://curl.se/docs/)
- [HTTPie Documentation](https://httpie.io/docs)
- [Jest Documentation](https://jestjs.io/)
- [Newman Documentation](https://learning.postman.com/docs/running-collections/using-newman-cli/command-line-integration-with-newman/)

---

**最後更新 (Last Updated):** 2025-12-02  
**文件版本 (Document Version):** 1.0.0
