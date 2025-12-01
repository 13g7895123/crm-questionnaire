<!--
SYNC IMPACT REPORT
Version: 1.0.0 -> 1.1.0
Modified Principles:
- Added: V. Language & Localization
Added Sections:
- V. Language & Localization
Templates Status:
- .specify/templates/plan-template.md: ✅ Checked (Aligned)
- .specify/templates/spec-template.md: ✅ Checked (Aligned)
- .specify/templates/tasks-template.md: ✅ Checked (Aligned)
-->
# CRM Questionnaire Constitution

## Core Principles

### I. Code Quality & Standards
Code must be clean, readable, and maintainable. All code must adhere to the project's linter and formatter configurations. No code shall be merged without passing static analysis. Comments should explain "why", not "what". Functions should be small and focused (Single Responsibility Principle).

### II. Testing Strategy
Testing is mandatory and non-negotiable. All new features and bug fixes must include accompanying tests.
- **Unit Tests**: Required for all business logic and utility functions.
- **Integration Tests**: Required for API endpoints and critical user flows.
- **Coverage**: Aim for high coverage of critical paths; 100% coverage is not the goal, confidence is.
- **Test-First**: Tests should ideally be written before implementation (TDD) or immediately alongside it.

### III. User Experience Consistency
The user experience must be consistent, accessible, and intuitive.
- **Design System**: UI components must use the established design system/library to ensure visual consistency.
- **Accessibility**: All interfaces must meet WCAG 2.1 AA standards. Semantic HTML and ARIA labels are required where appropriate.
- **Feedback**: The system must provide clear feedback for all user actions (loading states, success messages, error handling).

### IV. Performance Requirements
Performance is a feature. The system must be responsive and efficient.
- **Load Time**: Initial page load should be under 2 seconds on 4G networks.
- **Response Time**: API responses should be under 200ms for standard operations.
- **Optimization**: Assets must be optimized (compressed images, minified bundles). Database queries must be indexed and optimized.

### V. Language & Localization
All specifications, plans, and user-facing documentation MUST be written in Traditional Chinese (zh-TW).
- **Documentation**: All project documentation, including specs, plans, and guides, must be in Traditional Chinese.
- **User Interface**: All user-facing text in the application must be in Traditional Chinese (zh-TW).
- **Code Comments**: Code comments may remain in English for technical clarity, but documentation intended for stakeholders must be zh-TW.

## Security & Compliance

All data handling must adhere to security best practices.
- **Data Protection**: User data must be encrypted in transit and at rest.
- **Authentication**: Secure authentication mechanisms (e.g., OAuth, JWT) must be used.
- **Validation**: All user input must be validated on both client and server sides to prevent injection attacks.

## Development Workflow

- **Branching**: Use feature branches (`feature/name`, `fix/issue`).
- **Commits**: Use conventional commits (e.g., `feat:`, `fix:`, `docs:`).
- **Reviews**: All PRs require at least one peer review.
- **CI/CD**: All tests and linters must pass in CI before merging.

## Governance

This Constitution supersedes all other project documentation. Amendments require a Pull Request with a "Constitution Check" and approval from project maintainers.

- **Compliance**: All PRs must verify compliance with these principles.
- **Versioning**: Semantic Versioning (MAJOR.MINOR.PATCH) applies to this document.
- **Runtime Guidance**: Refer to `README.md` for setup and specific command usage.

**Version**: 1.1.0 | **Ratified**: 2025-12-01 | **Last Amended**: 2025-12-01
