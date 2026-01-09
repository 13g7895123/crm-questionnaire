# 責任礦產後端開發進度

> **開始時間**: 2026-01-09 11:34  
> **當前狀態**: 🟡 進行中

## ✅ 已完成項目

### 1. 資料庫 Migrations (2/2) - 已執行
- ✅ 建立並執行 `rm_template_sets` 表
- ✅ 建立並執行 `rm_projects` 與 `rm_supplier_assignments` 表

### 2. Models (3/3)
- ✅ `TemplateSetModel.php` - JSON 處理與工具方法
- ✅ `RmProjectModel.php` - 關聯查詢與進度統計
- ✅ `RmSupplierAssignmentModel.php` - 批量指派邏輯

### 3. Controllers (3/3)
- ✅ `TemplateSets.php` - 5 個 API 端點 (CRUD)
- ✅ `RmProjects.php` - 6 個 API 端點 (CRUD + Progress)
- ✅ `RmSupplierAssignments.php` - 6 個 API 端點 (Assign + Batch + Notify)

### 4. Routes (1/1)
- ✅ 已在 `Config/Routes.php` 設定 `api/v1/rm` 分組路由

需要在 `app/Config/Routes.php` 新增:
```php
$routes->group('api/v1/rm', function ($routes) {
    // 範本組
    $routes->resource('template-sets');
    
    // 專案
    $routes->resource('projects');
    $routes->post('projects/(:num)/suppliers/import', 'RmProjects::importSuppliers/$1');
    
    // 供應商指派
    $routes->put('projects/(:num)/suppliers/(:num)/templates', 'RmSupplierAssignments::assignTemplate/$1/$2');
    $routes->post('projects/(:num)/suppliers/batch-assign-templates', 'RmSupplierAssignments::batchAssign/$1');
    // ... 更多路由
});
```

---

### 本週完成 (已提前達成)
- [x] 執行 Migration 與資料庫設定
- [x] 建立並測試所有 RM 相關 Models
- [x] 建立並測試所有 RM 相關 Controllers
- [x] 前端 `useProjects.ts`, `useTemplateSets.ts`, `useResponsibleMinerals.ts` 對接
- [x] 專案建立頁面 (`ProjectForm.vue`) 支援 RM 流程
- [x] 供應商管理頁面 (`suppliers.vue`) 支援指派與通知
- [x] 進度追蹤頁面 (`progress.vue`) 支援即時統計

---

## 🎯 下一步驟

**立即執行**:
```bash
# 1. 執行 Migration
cd backend
php spark migrate

# 2. 檢查資料表是否建立成功
php spark db:table rm_template_sets
php spark db:table rm_projects
php spark db:table rm_supplier_assignments
```

**繼續開發**:
- 建立剩餘的 Models
- 建立 Controllers
- 設定 Routes
- API 測試

---

## 📝 備註

### 資料庫設計重點

1. **範本組與專案關聯**
   - 一個專案對應一個範本組
   - 專案刪除時相關資料會 CASCADE 刪除

2. **JSON 欄位**
   - `amrt_minerals`: 儲存選擇的礦產陣列
   - `review_config`: 儲存審核流程設定
   - Model 會自動處理 JSON 編碼/解碼

3. **供應商指派**
   - project_id + supplier_id 為 UNIQUE KEY
   - 同一專案同一供應商只能指派一次

### API 設計考量

1. **RESTful 設計**
   - 使用 CodeIgniter 4 的 ResourceController
   - 標準 HTTP 方法 (GET/POST/PUT/DELETE)

2. **回應格式統一**
   ```json
   {
     "success": true,
     "data": {...},
     "message": "操作成功"
   }
   ```

3. **錯誤處理**
   - 使用 HTTP 狀態碼
   - 提供詳細錯誤訊息

---

*此文件會持續更新*
