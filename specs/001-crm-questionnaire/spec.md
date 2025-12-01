# Feature Specification: CRM Questionnaire System

**Feature Branch**: `001-crm-questionnaire`
**Created**: 2025-12-01
**Status**: Draft
**Input**: User description: "問卷系統CRM - 會員中心、SAQ與衝突資產..."

## User Scenarios & Testing

### User Story 1 - Member Center Navigation (Priority: P1)

As a logged-in user, I want to see a central hub with available applications so that I can easily access different modules like SAQ and Conflict Minerals.

**Why this priority**: This is the entry point for the entire system.

**Independent Test**: Can be fully tested by logging in and verifying the dashboard displays the correct application cards and navigation works.

**Acceptance Scenarios**:

1. **Given** a logged-in user, **When** they access the homepage, **Then** they see the Member Center with a navigation bar.
2. **Given** the Member Center, **When** viewing the application list, **Then** "Account Management", "SAQ", and "Conflict Minerals" are displayed with their respective icons and labels.
3. **Given** the interface, **When** switching languages (e.g., English/Chinese), **Then** all application names and UI text update accordingly.

---

### User Story 2 - SAQ Project Management (Priority: P1)

As a user, I want to manage SAQ projects so that I can organize questionnaires by year and template.

**Why this priority**: Core functionality for the SAQ module.

**Independent Test**: Can be tested by creating, editing, and deleting projects in the SAQ module.

**Acceptance Scenarios**:

1. **Given** the SAQ module homepage, **When** viewed, **Then** a list of existing SAQ projects is displayed.
2. **Given** the project list, **When** clicking "Create Project", **Then** a form appears asking for Name, Year, and Template selection.
3. **Given** an existing project, **When** editing it, **Then** the user can update the Name, Year, and Template.
4. **Given** a project, **When** deleting it, **Then** it is removed from the list.

---

### User Story 3 - Conflict Minerals Project Management (Priority: P1)

As a user, I want to manage Conflict Minerals projects so that I can track these specific assessments.

**Why this priority**: Core functionality for the Conflict Minerals module.

**Independent Test**: Can be tested by creating, editing, and deleting projects in the Conflict Minerals module.

**Acceptance Scenarios**:

1. **Given** the Conflict Minerals module homepage, **When** viewed, **Then** a list of existing projects is displayed.
2. **Given** the project list, **When** clicking "Create Project", **Then** the user can enter project details (Name).
3. **Given** an existing project, **When** editing it, **Then** the user can update its details.
4. **Given** a project, **When** deleting it, **Then** it is removed from the list.

### Edge Cases

- What happens when a user tries to delete a project that is in use?
- How does the system handle missing templates when creating an SAQ project?
- What happens if the user has no permissions for a specific application?

## Requirements

### Functional Requirements

- **FR-001**: System MUST provide a Member Center dashboard accessible after login.
- **FR-002**: Member Center MUST display application cards for "Account Management", "SAQ", and "Conflict Minerals".
- **FR-003**: Each application card MUST include an icon/image and a text label.
- **FR-004**: System MUST support multi-language switching (at least English and Traditional Chinese) for all UI elements.
- **FR-005**: SAQ Module MUST display a list of projects on its homepage.
- **FR-006**: SAQ Module MUST allow creating a project with fields: Name, Year, and Template Selection.
- **FR-007**: SAQ Module MUST allow editing and deleting existing projects.
- **FR-008**: System MUST support SAQ Templates containing a Version and a list of Questions.
- **FR-009**: Conflict Minerals Module MUST display a list of projects on its homepage.
- **FR-010**: Conflict Minerals Module MUST allow creating, editing, and deleting projects.
- **FR-011**: Account Management Module MUST be present as a placeholder/basic view [NEEDS CLARIFICATION: Specific features for Account Management not defined - assume basic profile view?].

### Key Entities

- **Application**: Represents a module (e.g., SAQ) with Name and Icon.
- **SAQ Project**: Contains Name, Year, and reference to a Template.
- **SAQ Template**: Contains Version and a collection of Questions.
- **Conflict Minerals Project**: Contains Name and basic metadata.

## Success Criteria

### Measurable Outcomes

- **SC-001**: Users can navigate to any of the 3 modules from the Member Center within 1 click.
- **SC-002**: Users can successfully create a new SAQ project with a selected template in under 1 minute.
- **SC-003**: UI text reflects the selected language immediately upon switching.
- **SC-004**: Project lists load and display correct data for SAQ and Conflict Minerals.
