# WebSocket Authentication Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Empêcher toute connexion non authentifiée/non autorisée au serveur WebSocket en déléguant la décision d'accès au backend Symfony.

**Architecture :** Le frontend joint son JWT existant à chaque connexion WebSocket (query param `?token=`). Le serveur Node intercepte le handshake `upgrade`, extrait le token + l'ID de room, et appelle un nouvel endpoint backend `GET /api/ws/authorize/{roomId}` (authentifié par le firewall `^/api`, autorisé par `RoomAccessChecker`). Selon la réponse, Node accepte la connexion ou la ferme avec un code dédié. Un cache mémoire (TTL 30 s) évite de marteler le backend sur les reconnexions.

**Tech Stack :** Symfony 7.4 (PHP 8.3, API Platform, Lexik JWT), Node.js 20 (`ws`, `y-websocket`, `fetch` natif, `node:test`), Vue 3 + TS (`y-websocket` WebsocketProvider, Pinia).

**Référence spec :** `docs/superpowers/specs/2026-05-30-websocket-auth-design.md`

---

## File Structure

**Backend**
- Create: `backend/src/Controller/WebSocketAuthController.php` — endpoint d'autorisation WS (1 responsabilité).
- Create: `backend/tests/Unit/Controller/WebSocketAuthControllerTest.php` — tests unitaires du controller.

**Serveur WebSocket**
- Create: `server/auth.cjs` — fonctions pures d'authentification (parsing URL, mapping statut→code, délégation backend). Isolé pour être testable sans réseau.
- Create: `server/test/auth.test.cjs` — tests `node:test` de `auth.cjs`.
- Modify: `server/unified-server.cjs` — câblage de l'auth dans le handshake `upgrade`.
- Modify: `server/package.json` — script `test`.

**Frontend**
- Modify: `frontend/src/composables/useYjs.ts` — joindre le token + gérer les codes de fermeture.
- Modify: `frontend/src/composables/useWebRTC.ts` — joindre le token + gérer la fermeture.
- Modify: `frontend/src/components/chat/ChatModule.vue` — joindre le token.
- Modify: `frontend/src/components/editor/CollaborativeEditor.vue` — joindre le token (2 occurrences).
- Modify: `frontend/src/components/editor/EditorModule.vue` — joindre le token.
- Modify: `frontend/src/components/whiteboard/WhiteboardModule.vue` — joindre le token.

**Infra**
- Modify: `docker-compose.yml` — `BACKEND_INTERNAL_URL` (service `websocket-synkro`).
- Modify: `docker-compose.deploiement.yml` — `BACKEND_INTERNAL_URL` (service `websocket-synkro`).
- Modify (optionnel/bonus): `ansible/roles/vhosts/templates/synkro.conf.j2` — masquage du token dans les access logs.

---

## Task 1 : Backend — endpoint `GET /api/ws/authorize/{roomId}`

**Files:**
- Create: `backend/src/Controller/WebSocketAuthController.php`
- Test: `backend/tests/Unit/Controller/WebSocketAuthControllerTest.php`

Le controller reçoit l'utilisateur courant via `#[CurrentUser]` (arg de méthode) et ses dépendances via le constructeur (pour être testable unitairement sans conteneur). Il retourne `JsonResponse` directement (pas de `$this->json()` qui nécessiterait le conteneur).

- [ ] **Step 1 : Écrire le test qui échoue**

Create `backend/tests/Unit/Controller/WebSocketAuthControllerTest.php` :

```php
<?php

namespace App\Tests\Unit\Controller;

use App\Controller\WebSocketAuthController;
use App\Entity\Room;
use App\Entity\User;
use App\Repository\RoomRepository;
use App\Security\RoomAccessChecker;
use PHPUnit\Framework\TestCase;

class WebSocketAuthControllerTest extends TestCase
{
    private function makeController(RoomRepository $repo, RoomAccessChecker $checker): WebSocketAuthController
    {
        return new WebSocketAuthController($repo, $checker);
    }

    public function testReturns401WhenNoUser(): void
    {
        $controller = $this->makeController(
            $this->createMock(RoomRepository::class),
            $this->createMock(RoomAccessChecker::class),
        );

        $response = $controller->authorize(42, null);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testReturns404WhenRoomMissing(): void
    {
        $repo = $this->createMock(RoomRepository::class);
        $repo->method('find')->willReturn(null);

        $controller = $this->makeController($repo, $this->createMock(RoomAccessChecker::class));

        $response = $controller->authorize(42, new User());

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testReturns403WhenAccessDenied(): void
    {
        $repo = $this->createMock(RoomRepository::class);
        $repo->method('find')->willReturn(new Room());

        $checker = $this->createMock(RoomAccessChecker::class);
        $checker->method('canAccess')->willReturn(false);

        $controller = $this->makeController($repo, $checker);

        $response = $controller->authorize(42, new User());

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testReturns200WithUserInfoWhenAllowed(): void
    {
        $repo = $this->createMock(RoomRepository::class);
        $repo->method('find')->willReturn(new Room());

        $checker = $this->createMock(RoomAccessChecker::class);
        $checker->method('canAccess')->willReturn(true);

        $user = new User();
        $user->setDisplayName('Alice');

        $controller = $this->makeController($repo, $checker);

        $response = $controller->authorize(42, $user);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Alice', $data['displayName']);
        $this->assertArrayHasKey('userId', $data);
    }
}
```

- [ ] **Step 2 : Lancer le test pour vérifier qu'il échoue**

Run : `docker-compose exec backend php vendor/bin/phpunit tests/Unit/Controller --testdox`
Expected : FAIL — `Class "App\Controller\WebSocketAuthController" not found`.

- [ ] **Step 3 : Écrire le controller**

Create `backend/src/Controller/WebSocketAuthController.php` :

```php
<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\RoomRepository;
use App\Security\RoomAccessChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class WebSocketAuthController extends AbstractController
{
    public function __construct(
        private RoomRepository $roomRepository,
        private RoomAccessChecker $accessChecker,
    ) {}

    /**
     * Appelé par le serveur WebSocket (Node) pour savoir si l'utilisateur
     * porteur du JWT peut accéder à la room donnée.
     */
    #[Route(
        '/api/ws/authorize/{roomId}',
        name: 'ws_authorize',
        methods: ['GET'],
        requirements: ['roomId' => '\d+'],
    )]
    public function authorize(int $roomId, #[CurrentUser] ?User $user): JsonResponse
    {
        // Le firewall ^/api renvoie déjà 401 si le token est absent/invalide ;
        // cette garde couvre le cas défensif + permet le test unitaire.
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $room = $this->roomRepository->find($roomId);
        if ($room === null) {
            return new JsonResponse(['error' => 'Room not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            return new JsonResponse(['error' => 'Forbidden'], JsonResponse::HTTP_FORBIDDEN);
        }

        return new JsonResponse([
            'userId' => $user->getId(),
            'displayName' => $user->getDisplayName(),
        ]);
    }
}
```

- [ ] **Step 4 : Lancer le test pour vérifier qu'il passe**

Run : `docker-compose exec backend php vendor/bin/phpunit tests/Unit/Controller --testdox`
Expected : PASS — 4 tests verts.

- [ ] **Step 5 : Vérifier que la route est protégée (pas dans PUBLIC_ACCESS)**

`backend/config/packages/security.yaml` ne doit **pas** lister `^/api/ws`. La règle générique `- { path: ^/api, roles: IS_AUTHENTICATED }` (déjà présente) s'applique. Aucune modification attendue — juste relire le fichier pour confirmer qu'aucune règle publique ne capture `^/api/ws`.

- [ ] **Step 6 : Commit**

```bash
git add backend/src/Controller/WebSocketAuthController.php backend/tests/Unit/Controller/WebSocketAuthControllerTest.php
git commit -m "feat(security): endpoint /api/ws/authorize pour l'auth WebSocket"
```

---

## Task 2 : Serveur WS — module d'auth pur + tests

**Files:**
- Create: `server/auth.cjs`
- Test: `server/test/auth.test.cjs`
- Modify: `server/package.json`

On isole la logique dans des fonctions pures, sans dépendance au serveur HTTP, pour les tester avec `node:test` (intégré à Node 20, zéro dépendance).

- [ ] **Step 1 : Écrire les tests qui échouent**

Create `server/test/auth.test.cjs` :

```js
'use strict';

const test = require('node:test');
const assert = require('node:assert');
const { parseConnection, closeCodeForStatus, authorize } = require('../auth.cjs');

test('parseConnection extrait docName, token et roomId numérique', () => {
  const r = parseConnection('/room-42-editor?token=abc');
  assert.strictEqual(r.docName, 'room-42-editor');
  assert.strictEqual(r.token, 'abc');
  assert.strictEqual(r.roomId, '42');
});

test('parseConnection renvoie roomId null pour un chemin non-room', () => {
  const r = parseConnection('/health?token=abc');
  assert.strictEqual(r.roomId, null);
});

test('parseConnection gère un token absent', () => {
  const r = parseConnection('/room-7-chat');
  assert.strictEqual(r.token, null);
  assert.strictEqual(r.roomId, '7');
});

test('closeCodeForStatus mappe les statuts HTTP', () => {
  assert.strictEqual(closeCodeForStatus(200), null);
  assert.strictEqual(closeCodeForStatus(401), 4401);
  assert.strictEqual(closeCodeForStatus(403), 4403);
  assert.strictEqual(closeCodeForStatus(404), 4403);
  assert.strictEqual(closeCodeForStatus(500), 1011);
});

test('authorize ferme en 4401 si token ou roomId manquant', async () => {
  const d = await authorize({ reqUrl: '/room-1-editor', backendUrl: 'http://x' });
  assert.deepStrictEqual(d, { ok: false, closeCode: 4401, user: null });
});

test('authorize renvoie ok + user sur 200 backend', async () => {
  const fetchImpl = async () => ({ status: 200, json: async () => ({ userId: 5, displayName: 'Bob' }) });
  const d = await authorize({ reqUrl: '/room-1-editor?token=t', backendUrl: 'http://x', fetchImpl });
  assert.strictEqual(d.ok, true);
  assert.strictEqual(d.closeCode, null);
  assert.deepStrictEqual(d.user, { userId: 5, displayName: 'Bob' });
});

test('authorize mappe 403 vers closeCode 4403', async () => {
  const fetchImpl = async () => ({ status: 403, json: async () => ({}) });
  const d = await authorize({ reqUrl: '/room-1-editor?token=t', backendUrl: 'http://x', fetchImpl });
  assert.deepStrictEqual(d, { ok: false, closeCode: 4403, user: null });
});

test('authorize mappe un 5xx backend vers 1011 sans mise en cache', async () => {
  const cache = new Map();
  const fetchImpl = async () => ({ status: 500, json: async () => ({}) });
  const d = await authorize({ reqUrl: '/room-1-editor?token=t', backendUrl: 'http://x', fetchImpl, cache });
  assert.strictEqual(d.closeCode, 1011);
  assert.strictEqual(cache.size, 0);
});

test('authorize renvoie 1011 si le backend est injoignable', async () => {
  const fetchImpl = async () => { throw new Error('ECONNREFUSED'); };
  const d = await authorize({ reqUrl: '/room-1-editor?token=t', backendUrl: 'http://x', fetchImpl });
  assert.strictEqual(d.closeCode, 1011);
});

test('authorize met en cache une décision positive pendant le TTL', async () => {
  const cache = new Map();
  let calls = 0;
  const fetchImpl = async () => { calls++; return { status: 200, json: async () => ({ userId: 1, displayName: 'A' }) }; };
  let clock = 1000;
  const now = () => clock;
  const opts = { reqUrl: '/room-9-editor?token=tok', backendUrl: 'http://x', fetchImpl, cache, now, ttlMs: 30000 };

  await authorize(opts);
  await authorize(opts); // dans le TTL → servi par le cache
  assert.strictEqual(calls, 1);

  clock = 1000 + 30001; // au-delà du TTL
  await authorize(opts);
  assert.strictEqual(calls, 2);
});
```

- [ ] **Step 2 : Lancer les tests pour vérifier qu'ils échouent**

Run : `cd server && node --test`
Expected : FAIL — `Cannot find module '../auth.cjs'`.

- [ ] **Step 3 : Écrire le module d'auth**

Create `server/auth.cjs` :

```js
'use strict';

/**
 * Découpe l'URL d'un handshake WebSocket en ses composants.
 * @param {string} reqUrl ex. "/room-42-editor?token=abc"
 * @returns {{docName: string, token: string|null, roomId: string|null}}
 */
function parseConnection(reqUrl) {
  const url = new URL(reqUrl || '/', 'http://localhost');
  const docName = url.pathname.slice(1);
  const token = url.searchParams.get('token');
  const match = docName.match(/room-(\d+)-/);
  const roomId = match ? match[1] : null;
  return { docName, token, roomId };
}

/**
 * Traduit un statut HTTP backend en code de fermeture WebSocket.
 * @returns {number|null} null = autorisé (ne pas fermer)
 */
function closeCodeForStatus(status) {
  if (status === 200) return null;
  if (status === 401) return 4401;          // non authentifié
  if (status === 403 || status === 404) return 4403; // interdit / room inconnue
  return 1011;                               // 5xx ou inattendu → erreur transitoire
}

/**
 * Décide si une connexion WS est autorisée en déléguant au backend Symfony.
 * @returns {Promise<{ok: boolean, closeCode: number|null, user: object|null}>}
 */
async function authorize({
  reqUrl,
  backendUrl,
  fetchImpl = fetch,
  cache = null,
  now = Date.now,
  ttlMs = 30000,
}) {
  const { token, roomId } = parseConnection(reqUrl);
  if (!token || !roomId) {
    return { ok: false, closeCode: 4401, user: null };
  }

  const key = `${token}|${roomId}`;
  if (cache) {
    const hit = cache.get(key);
    if (hit && hit.expires > now()) {
      return hit.decision;
    }
  }

  let res;
  try {
    res = await fetchImpl(`${backendUrl}/api/ws/authorize/${roomId}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
  } catch (e) {
    // Backend injoignable : erreur transitoire, on ne met PAS en cache.
    return { ok: false, closeCode: 1011, user: null };
  }

  const closeCode = closeCodeForStatus(res.status);
  let decision;
  if (closeCode === null) {
    let user = null;
    try { user = await res.json(); } catch (e) { user = null; }
    decision = { ok: true, closeCode: null, user };
  } else {
    decision = { ok: false, closeCode, user: null };
  }

  // On ne met en cache que les décisions définitives (pas les 5xx → 1011).
  if (cache && closeCode !== 1011) {
    cache.set(key, { decision, expires: now() + ttlMs });
  }

  return decision;
}

module.exports = { parseConnection, closeCodeForStatus, authorize };
```

- [ ] **Step 4 : Ajouter le script de test**

Modify `server/package.json` : ajouter dans la section `"scripts"` la ligne :

```json
    "test": "node --test"
```

(Si une autre entrée `scripts` existe déjà, ajouter la virgule nécessaire. Si `"scripts"` n'existe pas, la créer : `"scripts": { "test": "node --test" }`.)

- [ ] **Step 5 : Lancer les tests pour vérifier qu'ils passent**

Run : `cd server && npm test`
Expected : PASS — tous les tests (`# pass` = nombre de tests, `# fail 0`).

- [ ] **Step 6 : Commit**

```bash
git add server/auth.cjs server/test/auth.test.cjs server/package.json
git commit -m "feat(security): module d'auth WebSocket (parsing + délégation backend) + tests"
```

---

## Task 3 : Serveur WS — câblage de l'auth dans le handshake

**Files:**
- Modify: `server/unified-server.cjs`

On passe le serveur `ws` en mode `noServer` et on authentifie dans `server.on('upgrade')`. Le handshake est complété (pour pouvoir renvoyer un code de fermeture précis au client), puis soit on ferme avec le code, soit on émet `connection` (ce qui déclenche la logique Yjs/WebRTC existante).

- [ ] **Step 1 : Importer le module d'auth et la config (haut du fichier)**

Modify `server/unified-server.cjs` — après la ligne `const { setupWSConnection } = require('y-websocket/bin/utils');` (ligne 3), ajouter :

```js
const { authorize } = require('./auth.cjs');

const BACKEND_INTERNAL_URL = process.env.BACKEND_INTERNAL_URL || 'http://backend-synkro:8000';
const authCache = new Map(); // clé "token|roomId" -> { decision, expires }
```

- [ ] **Step 2 : Passer le serveur ws en mode noServer**

Modify `server/unified-server.cjs` — remplacer la ligne :

```js
const wss = new WebSocket.Server({ server });
```

par :

```js
const wss = new WebSocket.Server({ noServer: true });
```

- [ ] **Step 3 : Ajouter le portail d'authentification sur l'upgrade**

Modify `server/unified-server.cjs` — juste après la création de `wss` (et la déclaration `const roomConnections = new Map();`), ajouter :

```js
// Portail d'authentification : on authentifie AVANT de brancher Yjs/WebRTC.
server.on('upgrade', (req, socket, head) => {
  wss.handleUpgrade(req, socket, head, async (ws) => {
    const decision = await authorize({
      reqUrl: req.url,
      backendUrl: BACKEND_INTERNAL_URL,
      cache: authCache,
    });

    if (!decision.ok) {
      console.log(`[${new Date().toISOString()}] Connexion WS refusée (code ${decision.closeCode}) pour ${req.url?.split('?')[0]}`);
      ws.close(decision.closeCode);
      return;
    }

    // Utilisateur authentifié : disponible pour fiabiliser l'identité plus tard.
    ws.user = decision.user;
    wss.emit('connection', ws, req);
  });
});
```

> Note : `/health` est servi par le handler HTTP `createServer` (pas un upgrade WebSocket), donc il reste public et non affecté.

- [ ] **Step 4 : Vérification manuelle (smoke test, backend requis)**

Run :
```bash
docker-compose up -d --build websocket-synkro backend-synkro
```

Puis, sans token (doit être refusé) :
```bash
npx -y wscat -c "ws://localhost:3001/room-1-editor"
```
Expected : la connexion se ferme immédiatement (code 4401). `wscat` affiche `Disconnected (code: 4401 ...)` ou une erreur de connexion.

Vérifier les logs :
```bash
docker-compose logs --tail=20 websocket-synkro
```
Expected : ligne `Connexion WS refusée (code 4401) pour /room-1-editor`.

- [ ] **Step 5 : Commit**

```bash
git add server/unified-server.cjs
git commit -m "feat(security): authentifie chaque connexion WebSocket via le backend"
```

---

## Task 4 : Infra — variable `BACKEND_INTERNAL_URL`

**Files:**
- Modify: `docker-compose.yml`
- Modify: `docker-compose.deploiement.yml`

- [ ] **Step 1 : Dev — ajouter la variable au service websocket**

Modify `docker-compose.yml` — dans le service `websocket-synkro`, section `environment`, après `- PORT=${PORT}` ajouter :

```yaml
      - BACKEND_INTERNAL_URL=http://backend-synkro:8000
```

- [ ] **Step 2 : Prod — ajouter la variable au service websocket**

Modify `docker-compose.deploiement.yml` — dans le service `websocket-synkro`, section `environment`, après `PORT: "3001"` ajouter :

```yaml
      BACKEND_INTERNAL_URL: "http://backend-synkro:8000"
```

> Justification : en prod, le service `backend-synkro` est le nginx interne qui écoute sur le port 8000 (`backend/docker/nginx.conf:2`) et route `/index.php` vers `backend-fpm:9000`. L'URL interne est donc identique à la dev. Les services `websocket-synkro` et `backend-synkro` sont sur le même réseau Docker (`synkronet-prod`).

- [ ] **Step 3 : Vérifier que le WS joint le backend en interne (dev)**

Run :
```bash
docker-compose up -d
docker-compose exec websocket-synkro node -e "fetch('http://backend-synkro:8000/api/ws/authorize/1',{headers:{Authorization:'Bearer invalide'}}).then(r=>console.log('STATUS',r.status)).catch(e=>console.log('ERR',e.message))"
```
Expected : `STATUS 401` (backend joignable, token invalide rejeté par le firewall). Tout autre `STATUS` 4xx/2xx prouve aussi la joignabilité ; `ERR` indiquerait un problème réseau à corriger avant de continuer.

- [ ] **Step 4 : Commit**

```bash
git add docker-compose.yml docker-compose.deploiement.yml
git commit -m "chore(security): BACKEND_INTERNAL_URL pour l'auth WebSocket (dev + prod)"
```

---

## Task 5 : Frontend — joindre le JWT à chaque connexion

**Files:**
- Modify: `frontend/src/composables/useYjs.ts`
- Modify: `frontend/src/components/chat/ChatModule.vue`
- Modify: `frontend/src/components/editor/CollaborativeEditor.vue`
- Modify: `frontend/src/components/editor/EditorModule.vue`
- Modify: `frontend/src/components/whiteboard/WhiteboardModule.vue`
- Modify: `frontend/src/composables/useWebRTC.ts`

`WebsocketProvider` accepte un 4e argument `{ params: { token } }` qui ajoute `?token=...` à l'URL. Pour les `WebSocket` bruts (WebRTC), on ajoute le query param à la main.

- [ ] **Step 1 : `useYjs.ts` — importer le store et joindre le token**

Modify `frontend/src/composables/useYjs.ts` :

1. Après la ligne `import { WebsocketProvider } from 'y-websocket'` ajouter :
```ts
import { useAuthStore } from '@/stores/auth'
```
2. Dans `useYjs`, juste après `export function useYjs(roomId: string) {`, ajouter :
```ts
  const authStore = useAuthStore()
```
3. Remplacer :
```ts
    provider.value = new WebsocketProvider(WS_URL, roomId, ydoc.value)
```
par :
```ts
    provider.value = new WebsocketProvider(WS_URL, roomId, ydoc.value, {
      params: { token: authStore.token ?? '' },
    })
```

- [ ] **Step 2 : `ChatModule.vue` — joindre le token**

Modify `frontend/src/components/chat/ChatModule.vue` :

1. Ajouter l'import dans le `<script>` (à côté de `import { WebsocketProvider } from "y-websocket";`) :
```ts
import { useAuthStore } from '@/stores/auth'
```
2. Remplacer la ligne 72 :
```ts
  provider = new WebsocketProvider(WS_URL, `room-${props.roomId}-chat`, ydoc);
```
par :
```ts
  provider = new WebsocketProvider(WS_URL, `room-${props.roomId}-chat`, ydoc, {
    params: { token: useAuthStore().token ?? '' },
  });
```

- [ ] **Step 3 : `CollaborativeEditor.vue` — joindre le token (2 occurrences)**

Modify `frontend/src/components/editor/CollaborativeEditor.vue` :

1. Ajouter l'import à côté de `import { WebsocketProvider } from 'y-websocket'` :
```ts
import { useAuthStore } from '@/stores/auth'
```
2. Remplacer **les deux** occurrences (lignes 60 et 127) :
```ts
  provider = new WebsocketProvider(WS_URL, `room-${props.roomId}-editor`, ydoc)
```
par :
```ts
  provider = new WebsocketProvider(WS_URL, `room-${props.roomId}-editor`, ydoc, {
    params: { token: useAuthStore().token ?? '' },
  })
```

- [ ] **Step 4 : `EditorModule.vue` — joindre le token**

Modify `frontend/src/components/editor/EditorModule.vue` :

1. Ajouter l'import à côté de `import { WebsocketProvider } from "y-websocket";` :
```ts
import { useAuthStore } from '@/stores/auth'
```
2. Remplacer la ligne 80 :
```ts
  provider = new WebsocketProvider(WS_URL, `room-${props.roomId}-editor`, ydoc);
```
par :
```ts
  provider = new WebsocketProvider(WS_URL, `room-${props.roomId}-editor`, ydoc, {
    params: { token: useAuthStore().token ?? '' },
  });
```

- [ ] **Step 5 : `WhiteboardModule.vue` — joindre le token**

Modify `frontend/src/components/whiteboard/WhiteboardModule.vue` :

1. Ajouter l'import à côté de `import { WebsocketProvider } from "y-websocket";` :
```ts
import { useAuthStore } from '@/stores/auth'
```
2. Le provider est créé en multi-lignes (≈ lignes 121-124) :
```ts
  provider = new WebsocketProvider(
    WS_URL,
    `room-${props.roomId}-whiteboard`,
    ydoc,
  );
```
Le transformer en :
```ts
  provider = new WebsocketProvider(
    WS_URL,
    `room-${props.roomId}-whiteboard`,
    ydoc,
    { params: { token: useAuthStore().token ?? '' } },
  );
```
> Avant d'éditer, lire le bloc exact : le 4e argument peut déjà exister ou la ponctuation différer. Insérer `{ params: { token: useAuthStore().token ?? '' } }` comme 4e argument du constructeur.

- [ ] **Step 6 : `useWebRTC.ts` — joindre le token à la WebSocket brute**

Modify `frontend/src/composables/useWebRTC.ts` — remplacer la ligne 169 :
```ts
    ws = new WebSocket(`${WS_URL}/room-${roomId}-video`)
```
par :
```ts
    const token = authStore.token ?? ''
    ws = new WebSocket(`${WS_URL}/room-${roomId}-video?token=${encodeURIComponent(token)}`)
```
> `authStore` est déjà disponible (déclaré ligne 31).

- [ ] **Step 7 : Vérifier le build et le lint**

Run :
```bash
cd frontend && npm run lint && npm run build
```
Expected : lint sans erreur, build réussi.

- [ ] **Step 8 : Commit**

```bash
git add frontend/src/composables/useYjs.ts frontend/src/composables/useWebRTC.ts frontend/src/components/chat/ChatModule.vue frontend/src/components/editor/CollaborativeEditor.vue frontend/src/components/editor/EditorModule.vue frontend/src/components/whiteboard/WhiteboardModule.vue
git commit -m "feat(security): joint le JWT à toutes les connexions WebSocket (Yjs + WebRTC)"
```

---

## Task 6 : Frontend — gestion des codes de fermeture (anti-boucle)

**Files:**
- Modify: `frontend/src/composables/useYjs.ts`
- Modify: `frontend/src/composables/useWebRTC.ts`

Sur un refus d'auth (4401/4403), `y-websocket` retenterait en boucle. On détecte ces codes pour arrêter la reconnexion et exposer un message d'erreur.

- [ ] **Step 1 : `useYjs.ts` — exposer une erreur et stopper la reconnexion**

Modify `frontend/src/composables/useYjs.ts` :

1. Après `const synced = ref(false)`, ajouter :
```ts
  const authError = ref<string | null>(null)
```
2. Dans `connect()`, après le bloc `provider.value.on('sync', ...)`, ajouter :
```ts
    provider.value.on('connection-close', (event: CloseEvent | null) => {
      if (event && (event.code === 4401 || event.code === 4403)) {
        authError.value = event.code === 4401
          ? 'Session expirée — veuillez vous reconnecter.'
          : "Accès refusé à cette room."
        // Stoppe la boucle de reconnexion de y-websocket.
        provider.value?.disconnect()
      }
    })
```
3. Ajouter `authError` à l'objet retourné (`return { ... }`), par ex. après `synced,` :
```ts
    authError,
```

- [ ] **Step 2 : `useWebRTC.ts` — gérer la fermeture pour auth refusée**

Modify `frontend/src/composables/useWebRTC.ts` — dans `setupSignaling()`, après l'affectation de `ws.onopen`, ajouter un handler `onclose` (ou compléter celui existant) :
```ts
    ws.onclose = (event) => {
      if (event.code === 4401 || event.code === 4403) {
        error.value = event.code === 4401
          ? 'Session expirée — veuillez vous reconnecter.'
          : "Accès refusé à l'appel vidéo de cette room."
        callState.value = 'idle'
      }
    }
```
> Si un `ws.onclose` existe déjà, y intégrer la vérification des codes 4401/4403 au lieu de l'écraser (lire le bloc avant d'éditer).

- [ ] **Step 3 : Vérifier le build**

Run :
```bash
cd frontend && npm run build
```
Expected : build réussi.

- [ ] **Step 4 : Commit**

```bash
git add frontend/src/composables/useYjs.ts frontend/src/composables/useWebRTC.ts
git commit -m "feat(security): stoppe la reconnexion WS et affiche un message sur refus d'auth"
```

---

## Task 7 (optionnel/bonus, prod) : masquer le token dans les access logs nginx

**Files:**
- Modify: `ansible/roles/vhosts/templates/synkro.conf.j2`

Le token passe en query string ; par défaut nginx logge l'URL complète (`$request`). On définit un `log_format` qui n'inclut que le chemin (`$uri`, sans query) pour le vhost WebSocket.

- [ ] **Step 1 : Lire le fichier et localiser le bloc WebSocket**

Run : ouvrir `ansible/roles/vhosts/templates/synkro.conf.j2` et repérer le `location` qui fait `proxy_pass` vers le serveur WebSocket (port 3001).

- [ ] **Step 2 : Définir un log_format sans query string**

Dans le contexte `http` (souvent `ansible/roles/nginx/templates/nginx.conf.j2` ou en tête du vhost si autorisé), ajouter :
```nginx
log_format ws_noquery '$remote_addr - $remote_user [$time_local] '
                      '"$request_method $uri $server_protocol" '
                      '$status $body_bytes_sent "$http_referer" "$http_user_agent"';
```

- [ ] **Step 3 : Utiliser ce format pour la location WebSocket**

Dans le `location` du proxy WebSocket, ajouter :
```nginx
        access_log /var/log/nginx/synkro-ws.access.log ws_noquery;
```

- [ ] **Step 4 : Valider la conf nginx (sur le serveur, via Ansible ou manuellement)**

Run (sur le VPS) : `nginx -t`
Expected : `syntax is ok` / `test is successful`.

- [ ] **Step 5 : Commit**

```bash
git add ansible/roles/vhosts/templates/synkro.conf.j2 ansible/roles/nginx/templates/nginx.conf.j2
git commit -m "chore(security): masque le token WebSocket dans les access logs nginx"
```

---

## Task 8 : Vérification end-to-end (démo avant/après)

**Files:** aucun (vérification fonctionnelle)

- [ ] **Step 1 : Démarrer la stack complète**

Run :
```bash
docker-compose up -d --build
cd frontend && npm run dev
```

- [ ] **Step 2 : Cas attaquant — connexion anonyme refusée**

Run :
```bash
npx -y wscat -c "ws://localhost:3001/room-1-editor"
```
Expected : fermeture immédiate, code **4401** (avant ce travail : la connexion restait ouverte et recevait les updates Yjs).

- [ ] **Step 3 : Cas attaquant — token valide mais room non autorisée**

Récupérer un JWT valide d'un utilisateur A (via `localStorage.token` dans le navigateur après login), puis tenter une room dont A n'est pas membre :
```bash
npx -y wscat -c "ws://localhost:3001/room-<ID_NON_AUTORISE>-editor?token=<JWT_DE_A>"
```
Expected : fermeture code **4403**.

- [ ] **Step 4 : Cas légitime — membre de la room**

Se connecter dans l'app avec un utilisateur membre de la room, ouvrir l'éditeur/le chat/le whiteboard de cette room.
Expected : la collaboration temps réel fonctionne normalement (synchro Yjs OK, pas de message d'erreur, pas de boucle de reconnexion).

- [ ] **Step 5 : Vérifier les suites de tests**

Run :
```bash
docker-compose exec backend php vendor/bin/phpunit tests/Unit --testdox
cd server && npm test
cd ../frontend && npm run build
```
Expected : backend vert, serveur WS vert, build frontend OK.

- [ ] **Step 6 : Commit final éventuel / notes de démo**

Documenter le résultat avant/après (captures wscat) pour la soutenance.

---

## Self-Review

**Spec coverage :**
- Composant 1 (endpoint backend) → Task 1. ✅
- Composant 2 (portail d'auth WS, codes de fermeture, cache) → Tasks 2 + 3. ✅
- Composant 3 (token frontend sur les 6 points + gestion d'erreur) → Tasks 5 + 6. ✅
- Composant 4 (infra `BACKEND_INTERNAL_URL` + logs nginx) → Tasks 4 + 7. ✅
- Tests (backend, node:test, démo manuelle) → Tasks 1, 2, 8. ✅
- Codes de fermeture 4401/4403/1011 → `closeCodeForStatus` (Task 2), câblage (Task 3), gestion front (Task 6). ✅

**Type consistency :**
- `authorize()` retourne toujours `{ ok, closeCode, user }` (Tasks 2 et 3 cohérents).
- `parseConnection()` retourne `{ docName, token, roomId }` (string|null) — utilisé en Task 2 uniquement.
- Endpoint backend : `userId` (int) + `displayName` (string) — produit en Task 1, consommé tel quel comme `decision.user` en Task 3 (`ws.user`).
- `BACKEND_INTERNAL_URL` : même nom et même valeur `http://backend-synkro:8000` en Tasks 3 et 4.
- Codes de fermeture identiques partout : 4401 / 4403 / 1011.

**Placeholder scan :** aucun TODO/TBD ; chaque step de code contient le code réel. Tasks 5.5, 6.2 et 7 demandent de **lire le bloc avant d'éditer** (texte multi-lignes ou pré-existant susceptible de varier), ce qui est une précaution, pas un placeholder.
