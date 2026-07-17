'use strict';

const test = require('node:test');
const assert = require('node:assert');
const { decodeSignalFrame } = require('../webrtc.cjs');

// CONTROL (type 5) "join" — comme envoyé par le front (useWebRTC.sendControl).
function joinFrame() {
  const payload = Buffer.from(JSON.stringify({ action: 'join', peerId: 'p1' }));
  return Buffer.concat([Buffer.from([5]), payload]);
}

test('decodeSignalFrame décode une frame CONTROL en Buffer', () => {
  const f = decodeSignalFrame(joinFrame());
  assert.strictEqual(f.type, 5);
  assert.deepStrictEqual(JSON.parse(f.payload.toString()), { action: 'join', peerId: 'p1' });
});

test('decodeSignalFrame décode la MÊME frame en ArrayBuffer (régression binaryType=arraybuffer)', () => {
  // Reproduit ce que reçoit réellement le serveur : y-websocket met la socket en
  // binaryType 'arraybuffer', donc `data` est un ArrayBuffer. L'ancien code
  // (Buffer.isBuffer) renvoyait false ici → aucun peer WebRTC enregistré.
  const b = joinFrame();
  const ab = b.buffer.slice(b.byteOffset, b.byteOffset + b.byteLength);
  assert.ok(ab instanceof ArrayBuffer);
  const f = decodeSignalFrame(ab);
  assert.ok(f, 'un ArrayBuffer doit être décodé');
  assert.strictEqual(f.type, 5);
  assert.deepStrictEqual(JSON.parse(f.payload.toString()), { action: 'join', peerId: 'p1' });
});

test('decodeSignalFrame ignore les messages Yjs (type 0/1) et le non-binaire', () => {
  assert.strictEqual(decodeSignalFrame(Buffer.from([0, 9, 9])), null); // sync Yjs
  assert.strictEqual(decodeSignalFrame(Buffer.from([1, 9])), null);    // awareness Yjs
  assert.strictEqual(decodeSignalFrame('hello'), null);                // texte
  assert.strictEqual(decodeSignalFrame(Buffer.alloc(0)), null);        // vide
});
