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
