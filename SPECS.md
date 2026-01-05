# SPECS.md — Synkro Technical Specifications

> Document de référence pour le développement de Synkro
> Dernière mise à jour : 2026-01-05

---

## 1. Vision du projet

**Synkro** est une plateforme collaborative temps réel destinée aux entreprises. Elle combine :
- Édition de texte collaborative (TipTap + Yjs)
- Whiteboard partagé
- Chat en temps réel
- Visioconférence (WebRTC)
- Gestion fine des permissions par équipe

**Objectif** : Offrir un espace de travail unifié où les équipes peuvent collaborer en temps réel dans des "rooms" configurables.

---

## 2. Stack technique

### Frontend
| Technologie | Version | Usage |
|-------------|---------|-------|
| Vue.js | 3.5+ | Framework principal |
| TypeScript | 5.9+ | Typage statique |
| Vite | 7.x | Build tool |
| TailwindCSS | 3.x | Styles utilitaires |
| DaisyUI | 4.x | Composants UI |
| TipTap | 2.x | Éditeur rich text |
| Yjs | 13.x | CRDT pour collaboration |
| y-websocket | 1.5+ | Client WebSocket Yjs |
| Vue Router | 4.x | Routing SPA |
| Pinia | 2.x | State management |

### Backend
| Technologie | Version | Usage |
|-------------|---------|-------|
| PHP | 8.2+ | Langage |
| Symfony | 7.4 | Framework |
| API Platform | 4.2 | API REST/GraphQL |
| Doctrine ORM | 3.x | ORM |
| Nelmio CORS | 2.x | Gestion CORS |
| LexikJWT | 3.x | Authentification JWT |

### Realtime Server
| Technologie | Version | Usage |
|-------------|---------|-------|
| Node.js | 20+ | Runtime |
| ws | 8.x | WebSocket server |
| Yjs | 13.x | CRDT sync |
| y-websocket | 1.5+ | Yjs WebSocket provider |

### Infrastructure
| Technologie | Version | Usage |
|-------------|---------|-------|
| Docker | 24+ | Conteneurisation |
| Docker Compose | 2.x | Orchestration locale |
| PostgreSQL | 16 | Base de données |
| Uptime Kuma | - | Monitoring (optionnel) |

---

## 3. Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         FRONTEND (Vue 3)                        │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────────┐ │
│  │  Auth    │  │  Rooms   │  │  Editor  │  │  Whiteboard/Chat │ │
│  │  Pages   │  │  List    │  │  TipTap  │  │  WebRTC          │ │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────────┬─────────┘ │
│       │             │             │                  │           │
│       └─────────────┴──────┬──────┴──────────────────┘           │
│                            │                                     │
└────────────────────────────┼─────────────────────────────────────┘
                             │
              ┌──────────────┴──────────────┐
              │                             │
              ▼                             ▼
┌─────────────────────────┐    ┌─────────────────────────┐
│   BACKEND (Symfony)     │    │  REALTIME (Node.js)     │
│   Port: 8000            │    │  Port: 1234             │
│                         │    │                         │
│  • API REST/GraphQL     │    │  • Yjs WebSocket        │
│  • Auth JWT             │    │  • Awareness (presence) │
│  • Gestion Rooms        │    │  • Sync documents       │
│  • Permissions          │    │  • Sync whiteboard      │
│  • Snapshots            │    │                         │
└───────────┬─────────────┘    └─────────────────────────┘
            │
            ▼
┌─────────────────────────┐
│   PostgreSQL            │
│   Port: 5432            │
│                         │
│  • Users, Teams         │
│  • Rooms, Permissions   │
│  • Messages, Documents  │
│  • Snapshots Yjs        │
└─────────────────────────┘
```

---

## 4. Schéma de base de données

### 4.1 Entités principales

#### Entreprise
```
entreprise
├── id (PK, auto)
├── name (varchar 255)
├── domain (varchar 255, unique)
└── created_at (datetime)
```

#### Team
```
team
├── id (PK, auto)
├── name (varchar 255)
└── entreprise_id (FK → entreprise.id)
```

#### User
```
user
├── id (PK, auto)
├── email (varchar 255, unique)
├── password (varchar 255, hashed)
├── display_name (varchar 255)
├── role (enum: admin, user)
├── entreprise_id (FK → entreprise.id, nullable)
├── team_id (FK → team.id, nullable)
└── created_at (datetime)
```

#### Room
```
room
├── id (PK, auto)
├── name (varchar 255)
├── is_temporary (boolean, default false)
├── visibility (enum: enterprise, private)
├── creator_id (FK → user.id)
├── entreprise_id (FK → entreprise.id)
├── template_id (FK → room_template.id, nullable)
└── created_at (datetime)
```

**Règles d'accès aux rooms :**
| Visibilité | Qui peut accéder |
|------------|------------------|
| `enterprise` | Tous les membres de l'entreprise |
| `private` | Uniquement les teams/users autorisés via `team_room_permission` ou `user_room_permission` |

> **Important** : Seuls les membres de l'entreprise peuvent voir/accéder aux rooms de cette entreprise. Une room `private` restreint encore plus l'accès à certains membres de l'entreprise.

#### Module
```
module
├── id (PK, auto)
├── name (varchar 100)
├── code (varchar 50, unique) // chat, video, editor, whiteboard, files, tasks, calendar
└── description (text)
```

#### ModuleRoom (pivot)
```
module_room
├── id (PK, auto)
├── room_id (FK → room.id)
├── module_id (FK → module.id)
└── config_json (jsonb, nullable)
```

#### TeamRoomPermission
```
team_room_permission
├── id (PK, auto)
├── room_id (FK → room.id)
├── team_id (FK → team.id)
└── role (enum: viewer, editor, owner)
```

#### UserRoomPermission (pour rooms privées)
```
user_room_permission
├── id (PK, auto)
├── room_id (FK → room.id)
├── user_id (FK → user.id)
└── role (enum: viewer, editor, owner)
```

> **Note** : Pour une room `private`, on peut autoriser soit des teams entières (via `team_room_permission`), soit des users individuels (via `user_room_permission`), ou les deux.

#### Message
```
message
├── id (PK, auto)
├── room_id (FK → room.id)
├── user_id (FK → user.id)
├── content (text)
└── created_at (datetime)
```

#### Document
```
document
├── id (PK, auto)
├── room_id (FK → room.id, unique)
├── content_markdown (text)
├── yjs_state (bytea, nullable) // snapshot Yjs
└── updated_at (datetime)
```

#### FileResource
```
file_resource
├── id (PK, auto)
├── room_id (FK → room.id)
├── user_id (FK → user.id)
├── file_name (varchar 255)
├── file_path (varchar 500)
├── mime_type (varchar 100)
├── size (integer)
└── created_at (datetime)
```

#### Task
```
task
├── id (PK, auto)
├── room_id (FK → room.id)
├── title (varchar 255)
├── description (text, nullable)
├── status (varchar 50) // todo, in_progress, done
├── position (integer)
├── assigned_to_user_id (FK → user.id, nullable)
└── created_at (datetime)
```

#### CalendarEvent (style Google Agenda)
```
calendar_event
├── id (PK, auto)
├── room_id (FK → room.id, nullable) // nullable si événement personnel
├── user_id (FK → user.id)
├── entreprise_id (FK → entreprise.id)
├── title (varchar 255)
├── description (text, nullable)
├── event_type (enum: meeting, absence, blocked, reminder, other)
├── start_date (datetime)
├── end_date (datetime)
├── is_all_day (boolean, default false)
├── recurrence (varchar 100, nullable) // RRULE format (daily, weekly, monthly...)
├── color (varchar 7, nullable) // hex color
├── location (varchar 255, nullable)
├── is_private (boolean, default false)
└── created_at (datetime)
```

**Types d'événements :**
| Type | Description |
|------|-------------|
| `meeting` | Réunion / Bloc de réunion |
| `absence` | Absence (congé, maladie, etc.) |
| `blocked` | Heures bloquées (indisponible) |
| `reminder` | Rappel / Pense-bête |
| `other` | Autre événement |

#### RoomTemplate
```
room_template
├── id (PK, auto)
├── name (varchar 100)
└── description (text)
```

#### TemplateModule (pivot)
```
template_module
├── id (PK, auto)
├── template_id (FK → room_template.id)
└── module_id (FK → module.id)
```

### 4.2 Relations

```
entreprise 1──N team
entreprise 1──N user
entreprise 1──N room
entreprise 1──N calendar_event
team 1──N user
user 1──N room (creator)
user 1──N message
user 1──N task (assigned)
user 1──N calendar_event
room N──N module (via module_room)
room N──N team (via team_room_permission)
room N──N user (via user_room_permission) // pour rooms privées
room 1──N message
room 1──1 document
room 1──N file_resource
room 1──N task
room 1──N calendar_event
room N──1 room_template
room_template N──N module (via template_module)
```

---

## 5. Modules disponibles

| Code | Nom | Description |
|------|-----|-------------|
| `editor` | Éditeur | TipTap collaboratif avec Yjs |
| `whiteboard` | Tableau blanc | Dessin collaboratif temps réel |
| `chat` | Chat | Messages texte en direct |
| `video` | Visio | Audio/vidéo WebRTC |
| `files` | Fichiers | Upload et partage de fichiers |
| `tasks` | Tâches | Kanban simple |
| `calendar` | Calendrier | Agenda complet (réunions, absences, heures bloquées, récurrence) |

---

## 6. Templates de rooms (personnalisables)

Les templates sont **entièrement personnalisables** par l'utilisateur. Lors de la création d'une room, l'utilisateur :
1. Choisit un nom pour sa room
2. Sélectionne les modules qu'il souhaite activer (checkbox)
3. Peut sauvegarder sa configuration comme template réutilisable

### Templates suggérés par défaut (exemples)
| Template | Modules inclus |
|----------|----------------|
| **Brainstorm** | whiteboard, chat |
| **Rédaction** | editor, chat |
| **Réunion** | video, chat, calendar |
| **Projet** | editor, tasks, files, chat |

> **Note** : L'utilisateur peut créer ses propres templates ou partir de zéro en sélectionnant manuellement les modules.

---

## 7. API Endpoints (prévu)

### Auth
```
POST   /api/auth/register     # Inscription
POST   /api/auth/login        # Connexion (retourne JWT)
POST   /api/auth/refresh      # Refresh token
GET    /api/auth/me           # User actuel
```

### Users
```
GET    /api/users             # Liste (admin)
GET    /api/users/{id}        # Détail
PATCH  /api/users/{id}        # Modifier
DELETE /api/users/{id}        # Supprimer
```

### Entreprises
```
GET    /api/entreprises       # Liste
POST   /api/entreprises       # Créer
GET    /api/entreprises/{id}  # Détail
PATCH  /api/entreprises/{id}  # Modifier
DELETE /api/entreprises/{id}  # Supprimer
```

### Teams
```
GET    /api/teams             # Liste
POST   /api/teams             # Créer
GET    /api/teams/{id}        # Détail
PATCH  /api/teams/{id}        # Modifier
DELETE /api/teams/{id}        # Supprimer
```

### Rooms
```
GET    /api/rooms             # Liste (filtrée par entreprise)
POST   /api/rooms             # Créer
GET    /api/rooms/{id}        # Détail + modules
PATCH  /api/rooms/{id}        # Modifier
DELETE /api/rooms/{id}        # Supprimer
POST   /api/rooms/{id}/join   # Rejoindre (avec password si privée)
```

### Messages
```
GET    /api/rooms/{id}/messages    # Liste messages d'une room
POST   /api/rooms/{id}/messages    # Envoyer message
```

### Documents
```
GET    /api/rooms/{id}/document    # Récupérer document
PUT    /api/rooms/{id}/document    # Sauvegarder snapshot
```

---

## 8. WebSocket Events

### Connexion Yjs
```
ws://localhost:1234/{roomId}
```

### Awareness (présence)
```javascript
// Structure awareness
{
  name: string,        // displayName
  color: string,       // couleur unique
  cursor: { x, y },    // position curseur (whiteboard)
  selection: {...}     // sélection (editor)
}
```

---

## 9. Variables d'environnement

### .env (racine)
```bash
# PostgreSQL
POSTGRES_USER=synkro
POSTGRES_PASSWORD=synkro_password
POSTGRES_DB=synkro

# Ports
PORT_BACKEND=8000:80
PORT_WEBSOCKET=1234:1234
PORT_POSTGRES=5432:5432
PORT_POSTGRES_2=5432

# Node
NODE_ENV=development
PORT=1234
```

### Backend (.env.local)
```bash
APP_ENV=dev
APP_SECRET=your_secret_here
DATABASE_URL="postgresql://synkro:synkro_password@postgres-synkro:5432/synkro?serverVersion=16"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

### Frontend (.env)
```bash
VITE_API_URL=http://localhost:8000/api
VITE_WS_URL=ws://localhost:1234
```

---

## 10. Structure des dossiers (cible)

```
synkro/
├── backend/
│   ├── config/
│   ├── migrations/
│   ├── src/
│   │   ├── Controller/
│   │   ├── Entity/
│   │   │   ├── User.php
│   │   │   ├── Entreprise.php
│   │   │   ├── Team.php
│   │   │   ├── Room.php
│   │   │   ├── Module.php
│   │   │   ├── ModuleRoom.php
│   │   │   ├── TeamRoomPermission.php
│   │   │   ├── UserRoomPermission.php
│   │   │   ├── Message.php
│   │   │   ├── Document.php
│   │   │   ├── FileResource.php
│   │   │   ├── Task.php
│   │   │   ├── CalendarEvent.php
│   │   │   ├── RoomTemplate.php
│   │   │   └── TemplateModule.php
│   │   ├── Repository/
│   │   ├── Service/
│   │   └── Security/
│   ├── Dockerfile
│   └── composer.json
│
├── frontend/
│   ├── src/
│   │   ├── assets/
│   │   ├── components/
│   │   │   ├── common/
│   │   │   ├── editor/
│   │   │   ├── whiteboard/
│   │   │   ├── chat/
│   │   │   └── video/
│   │   ├── composables/
│   │   ├── layouts/
│   │   ├── pages/
│   │   │   ├── auth/
│   │   │   ├── dashboard/
│   │   │   └── room/
│   │   ├── router/
│   │   ├── stores/
│   │   ├── types/
│   │   ├── utils/
│   │   ├── App.vue
│   │   └── main.ts
│   ├── Dockerfile
│   └── package.json
│
├── server/
│   ├── src/
│   │   ├── index.js
│   │   └── utils/
│   ├── Dockerfile
│   └── package.json
│
├── docker-compose.yml
├── .env
├── .gitignore
├── README.md
├── BDD.md
└── SPECS.md (ce fichier)
```

---

## 11. Roadmap de développement

### Phase 1 : Fondations
- [ ] Configurer toutes les entités Doctrine avec relations
- [ ] Générer les migrations
- [ ] Implémenter l'authentification JWT
- [ ] Configurer API Platform (CRUD auto)
- [ ] Installer dépendances frontend (Tailwind, DaisyUI, Router, Pinia)
- [ ] Créer layout de base et routing

### Phase 2 : Auth & Users
- [ ] Pages login/register frontend
- [ ] Intégration JWT côté frontend
- [ ] Store Pinia pour auth
- [ ] Page profil utilisateur

### Phase 3 : Rooms & Modules
- [ ] CRUD rooms backend
- [ ] Liste rooms frontend
- [ ] Création room avec sélection modules
- [ ] Système de permissions par team

### Phase 4 : Collaboration temps réel
- [ ] Serveur WebSocket Yjs opérationnel
- [ ] Éditeur TipTap + Yjs
- [ ] Awareness (présence utilisateurs)
- [ ] Chat temps réel

### Phase 5 : Fonctionnalités avancées
- [ ] Whiteboard collaboratif
- [ ] Upload fichiers
- [ ] Kanban (tasks)
- [ ] Calendrier partagé

### Phase 6 : WebRTC & Polish
- [ ] Intégration visio (LiveKit ou simple WebRTC)
- [ ] Notifications toast
- [ ] Export PDF/ODT
- [ ] Tests et optimisations

---

## 12. Conventions de code

### Backend (PHP/Symfony)
- PSR-12 pour le style
- Nommage entités en PascalCase
- Propriétés en camelCase
- Tables SQL en snake_case
- Utiliser les attributs PHP 8 pour Doctrine/API Platform

### Frontend (Vue/TypeScript)
- Composition API avec `<script setup lang="ts">`
- Composants en PascalCase
- Fichiers en kebab-case ou PascalCase
- Types dans `src/types/`
- Composables préfixés par `use` (ex: `useAuth.ts`)

### Git
- Branches : `feature/xxx`, `fix/xxx`, `refactor/xxx`
- Commits conventionnels : `feat:`, `fix:`, `docs:`, `refactor:`
- PR obligatoire pour merge sur `main`

---

## 13. Commandes utiles

```bash
# Démarrer l'environnement
docker-compose up -d

# Backend - Créer une entité
docker exec -it symfony_app php bin/console make:entity

# Backend - Générer migration
docker exec -it symfony_app php bin/console make:migration

# Backend - Exécuter migrations
docker exec -it symfony_app php bin/console doctrine:migrations:migrate

# Frontend - Dev server
cd frontend && npm run dev

# Frontend - Build
cd frontend && npm run build
```

---

> Ce document sera mis à jour au fur et à mesure de l'avancement du projet.
