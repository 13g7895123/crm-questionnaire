# 組織管理 API

## 目錄
- [8.1 取得組織列表](#81-取得組織列表)
- [8.2 取得組織詳情](#82-取得組織詳情)
- [8.3 建立組織](#83-建立組織)
- [8.4 更新組織](#84-更新組織)
- [8.5 刪除組織](#85-刪除組織)
- [8.6 取得供應商列表](#86-取得供應商列表)

---

## 8.1 取得組織列表

**Endpoint**: `GET /api/v1/organizations`  
**權限**: 需要認證 (ADMIN)  
**用途**: 取得組織列表 (管理員用)

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Query Parameters

| 參數 | 類型 | 必填 | 說明 |
|------|------|------|------|
| page | integer | ✗ | 頁碼 (預設: 1) |
| limit | integer | ✗ | 每頁筆數 (預設: 20，最大: 100) |
| type | string | ✗ | 組織類型 (HOST, SUPPLIER) |
| search | string | ✗ | 搜尋關鍵字 (搜尋組織名稱) |

### Response (200 OK)

```json
{
  "success": true,
  "data": [
    {
      "id": "org_host123",
      "name": "製造商公司",
      "type": "HOST",
      "departmentCount": 5,
      "userCount": 25,
      "createdAt": "2025-01-01T00:00:00.000Z",
      "updatedAt": "2025-12-02T06:08:38.435Z"
    },
    {
      "id": "org_supplier456",
      "name": "供應商 A 公司",
      "type": "SUPPLIER",
      "departmentCount": 3,
      "userCount": 10,
      "createdAt": "2025-02-01T00:00:00.000Z",
      "updatedAt": "2025-11-15T10:00:00.000Z"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 25,
    "totalPages": 2
  }
}
```

**欄位說明:**

| 欄位 | 類型 | 說明 |
|------|------|------|
| departmentCount | integer | 組織內部門數量 |
| userCount | integer | 組織內使用者數量 |

---

## 8.2 取得組織詳情

**Endpoint**: `GET /api/v1/organizations/{organizationId}`  
**權限**: 需要認證 (ADMIN 或同組織成員)  
**用途**: 取得組織的詳細資訊

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Path Parameters

| 參數 | 類型 | 說明 |
|------|------|------|
| organizationId | string | 組織 ID |

### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "id": "org_host123",
    "name": "製造商公司",
    "type": "HOST",
    "departments": [
      {
        "id": "dept_qm123",
        "name": "品質管理部",
        "memberCount": 8
      },
      {
        "id": "dept_proc456",
        "name": "採購部",
        "memberCount": 5
      },
      {
        "id": "dept_exec789",
        "name": "高階主管部",
        "memberCount": 3
      }
    ],
    "userCount": 25,
    "projectCount": 150,
    "createdAt": "2025-01-01T00:00:00.000Z",
    "updatedAt": "2025-12-02T06:08:38.435Z"
  }
}
```

**欄位說明:**

| 欄位 | 類型 | 說明 |
|------|------|------|
| departments | array | 組織內的部門列表 |
| projectCount | integer | 相關專案數量 (HOST: 建立的專案, SUPPLIER: 被指派的專案) |

### Error Responses

**403 Forbidden - 非同組織成員**
```json
{
  "success": false,
  "error": {
    "code": "AUTH_INSUFFICIENT_PERMISSION",
    "message": "您無權檢視此組織資訊"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

**404 Not Found - 組織不存在**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "找不到指定的組織"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 8.3 建立組織

**Endpoint**: `POST /api/v1/organizations`  
**權限**: 需要認證 (ADMIN)  
**用途**: 管理員建立新組織

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Request Body

```json
{
  "name": "新供應商公司",
  "type": "SUPPLIER"
}
```

**欄位說明:**

| 欄位 | 類型 | 必填 | 說明 |
|------|------|------|------|
| name | string | ✓ | 組織名稱 (最長 200 字元) |
| type | string | ✓ | 組織類型 (HOST, SUPPLIER) |

### Response (201 Created)

```json
{
  "success": true,
  "data": {
    "id": "org_new789",
    "name": "新供應商公司",
    "type": "SUPPLIER",
    "createdAt": "2025-12-02T06:08:38.435Z",
    "updatedAt": "2025-12-02T06:08:38.435Z"
  }
}
```

### Error Responses

**403 Forbidden - 權限不足**
```json
{
  "success": false,
  "error": {
    "code": "AUTH_INSUFFICIENT_PERMISSION",
    "message": "您沒有權限執行此操作"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

**409 Conflict - 組織名稱重複**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_CONFLICT",
    "message": "組織名稱已存在"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 8.4 更新組織

**Endpoint**: `PUT /api/v1/organizations/{organizationId}`  
**權限**: 需要認證 (ADMIN)  
**用途**: 管理員更新組織資訊

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Path Parameters

| 參數 | 類型 | 說明 |
|------|------|------|
| organizationId | string | 組織 ID |

### Request Body

```json
{
  "name": "更新後的組織名稱"
}
```

**可更新欄位:**
- name

**不可更新欄位:**
- type (組織類型建立後不可修改)

### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "id": "org_supplier456",
    "name": "更新後的組織名稱",
    "type": "SUPPLIER",
    "updatedAt": "2025-12-02T06:08:38.435Z"
  }
}
```

### Error Responses

**404 Not Found - 組織不存在**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "找不到指定的組織"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 8.5 刪除組織

**Endpoint**: `DELETE /api/v1/organizations/{organizationId}`  
**權限**: 需要認證 (ADMIN)  
**用途**: 管理員刪除組織

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Path Parameters

| 參數 | 類型 | 說明 |
|------|------|------|
| organizationId | string | 組織 ID |

**注意事項:**
- 僅無使用者、無部門的組織可刪除
- 刪除組織前需先刪除或轉移所有相關資料

### Response (204 No Content)

成功刪除，無回應內容

### Error Responses

**404 Not Found - 組織不存在**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "找不到指定的組織"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

**409 Conflict - 組織有關聯資料**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_CONFLICT",
    "message": "此組織有使用者或部門，無法刪除",
    "details": {
      "userCount": 10,
      "departmentCount": 3
    }
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 8.6 取得供應商列表

**Endpoint**: `GET /api/v1/suppliers`  
**權限**: 需要認證 (HOST)  
**用途**: 製造商取得供應商列表 (用於建立專案時指派供應商)

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Query Parameters

| 參數 | 類型 | 必填 | 說明 |
|------|------|------|------|
| page | integer | ✗ | 頁碼 (預設: 1) |
| limit | integer | ✗ | 每頁筆數 (預設: 20，最大: 100) |
| search | string | ✗ | 搜尋關鍵字 (搜尋供應商名稱) |

**注意事項:**
- 此 API 為 `GET /api/v1/organizations?type=SUPPLIER` 的簡化版本
- 僅回傳供應商類型的組織

### Response (200 OK)

```json
{
  "success": true,
  "data": [
    {
      "id": "org_supplier456",
      "name": "供應商 A 公司",
      "type": "SUPPLIER",
      "createdAt": "2025-02-01T00:00:00.000Z"
    },
    {
      "id": "org_supplier789",
      "name": "供應商 B 公司",
      "type": "SUPPLIER",
      "createdAt": "2025-03-15T00:00:00.000Z"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 18,
    "totalPages": 1
  }
}
```

---

## 組織類型說明

### HOST (製造商/主辦方)
- 建立與管理 SAQ 與衝突資產專案
- 管理範本與題目
- 指派專案給供應商
- 設定審核流程
- 檢視所有專案與審核進度

### SUPPLIER (供應商)
- 被製造商指派專案
- 填寫問卷並提交
- 檢視被指派的專案列表
- 接收審核結果與退回通知
- 無法建立專案或範本

---

## 使用情境說明

### 情境 1: 管理員建立供應商組織
1. 管理員登入系統
2. 進入「組織管理」頁面
3. 點擊「新增組織」
4. 選擇類型: SUPPLIER
5. 輸入名稱: "新供應商公司"
6. 呼叫 `POST /api/v1/organizations`
7. 組織建立成功
8. 管理員可為該組織建立部門與使用者

### 情境 2: 製造商選擇供應商建立專案
1. 製造商進入「新增專案」頁面
2. 填寫專案基本資訊
3. 在「指派供應商」欄位
4. 呼叫 `GET /api/v1/suppliers` 取得供應商下拉選單
5. 選擇 "供應商 A 公司"
6. 完成專案建立
7. 該供應商的使用者可看到被指派的專案

### 情境 3: 檢視組織詳情
1. 管理員或組織成員進入「組織管理」
2. 點擊某個組織
3. 呼叫 `GET /api/v1/organizations/{organizationId}`
4. 顯示組織資訊:
   - 組織名稱與類型
   - 部門列表與成員數
   - 總使用者數
   - 相關專案數量

### 情境 4: 刪除組織前處理
1. 管理員嘗試刪除「供應商 A 公司」
2. 呼叫 `DELETE /api/v1/organizations/org_supplier456`
3. 後端檢查發現該組織有 10 名使用者與 3 個部門
4. 回傳 409 錯誤
5. 管理員需先:
   - 刪除或轉移 10 名使用者
   - 刪除 3 個部門
6. 完成後再次呼叫刪除 API
7. 組織成功刪除

---

## 資料關聯說明

### 組織 (Organization) 與部門 (Department)
- 一對多關係: 一個組織包含多個部門
- 刪除組織前需先刪除所有部門

### 組織 (Organization) 與使用者 (User)
- 一對多關係: 一個組織包含多個使用者
- 刪除組織前需先刪除或轉移所有使用者

### 組織 (Organization) 與專案 (Project)
- HOST 組織: 建立專案的主辦方
- SUPPLIER 組織: 被指派專案的供應商
- 專案的 `supplierId` 欄位指向 SUPPLIER 組織

---

## 注意事項

1. **組織類型不可變更**
   - 組織建立後，`type` 欄位無法修改
   - 若需變更類型，需建立新組織並轉移資料

2. **權限隔離**
   - HOST 組織與 SUPPLIER 組織完全獨立
   - 使用者只能屬於一個組織
   - 部門只能屬於一個組織

3. **刪除限制**
   - 有關聯資料的組織無法直接刪除
   - 建議實作「停用」功能而非直接刪除
   - 刪除前應顯示警告訊息與關聯資料統計

4. **供應商指派**
   - 建立專案時只能指派給 SUPPLIER 類型的組織
   - 一個專案只能指派給一個供應商
   - 供應商無法看到其他供應商的專案

5. **組織名稱**
   - 全系統唯一 (不分 HOST 或 SUPPLIER)
   - 建議包含公司全名或統一編號以確保唯一性
