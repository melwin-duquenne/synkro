# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Synkro is a real-time collaborative platform (Vue 3 + Symfony + Node.js WebSocket) with rooms containing pluggable modules (chat, editor, whiteboard, calendar, kanban, files, video). Written in French context (UI, variable names may mix French/English).

## Architecture

Three independent services orchestrated via Docker Compose:

- **Backend** (`backend/`): Symfony 7.4 + API Platform 4.2, PHP 8.3. Stateless JWT API on port 8000. Entities in `src/Entity/`, API resources in `src/ApiResource/`, state processors/providers handle business logic. Security config in `config/packages/security.yaml`.
- **Frontend** (`frontend/`): Vue 3 + TypeScript + Vite + TailwindCSS 4 + DaisyUI 5. Pinia stores in `src/stores/`, composables in `src/composables/`. Path alias `@/*` maps to `src/*`.
- **WebSocket Server** (`server/`): Node.js with Yjs CRDT relay + WebRTC signaling. Single entry point: `unified-server.cjs` on port 3001.

**Database**: PostgreSQL 16 via Doctrine ORM. Migrations in `backend/migrations/`.

**Auth**: JWT (Lexik bundle) + Google OAuth2. Keys in `backend/config/jwt/`. Login at `/api/auth/login`, OAuth at `/auth/google`.

## Development Commands

### Start all services
```bash
docker-compose up -d
# backend: localhost:8000, frontend: localhost:5173, websocket: localhost:3001
# mailpit: localhost:8025, postgres: localhost:5432
```

### Backend
```bash
# Run all unit tests
docker-compose exec backend php vendor/bin/phpunit tests/Unit --testdox

# Run a single test file
docker-compose exec backend php vendor/bin/phpunit tests/Unit/Path/To/TestFile.php --testdox

# Run database migrations
docker-compose exec backend php bin/console doctrine:migrations:migrate

# Clear cache
docker-compose exec backend php bin/console cache:clear
```

### Frontend
```bash
cd frontend
npm install
npm run dev          # Dev server (hot reload)
npm run build        # Production build
npm run lint         # ESLint + Oxlint
npm run test         # Vitest unit tests
npm run test:ui      # Vitest UI dashboard
npm run test:coverage # Coverage report
```

### Generate JWT keys (first setup)
```bash
docker-compose exec backend php bin/console lexik:jwt:generate-keypair
```

## Key Patterns

- **API Platform state pattern**: POST/PATCH/DELETE go through state processors (`src/ApiResource/` classes define `processor`), GET through state providers. DTOs handle serialization.
- **Room modules**: Rooms have many-to-many with Module via `ModuleRoom`. Templates (Brainstorm, Redaction, Reunion) preconfigure module sets.
- **Permissions**: Two-level — `UserRoomPermission` (per-user) and `TeamRoomPermission` (per-team) with roles: editor, commenter, viewer, drawer.
- **Real-time sync**: Frontend `useYjs` composable connects to the WebSocket server. Yjs CRDTs handle conflict-free merges. Awareness protocol tracks presence/cursors.

## CI/CD

GitHub Actions (`.github/workflows/main.yml`): builds Docker images for all 3 services, pushes to Docker Hub, deploys to VPS via Ansible (`ansible/deploy.yml`). Production uses `docker-compose.prod.yml`.
