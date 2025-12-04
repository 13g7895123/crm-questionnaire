# 審核流程 API

## 目錄
- [7.1 取得待審核專案列表](#71-取得待審核專案列表)
- [7.2 審核專案](#72-審核專案)
- [7.3 取得審核歷程](#73-取得審核歷程)
- [7.4 取得審核統計](#74-取得審核統計)

---

## 7.1 取得待審核專案列表

**Endpoint**: `GET /api/v1/reviews/pending`  
**權限**: 需要認證 (HOST)  
**用途**: 取得當前使用者所屬部門待審核的專案列表

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Query Parameters

| 參數 | 類型 | 必填 | 說明 |
|------|------|------|------|
| page | integer | ✗ | 頁碼 (預設: 1) |
| limit | integer | ✗ | 每頁筆數 (預設: 20，最大: 100) |
| type | string | ✗ | 專案類型 (SAQ, CONFLICT) |
| sortBy | string | ✗ | 排序欄位 (submittedAt, updatedAt) |
| order | string | ✗ | 排序方向 (asc, desc) |

**權限說明:**
- 系統會根據使用者的部門 (departmentId) 篩選待審核專案
- 僅顯示目前審核階段 (currentStage) 對應到使用者部門的專案
- 專案狀態必須為 `REVIEWING`

### Response (200 OK)

```json
{
  "success": true,
  "data": [
    {
      "id": 101,
      "projectId": 1,
      "name": "2025 SAQ 供應商評估",
      "year": 2025,
      "type": "SAQ",
      "supplierId": 2,
      "supplier": {
        "id": 2,
        "name": "供應商 A 公司"
      },
      "status": "REVIEWING",
      "currentStage": 1,
      "totalStages": 2,
      "currentStageDepartment": {
        "id": 1,
        "name": "品質管理部"
      },
      "submittedAt": "2025-11-01T10:00:00.000Z",
      "updatedAt": "2025-11-15T14:30:00.000Z"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 8,
    "totalPages": 1
  }
}
```

**欄位說明:**

| 欄位 | 類型 | 說明 |
|------|------|------|
| id | integer | ProjectSupplier ID (用於審核操作) |
| projectId | integer | 專案 ID |
| currentStage | integer | 目前審核階段編號 (1-5) |
| totalStages | integer | 總審核階段數 |
| currentStageDepartment | object | 目前審核階段負責部門 |
| submittedAt | string | 供應商提交時間 |

---

## 7.2 審核專案

**Endpoint**: `POST /api/v1/project-suppliers/{projectSupplierId}/review`  
**權限**: 需要認證 (HOST，且所屬部門為目前審核階段負責部門)  
**用途**: 審核者核准或退回專案

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Path Parameters

| 參數 | 類型 | 說明 |
|------|------|------|
| projectSupplierId | integer | 專案供應商 ID |

### Request Body

**核准專案:**
```json
{
  "action": "APPROVE",
  "comment": "資料完整，符合規範，核准通過。"
}
```

**退回專案:**
```json
{
  "action": "RETURN",
  "comment": "請補充以下資料：\n1. ISO 9001 認證文件\n2. 品質管理流程說明需更詳細"
}
```

**欄位說明:**

| 欄位 | 類型 | 必填 | 說明 |
|------|------|------|------|
| action | string | ✓ | 審核動作 (APPROVE, RETURN) |
| comment | string | ✓ | 審核意見 (最長 1000 字元，退回時必填) |

**權限驗證:**
- 使用者的部門必須為目前審核階段負責部門
- 專案狀態必須為 `REVIEWING`
- 專案的 `currentStage` 必須對應到使用者部門

### Response (200 OK)

**核准 - 進入下一階段:**
```json
{
  "success": true,
  "data": {
    "projectSupplierId": 101,
    "action": "APPROVE",
    "previousStage": 1,
    "currentStage": 2,
    "status": "REVIEWING",
    "message": "已核准，專案進入第 2 階段審核",
    "reviewLog": {
      "id": 1,
      "reviewerId": 10,
      "reviewerName": "審核者姓名",
      "stage": 1,
      "action": "APPROVE",
      "comment": "資料完整，符合規範，核准通過。",
      "timestamp": "2025-12-02T06:08:38.435Z"
    }
  }
}
```

**核准 - 完成所有審核:**
```json
{
  "success": true,
  "data": {
    "projectSupplierId": 101,
    "action": "APPROVE",
    "previousStage": 2,
    "currentStage": 2,
    "status": "APPROVED",
    "message": "已核准，專案審核完成",
    "reviewLog": {
      "id": 2,
      "reviewerId": 11,
      "reviewerName": "審核者姓名",
      "stage": 2,
      "action": "APPROVE",
      "comment": "最終審核通過，專案核准。",
      "timestamp": "2025-12-02T06:08:38.435Z"
    }
  }
}
```

**退回:**
```json
{
  "success": true,
  "data": {
    "projectSupplierId": 101,
    "action": "RETURN",
    "previousStage": 1,
    "currentStage": 0,
    "status": "RETURNED",
    "message": "已退回給供應商重新填寫",
    "reviewLog": {
      "id": 3,
      "reviewerId": 10,
      "reviewerName": "審核者姓名",
      "stage": 1,
      "action": "RETURN",
      "comment": "請補充以下資料：\n1. ISO 9001 認證文件\n2. 品質管理流程說明需更詳細",
      "timestamp": "2025-12-02T06:08:38.435Z"
    }
  }
}
```

**狀態變更邏輯:**

| 審核動作 | 條件 | 狀態變更 | currentStage 變更 |
|----------|------|----------|-------------------|
| APPROVE | 有下一階段 | REVIEWING | +1 (進入下一階段) |
| APPROVE | 無下一階段 | APPROVED | 維持最後階段編號 |
| RETURN | 任何階段 | RETURNED | 設為 0 |

### Error Responses

**403 Forbidden - 非當前審核階段負責部門**
```json
{
  "success": false,
  "error": {
    "code": "AUTH_INSUFFICIENT_PERMISSION",
    "message": "您的部門不負責目前審核階段"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

**409 Conflict - 專案狀態不允許審核**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_CONFLICT",
    "message": "專案目前不在審核狀態"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

**422 Unprocessable Entity - 退回時未填寫意見**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "退回專案時必須填寫審核意見"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 7.3 取得審核歷程

**Endpoint**: `GET /api/v1/project-suppliers/{projectSupplierId}/reviews`  
**權限**: 需要認證 (HOST 或被指派的 SUPPLIER)  
**用途**: 取得專案的完整審核歷程

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Path Parameters

| 參數 | 類型 | 說明 |
|------|------|------|
| projectSupplierId | integer | 專案供應商 ID |

### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "projectSupplierId": 101,
    "projectId": 1,
    "projectName": "2025 SAQ 供應商評估",
    "currentStatus": "APPROVED",
    "reviews": [
      {
        "id": 1,
        "reviewerId": 10,
        "reviewerName": "張三",
        "reviewerDepartment": {
          "id": 1,
          "name": "品質管理部"
        },
        "stage": 1,
        "action": "APPROVE",
        "comment": "資料完整，符合規範，核准通過。",
        "timestamp": "2025-11-10T10:30:00.000Z"
      },
      {
        "id": 2,
        "reviewerId": 11,
        "reviewerName": "李四",
        "reviewerDepartment": {
          "id": 2,
          "name": "採購部"
        },
        "stage": 2,
        "action": "APPROVE",
        "comment": "最終審核通過，專案核准。",
        "timestamp": "2025-11-15T14:00:00.000Z"
      }
    ]
  }
}
```

**包含退回紀錄的範例:**
```json
{
  "success": true,
  "data": {
    "projectId": "proj_def456",
    "projectName": "2025 衝突資產調查",
    "currentStatus": "REVIEWING",
    "reviews": [
      {
        "id": "log_003",
        "reviewerId": "usr_reviewer789",
        "reviewerName": "王五",
        "reviewerDepartment": {
          "id": "dept_qm123",
          "name": "品質管理部"
        },
        "stage": 1,
        "action": "RETURN",
        "comment": "請補充 ISO 9001 認證文件。",
        "timestamp": "2025-11-05T09:00:00.000Z"
      },
      {
        "id": "log_004",
        "reviewerId": "usr_reviewer123",
        "reviewerName": "張三",
        "reviewerDepartment": {
          "id": "dept_qm123",
          "name": "品質管理部"
        },
        "stage": 1,
        "action": "APPROVE",
        "comment": "已補充文件，核准通過。",
        "timestamp": "2025-11-20T11:30:00.000Z"
      }
    ]
  }
}
```

**注意事項:**
- 審核歷程按時間順序排列 (由舊到新)
- 包含所有審核動作 (APPROVE 與 RETURN)
- 供應商可檢視審核歷程，了解退回原因

---

## 7.4 取得審核統計

**Endpoint**: `GET /api/v1/reviews/stats`  
**權限**: 需要認證 (HOST)  
**用途**: 取得當前使用者所屬部門的審核統計 (儀表板顯示用)

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Query Parameters

| 參數 | 類型 | 必填 | 說明 |
|------|------|------|------|
| type | string | ✗ | 專案類型 (SAQ, CONFLICT) |
| startDate | string | ✗ | 開始日期 (ISO 8601) |
| endDate | string | ✗ | 結束日期 (ISO 8601) |

### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "departmentId": "dept_qm123",
    "departmentName": "品質管理部",
    "pending": 8,
    "approvedThisMonth": 25,
    "returnedThisMonth": 3,
    "averageReviewTime": 2.5,
    "byType": {
      "SAQ": {
        "pending": 5,
        "approved": 18,
        "returned": 2
      },
      "CONFLICT": {
        "pending": 3,
        "approved": 7,
        "returned": 1
      }
    }
  }
}
```

**欄位說明:**

| 欄位 | 類型 | 說明 |
|------|------|------|
| pending | integer | 待審核專案數量 |
| approvedThisMonth | integer | 本月核准數量 |
| returnedThisMonth | integer | 本月退回數量 |
| averageReviewTime | number | 平均審核時間 (天數) |

---

## 審核流程圖

```
供應商提交專案
    ↓
status: SUBMITTED → REVIEWING
currentStage: 0 → 1
    ↓
第一階段審核 (品質管理部)
    ├─ APPROVE (有下一階段)
    │   ↓
    │   currentStage: 1 → 2
    │   status: REVIEWING (維持)
    │   ↓
    │   第二階段審核 (採購部)
    │   ├─ APPROVE (無下一階段)
    │   │   ↓
    │   │   status: REVIEWING → APPROVED
    │   │   currentStage: 2 (維持)
    │   │   審核完成 ✓
    │   │
    │   └─ RETURN
    │       ↓
    │       status: REVIEWING → RETURNED
    │       currentStage: 2 → 0
    │       退回給供應商重新填寫
    │
    └─ RETURN
        ↓
        status: REVIEWING → RETURNED
        currentStage: 1 → 0
        退回給供應商重新填寫
```

---

## 使用情境說明

### 情境 1: 審核者檢視待審核專案
1. 審核者登入系統
2. 進入「待審核專案」頁面
3. 呼叫 `GET /api/v1/reviews/pending` 取得待審核列表
4. 僅顯示審核者所屬部門負責的專案
5. 點擊專案進入審核頁面 (使用 `projectSupplierId`)

### 情境 2: 審核者核准專案 (有下一階段)
1. 審核者進入專案審核頁面
2. 檢視供應商填寫的答案
3. 呼叫 `GET /api/v1/project-suppliers/{projectSupplierId}/reviews` 查看歷史審核紀錄
4. 確認資料完整無誤
5. 輸入審核意見: "資料完整，符合規範，核准通過。"
6. 點擊「核准」按鈕
7. 呼叫 `POST /api/v1/project-suppliers/{projectSupplierId}/review` (action: APPROVE)
8. 系統回應: "已核准，專案進入第 2 階段審核"
9. 專案狀態維持 `REVIEWING`，`currentStage` 從 1 變更為 2
10. 第二階段負責部門的審核者收到通知

### 情境 3: 審核者核准專案 (最後階段)
1. 第二階段審核者進入專案
2. 檢視資料並確認無誤
3. 輸入審核意見: "最終審核通過，專案核准。"
4. 點擊「核准」按鈕
5. 呼叫 `POST /api/v1/project-suppliers/{projectSupplierId}/review` (action: APPROVE)
6. 系統回應: "已核准，專案審核完成"
7. 專案狀態從 `REVIEWING` 變更為 `APPROVED`
8. 供應商與製造商收到通知

### 情境 4: 審核者退回專案
1. 第一階段審核者進入專案
2. 檢視答案後發現資料不完整
3. 輸入退回原因: "請補充以下資料：\n1. ISO 9001 認證文件\n2. 品質管理流程說明需更詳細"
4. 點擊「退回」按鈕
5. 呼叫 `POST /api/v1/project-suppliers/{projectSupplierId}/review` (action: RETURN)
6. 系統回應: "已退回給供應商重新填寫"
7. 專案狀態從 `REVIEWING` 變更為 `RETURNED`
8. `currentStage` 重置為 0
9. 供應商收到通知，可重新填寫專案

### 情境 5: 供應商查看退回原因
1. 供應商收到專案被退回的通知
2. 登入系統並進入專案
3. 呼叫 `GET /api/v1/project-suppliers/{projectSupplierId}/reviews` 查看審核歷程
4. 看到退回原因與審核者意見
5. 根據意見修改答案
6. 重新提交專案
7. 專案重新進入第一階段審核

---

## 審核權限控制

### 部門匹配驗證
```
當前使用者部門: dept_qm123 (品質管理部)
專案審核流程:
  - 第 1 階段: dept_qm123 (品質管理部)
  - 第 2 階段: dept_proc456 (採購部)

專案目前狀態:
  - currentStage: 1
  - 負責部門: dept_qm123

驗證結果: ✓ 允許審核 (使用者部門 = 目前階段負責部門)
```

### 特定審核者 (可選功能)
```json
{
  "reviewConfig": [
    {
      "stageOrder": 1,
      "departmentId": "dept_qm123",
      "approverId": "usr_specific123"
    }
  ]
}
```

若設定了 `approverId`，則該階段僅指定的審核者可執行審核，同部門其他成員無權限。

---

## 通知機制建議

### 供應商通知
- 專案被退回: Email + 系統通知
- 專案審核完成 (APPROVED): Email + 系統通知

### 審核者通知
- 有新的專案待審核: Email + 系統通知
- 專案進入本部門審核階段: Email + 系統通知

### 製造商通知
- 專案審核完成 (APPROVED): Email + 系統通知
- 專案被退回: 系統通知 (可選)
