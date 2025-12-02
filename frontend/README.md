# CRM Questionnaire System - Frontend

A Nuxt 3 application for managing questionnaires, projects, and reviews in a CRM system.

## Documentation

- **[API Requirements](./API_REQUIREMENTS.md)** - Complete API specification for all backend endpoints

## Features

- **Member Center**: User authentication and profile management
- **SAQ Management**: Create and manage SAQ (Supplier Assessment Questionnaire) projects and templates
- **Conflict Management**: Handle conflict-related questionnaires
- **Multi-stage Review**: Configurable review workflow with department-based approval stages
- **Multi-language Support**: i18n support for multiple languages
- **Template Version Control**: Manage questionnaire templates with version history

## Project Structure

```
app/
├── components/     # Vue components
├── composables/    # Reusable composables (API clients)
├── layouts/        # Application layouts
├── pages/          # Application pages (file-based routing)
├── stores/         # Pinia state management stores
├── types/          # TypeScript type definitions
├── utils/          # Utility functions
├── middleware/     # Route middleware
└── locales/        # i18n translation files
```

Look at the [Nuxt documentation](https://nuxt.com/docs/getting-started/introduction) to learn more.

## Setup

Make sure to install dependencies:

```bash
# npm
npm install

# pnpm
pnpm install

# yarn
yarn install

# bun
bun install
```

## Development Server

Start the development server on `http://localhost:3000`:

```bash
# npm
npm run dev

# pnpm
pnpm dev

# yarn
yarn dev

# bun
bun run dev
```

## Production

Build the application for production:

```bash
# npm
npm run build

# pnpm
pnpm build

# yarn
yarn build

# bun
bun run build
```

Locally preview production build:

```bash
# npm
npm run preview

# pnpm
pnpm preview

# yarn
yarn preview

# bun
bun run preview
```

Check out the [deployment documentation](https://nuxt.com/docs/getting-started/deployment) for more information.

## Testing

Run unit tests with Vitest:

```bash
# npm
npm run test

# pnpm
pnpm test

# yarn
yarn test

# bun
bun run test
```

Run tests with UI:

```bash
# npm
npm run test:ui

# pnpm
pnpm test:ui

# yarn
yarn test:ui

# bun
bun run test:ui
```

## Environment Variables

Create a `.env` file in the root directory:

```env
API_BASE_URL=http://localhost:3000
```

## Tech Stack

- **Framework**: Nuxt 3
- **UI**: Nuxt UI
- **State Management**: Pinia
- **i18n**: @nuxtjs/i18n
- **Testing**: Vitest + Vue Test Utils
- **TypeScript**: Full TypeScript support
