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

test('parseConnection retire la query string du docName (régression: rooms isolées par token)', () => {
  // Deux utilisateurs, deux tokens JWT différents, MÊME room. Le docName
  // (= room Yjs + clé peers WebRTC dans unified-server) doit être identique,
  // sinon chacun se retrouve seul dans sa propre room (visio/éditeur/whiteboard).
  const alice = parseConnection('/room-42-video?token=JWT_ALICE');
  const bob = parseConnection('/room-42-video?token=JWT_BOB');
  assert.strictEqual(alice.docName, 'room-42-video');
  assert.strictEqual(bob.docName, 'room-42-video');
  assert.strictEqual(alice.docName, bob.docName);
  // Garde-fou explicite : l'ancienne logique `url.slice(1)` gardait le token.
  assert.notStrictEqual('/room-42-video?token=JWT_ALICE'.slice(1), alice.docName);
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
