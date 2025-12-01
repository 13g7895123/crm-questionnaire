# Feature Specification: 問卷系統CRM (CRM Questionnaire System)

**Feature Branch**: `003-crm-questionnaire`
**Created**: 2025-12-01
**Status**: Draft
**Input**: User description: "問卷系統CRM - 會員中心、SAQ與衝突資產"

## User Scenarios & Testing *(mandatory)*

<!--
  IMPORTANT: User stories should be PRIORITIZED as user journeys ordered by importance.
  Each user story/journey must be INDEPENDENTLY TESTABLE - meaning if you implement just ONE of them,
  you should still have a viable MVP (Minimum Viable Product) that delivers value.
-->

### User Story 1 - 會員中心與帳戶管理 (Member Center & Account Management) (Priority: P1)

使用者登入後進入會員中心，可以看到應用程式列表（帳戶管理、SAQ、衝突資產）。使用者可以進入帳戶管理修改個人資料與密碼，並在會員中心切換語系。

**Why this priority**: 這是系統的入口與基礎功能，確保使用者能導航至各個模組並管理個人資訊。

**Independent Test**: 測試登入後能否看到 Navbar 與 App 列表，能否切換語系，能否成功修改密碼與資料。

**Acceptance Scenarios**:

1. **Given** 使用者已登入, **When** 進入會員中心首頁, **Then** 看到 Navbar 與三個應用程式區塊 (帳戶管理、SAQ、衝突資產)，每個區塊包含圖片與文字。
2. **Given** 在會員中心首頁, **When** 點擊「帳戶管理」, **Then** 進入帳戶管理頁面，顯示個人資料編輯表單。
3. **Given** 在帳戶管理頁面, **When** 修改密碼並點擊儲存, **Then** 系統提示更新成功，且下次登入必須使用新密碼。
4. **Given** 在會員中心首頁, **When** 切換語系選單, **Then** 介面所有文字變更為選定的語言。

---

### User Story 2 - SAQ 專案與範本管理 (SAQ Project & Template Management) (Priority: P2)

使用者可以管理 SAQ 專案與範本。建立專案時需選擇年份與範本。範本管理支援版本控制與題目編輯。

**Why this priority**: SAQ 是核心業務功能，需完整的問卷管理機制。

**Independent Test**: 測試建立範本、新增題目、發布版本。測試建立專案並選擇該範本。

**Acceptance Scenarios**:

1. **Given** 在 SAQ 首頁, **When** 點擊「新增專案」, **Then** 彈出對話框或進入頁面，要求輸入名稱、年份並選擇範本。
2. **Given** 已有建立好的 SAQ 範本, **When** 建立專案並選擇該範本, **Then** 專案建立成功，且該專案關聯至選定的範本版本。
3. **Given** 在 SAQ 範本管理介面, **When** 新增範本並編輯題目後儲存, **Then** 產生一個新的範本版本。
4. **Given** 在 SAQ 專案列表, **When** 點擊刪除某專案, **Then** 該專案從列表中移除。

---

### User Story 3 - 衝突資產管理 (Conflict Minerals Management) (Priority: P3)

使用者可以管理衝突資產專案與範本，功能機制與 SAQ 相同，但資料獨立。

**Why this priority**: 這是另一個核心業務模組，複用 SAQ 的架構但業務範疇不同。

**Independent Test**: 測試衝突資產的專案建立與範本管理，確保與 SAQ 獨立但功能一致。

**Acceptance Scenarios**:

1. **Given** 在衝突資產首頁, **When** 點擊「新增專案」, **Then** 流程與 SAQ 相同，需輸入名稱、年份並選擇衝突資產範本。
2. **Given** 在衝突資產範本管理, **When** 建立新範本, **Then** 該範本僅在衝突資產模組中可見，不會出現在 SAQ 模組中。
3. **Given** 在衝突資產專案列表, **When** 點擊編輯專案, **Then** 可以修改專案名稱與年份。

---

### Edge Cases

- **EC-001**: 當使用者刪除一個已被專案使用的範本時，系統應阻止刪除或僅標記為停用，避免破壞現有專案資料。
- **EC-002**: 當切換語系時，若某個應用程式名稱或題目沒有對應的翻譯，系統應顯示預設語言（如英文或繁體中文）。
- **EC-003**: 若使用者在編輯專案時網路斷線，系統應有適當的錯誤提示，避免資料不一致。

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: 系統必須提供會員中心首頁，包含 Navbar 與應用程式列表（帳戶管理、SAQ、衝突資產）。
- **FR-002**: 應用程式列表中的每個項目必須包含圖片與文字說明。
- **FR-003**: 系統必須支援多語系切換（至少包含繁體中文）。
- **FR-004**: 帳戶管理必須允許使用者編輯個人資料（如姓名、Email）與修改密碼。
- **FR-005**: SAQ 與衝突資產模組必須各自擁有獨立的專案列表首頁。
- **FR-006**: 專案管理必須支援新增、編輯、刪除功能。
- **FR-007**: 新增/編輯專案時，必須包含名稱、年份、範本選擇欄位。
- **FR-008**: 系統必須提供完整的範本管理介面，包含建立、編輯範本基本資訊。
- **FR-009**: 範本必須支援版本控制（Versioning），每次重大修改應產生新版本。
- **FR-010**: 範本必須包含題目管理功能（支援新增、編輯、刪除題目）。
- **FR-011**: SAQ 與衝突資產的資料（專案、範本）必須邏輯隔離，互不影響。

### Key Entities *(include if feature involves data)*

- **User (使用者)**: 系統使用者，包含帳號、密碼、個人資料。
- **Project (專案)**: 屬於特定模組 (SAQ/Conflict) 的執行實體，包含名稱、年份、關聯的範本版本。
- **Template (範本)**: 問卷的結構藍圖，包含多個版本。
- **TemplateVersion (範本版本)**: 範本的特定快照，包含一組題目定義。
- **Question (題目)**: 問卷中的具體問題，包含題型與內容。

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 使用者能在 30 秒內完成一個新專案的建立（包含輸入資訊與選擇範本）。
- **SC-002**: 範本管理者能成功發布新版本的範本，且系統能正確記錄版本號。
- **SC-003**: 系統支援至少 2 種語系切換，且切換後介面文字即時更新。
- **SC-004**: 使用者能成功修改密碼，並驗證舊密碼失效、新密碼生效。
