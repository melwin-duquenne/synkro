'use strict';

/**
 * Normalise une donnée de message WebSocket en Buffer.
 * y-websocket force `conn.binaryType = 'arraybuffer'` sur la connexion serveur
 * (voir node_modules/y-websocket/bin/utils.js), donc les frames binaires
 * arrivent en ArrayBuffer — PAS en Buffer. Sans normalisation, tout test
 * `Buffer.isBuffer(data)` échoue et le signaling WebRTC est ignoré.
 * @param {*} data Buffer | ArrayBuffer | TypedArray | autre
 * @returns {Buffer|null}
 */
function toBuffer(data) {
  if (Buffer.isBuffer(data)) return data;
  if (data instanceof ArrayBuffer) return Buffer.from(data);
  if (ArrayBuffer.isView(data)) return Buffer.from(data.buffer, data.byteOffset, data.byteLength);
  return null;
}

/**
 * Décode une frame de signaling WebRTC (1er octet = type 2..5, reste = payload).
 * Retourne null pour tout le reste (messages Yjs type 0/1, non-binaire) afin de
 * les laisser à y-websocket.
 * @param {*} data
 * @returns {{type: number, payload: Buffer}|null}
 */
function decodeSignalFrame(data) {
  const buf = toBuffer(data);
  if (!buf || buf.length === 0) return null;
  const type = buf[0];
  if (type < 2 || type > 5) return null;
  return { type, payload: buf.slice(1) };
}

module.exports = { toBuffer, decodeSignalFrame };
