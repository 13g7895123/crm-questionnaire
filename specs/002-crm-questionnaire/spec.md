# Feature Specification: CRM Questionnaire System

**Feature Branch**: `002-crm-questionnaire`
**Created**: 2025-12-01
**Status**: Draft
**Input**: User description: "問卷系統CRM: 會員中心(帳戶管理、SAQ、衝突資產), SAQ(專案列表、問卷範本), 衝突資產(專案列表)"

## User Scenarios & Testing

### User Story 1 - Member Center Access (Priority: P1)

As a logged-in user, I want to access a central hub where I can see and navigate to all available applications (Account Management, SAQ, Conflict Minerals).

**Why this priority**: This is the entry point for the entire system. Without it, users cannot navigate to specific tools.

**Independent Test**: Can be tested by logging in and verifying the dashboard displays the correct application icons and text in the selected language.

**Acceptance Scenarios**:

1. **Given** a logged-in user, **When** they access the homepage, **Then** they see a navbar and a list of applications (Account Management, SAQ, Conflict Minerals) with icons and text.
2. **Given** the Member Center, **When** the user changes the language setting, **Then** the application names and interface text update to the selected language.
3. **Given** the Member Center, **When** the user clicks "SAQ", **Then** they are redirected to the SAQ application homepage.

---

### User Story 2 - SAQ Project Management (Priority: P1)

As a user, I want to manage SAQ projects (create, edit, delete) so that I can organize and track different questionnaire initiatives.

**Why this priority**: Core functionality for the SAQ module. Users need to create projects to start working on questionnaires.

**Independent Test**: Can be tested by creating a new project, verifying it appears in the list, editing it, and deleting it.

**Acceptance Scenarios**:

1. **Given** the SAQ homepage, **When** the user views the page, **Then** they see a list of existing SAQ projects.
2. **Given** the SAQ project list, **When** the user clicks "Add Project", **Then** they can enter a Name, Year, and select a Template.
3. **Given** an existing SAQ project, **When** the user edits it, **Then** they can update the Name, Year, and Template.
4. **Given** an existing SAQ project, **When** the user deletes it, **Then** it is removed from the list.

---

### User Story 3 - Conflict Minerals Project Management (Priority: P2)

As a user, I want to manage Conflict Minerals projects (create, edit, delete) to track compliance initiatives.

**Why this priority**: Essential for the Conflict Minerals module, similar to SAQ.

**Independent Test**: Can be tested by creating, editing, and deleting Conflict Minerals projects.

**Acceptance Scenarios**:

1. **Given** the Conflict Minerals homepage, **When** the user views the page, **Then** they see a list of existing projects.
2. **Given** the project list, **When** the user adds a project, **Then** they can enter project details (Name, Year).
3. **Given** an existing project, **When** the user deletes it, **Then** it is removed from the list.

### Edge Cases

- **Duplicate Project Names**: System should allow/disallow duplicate project names within the same year? (Assumption: Allowed, but maybe warn).
- **Empty Fields**: Attempting to create a project without a Name or Year should show a validation error.
- **Deletion Confirmation**: Deleting a project should require confirmation to prevent accidental data loss.

## Requirements

### Functional Requirements

#### Member Center
- **FR-001**: System MUST provide a Member Center homepage accessible after login.
- **FR-002**: Member Center MUST display a navigation bar.
- **FR-003**: Member Center MUST display entry points for "Account Management", "SAQ", and "Conflict Minerals".
- **FR-004**: Each application entry point MUST display an icon and a descriptive label.
- **FR-005**: System MUST support multiple languages for all UI text in the Member Center.

#### SAQ Application
- **FR-006**: SAQ Homepage MUST display a list of SAQ projects.
- **FR-007**: Users MUST be able to create a new SAQ project with the following fields:
  - Name (Text)
  - Year (Number/Date)
  - Template (Selection from available templates)
- **FR-008**: Users MUST be able to edit existing SAQ project details (Name, Year, Template).
- **FR-009**: Users MUST be able to delete SAQ projects.
- **FR-010**: System MUST support SAQ Templates containing Versions and Questions. [NEEDS CLARIFICATION: Do we need a UI to create/manage these templates in this version, or just select pre-existing ones?]

#### Conflict Minerals Application
- **FR-011**: Conflict Minerals Homepage MUST display a list of projects.
- **FR-012**: Users MUST be able to create, edit, and delete Conflict Minerals projects.
- **FR-013**: Conflict Minerals projects MUST include basic metadata (Name, Year). [NEEDS CLARIFICATION: Does Conflict Minerals follow the same "Questionnaire/Template" structure as SAQ, or is it just a simple project list for now?]

#### Account Management
- **FR-014**: System MUST provide an "Account Management" section. [NEEDS CLARIFICATION: What specific features are required for Account Management? (e.g., Profile edit, Password change, or just a placeholder?)]

### Key Entities

- **User**: Represents a system user with access to the Member Center.
- **SAQ Project**: A specific instance of an SAQ initiative (Name, Year, linked Template).
- **SAQ Template**: A blueprint for questionnaires (contains Versions, Questions).
- **Conflict Minerals Project**: A specific instance of a Conflict Minerals initiative.

### Assumptions

- **Templates**: Pre-existing SAQ templates are available in the system or loaded via a separate admin process not covered in this feature.
- **Authentication**: Users are already authenticated; this feature covers the post-login experience.
- **Conflict Minerals**: Currently treated as a project tracking list without detailed questionnaire functionality in this iteration.

## Success Criteria

### Measurable Outcomes

- **SC-001**: Users can navigate from Member Center to any specific app (SAQ, CM) in under 2 clicks.
- **SC-002**: Users can create a new SAQ project with a selected template in under 1 minute.
- **SC-003**: System supports switching languages instantly (without page reload or under 1 second).
- **SC-004**: Project lists load in under 2 seconds for a user with 50+ projects.

