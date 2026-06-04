# Design : Authentification du serveur WebSocket

Date : 2026-05-30
Auteur : Calista (responsable sécurité)
Statut : Validé, prêt pour planification

## Contexte et problème

Synkro est une plateforme collaborative temps réel (Vue 3 + Symfony 7.4 + serveur
WebSocket Node.js). Le serveur WebSocket (`server/unified-server.cjs`) relaie les
mises à jour Yjs (CRDT) et la signalisation WebRTC entre les clients d'une même
« room ».

**Faille (critique) :** le serveur WS n'authentifie aucune connexion. Il extrait
seulement l'identifiant de room depuis l'URL (`/room-42-editor`) puis branche Yjs
(`unified-server.cjs:103-105,154`). Les IDs de room sont des entiers séquentiels en
base, donc triviaux à deviner (1, 2, 3...). Conséquence : n'importe qui peut se
connecter à la WebSocket de n'importe quelle room et **lire + modifier en temps réel**
son contenu (éditeur, whiteboard, chat, kanban) et rejoindre les appels vidéo, sans
être membre ni même authentifié.

### Briques déjà présentes (réutilisées)

- Le frontend possède déjà le JWT dans `authStore` (token en `localStorage`).
  `useWebRTC` l'utilise déjà ; `useYjs` non.
- Le backend sait déjà répondre à « cet utilisateur peut-il accéder à cette room ? »
  via `RoomAccessChecker::canAccess(User, Room)` (`backend/src/Security/RoomAccessChecker.php`).
- Le JWT est signé RS256 et déjà vérifié par le firewall `^/api` (`security.yaml`).

Il manque uniquement le **chaînon** : transmettre le token au serveur WS et le faire
valider.

## Approche retenue

**Approche A — délégation au backend** (choisie parmi 3 ; voir « Alternatives »).

Le serveur WS ne duplique aucune logique de permission. Il transmet le JWT existant au
backend, qui reste la **seule source de vérité** pour l'autorisation.

Transport du token : **query param** `?token=...` (méthode standard de y-websocket via
l'option `params`).

### Flux

```
Frontend (a le JWT)                Serveur WS (Node)              Backend (Symfony)
      │                                  │                              │
      │  ws://…/room-42-editor?token=JWT │                              │
      ├─────────────────────────────────►                              │
      │                          extrait roomId=42 + token             │
      │                                  │  GET /api/ws/authorize/42    │
      │                                  │  Authorization: Bearer JWT   │
      │                                  ├──────────────────────────────►
      │                                  │   firewall ^/api authentifie  │
      │                                  │   RoomAccessChecker::canAccess │
      │                                  │   200 {userId,displayName}     │
      │                                  │   ou 401/403/404               │
      │                                  ◄──────────────────────────────┤
      │   200 → upgrade accepté          │                              │
      │   sinon → fermeture (4401/4403)  │                              │
      ◄─────────────────────────────────┤                              │
```

## Composants

### 1. Backend — endpoint d'autorisation

Nouvel endpoint **`GET /api/ws/authorize/{roomId}`** (controller `WebSocketAuthController`,
`backend/src/Controller/`) :

- Sous le firewall `^/api` → JWT authentifié automatiquement. Token absent/invalide/
  expiré → **401** géré par Lexik (rien à coder).
- Charge la `Room` par `id` ; introuvable → **404**.
- `RoomAccessChecker::canAccess($user, $room)` → refus → **403**.
- Succès → **200** `{ "userId": int, "displayName": string }` (Node en aura besoin pour
  fiabiliser l'identité WebRTC plus tard).
- L'endpoint ne doit **pas** être ajouté à la liste `PUBLIC_ACCESS` de `security.yaml`
  (protégé par défaut via la règle `- { path: ^/api, roles: IS_AUTHENTICATED }`).

### 2. Serveur WS — portail d'authentification

Refonte de `server/unified-server.cjs` pour authentifier **pendant le handshake HTTP
`upgrade`**, avant d'établir la connexion :

- `WebSocket.Server` passe en mode `{ noServer: true }` ; on gère `server.on('upgrade', …)`.
- Fonction `authenticate(req)` :
  1. Parser l'URL → `docName` (chemin) + `token` (query `?token=`).
  2. Extraire l'ID numérique via regex `room-(\d+)-` (couvre `editor`, `chat`,
     `whiteboard`, `video`). Pas de token ou pas d'ID → rejet **4401**.
  3. Cache mémoire (clé `token|roomId`, TTL ~30 s). Hit → renvoyer la décision.
  4. Sinon `fetch(BACKEND_INTERNAL_URL + '/api/ws/authorize/' + id,
     { headers: { Authorization: 'Bearer ' + token } })` :
     - `200` → cache + autoriser, attacher `ws.user = {userId, displayName}`.
     - `401` → fermer **4401**.
     - `403`/`404` → fermer **4403**.
     - injoignable / 5xx → fermer **1011** (pas de mise en cache).
  5. Succès → `wss.handleUpgrade(...)` puis `wss.emit('connection', ws, req)`.
- `/health` reste public, non authentifié.
- URL backend via env `BACKEND_INTERNAL_URL` (à définir par environnement, voir §4).
- **Aucune nouvelle dépendance npm** (`fetch` natif, Node 20).

### 3. Frontend — transmettre le token

5 points de connexion à mettre à jour (token lu depuis `authStore`) :

- Providers Yjs : ajouter l'option `{ params: { token } }` au `WebsocketProvider`
  (ajoute `?token=…`) :
  - `useYjs.ts:17`
  - `chat/ChatModule.vue:72`
  - `editor/CollaborativeEditor.vue:60` et `:127`
  - `editor/EditorModule.vue:80`
  - `whiteboard/WhiteboardModule.vue:121`
- WebRTC : ajouter `?token=${token}` à l'URL (`useWebRTC.ts:169`).
- Gestion d'erreur : sur fermeture WS code 4401/4403, afficher un message
  (« accès refusé / session expirée ») plutôt qu'une reconnexion silencieuse en boucle.

### 4. Infra — variable d'environnement par environnement

L'archi prod diffère de la dev mais l'URL interne est **identique** (vérifié) :

- **dev** (`docker-compose.yml`) : backend = service `backend-synkro` (Symfony) sur le
  port 8000.
- **prod** (`docker-compose.deploiement.yml`) : backend éclaté en `backend-fpm`
  (PHP-FPM, FastCGI:9000, **pas** HTTP) + `backend-synkro` (nginx) qui écoute sur le
  **port 8000** (`backend/docker/nginx.conf:2`) et route `/index.php` → FPM via le
  resolver Docker interne.

Dans les deux cas : `BACKEND_INTERNAL_URL=http://backend-synkro:8000`.
Ajouter la variable au service `websocket-synkro` dans **les deux** fichiers compose.
Les services WS et backend-nginx sont sur le même réseau Docker dans chaque env
(joignables ; les appels container-à-container court-circuitent le nginx hôte + fail2ban).

Bonus présentation : masquer l'arg `token` dans les access logs nginx du vhost WS
(`ansible/roles/vhosts/templates/synkro.conf.j2`).

## Gestion des erreurs (codes de fermeture WS)

| Situation | Code | Sens |
|-----------|------|------|
| Pas de token / ID room illisible | 4401 | Unauthorized |
| Backend 401 (token invalide/expiré) | 4401 | Unauthorized |
| Backend 403/404 | 4403 | Forbidden |
| Backend injoignable / 5xx | 1011 | Internal error (retry possible) |

## Tests

- **Backend** : test fonctionnel de `WebSocketAuthController` — 200 membre, 403 non-membre,
  404 room inexistante, 401 sans token (firewall).
- **Serveur WS** : tests via `node:test` (intégré, zéro dépendance) — extraction
  (`docName` + token + regex ID) et logique de décision avec backend mocké
  (200/401/403/5xx → bon code de fermeture). Le serveur n'a pas encore de setup de test ;
  on l'ajoute.
- **Démo manuelle (avant/après)** : `wscat` anonyme sur `room-1-editor` reçoit les
  updates *avant* ; *après* = fermé 4401. Non-membre = 4403. Membre légitime = OK.

## Alternatives écartées

- **B — vérif JWT locale dans Node + appel backend** : défense en profondeur mais ajoute
  une dépendance (`jsonwebtoken`) et la distribution de la clé publique au conteneur Node,
  pour un gain marginal (le backend rejette déjà les tokens invalides).
- **C — ticket de room signé** émis par le backend : le plus « propre » en théorie mais
  le plus de pièces mobiles (endpoint d'émission, expiration/renouvellement côté front) ;
  overkill ici.

## Hors-scope (notés pour plus tard)

- Anti-spoof de l'identité dans les messages de contrôle WebRTC (remplacer le `userId`
  client déclaratif par `ws.user`).
- Rate-limiting des messages WS.
- Distinction lecture seule (viewer) vs édition au niveau WS (le backend renvoie l'accès,
  pas encore le rôle fin).
