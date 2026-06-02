const http = require('http');
const WebSocket = require('ws');
const { setupWSConnection } = require('y-websocket/bin/utils');
const { authorize } = require('./auth.cjs');

const port = process.env.PORT || 3001;
const BACKEND_INTERNAL_URL = process.env.BACKEND_INTERNAL_URL || 'http://backend-synkro:8000';
const authCache = new Map(); // clé "token|roomId" -> { decision, expires }

// WebRTC signaling message types
const WEBRTC_MESSAGE_TYPES = {
  OFFER: 2,
  ANSWER: 3,
  ICE_CANDIDATE: 4,
  CONTROL: 5,
};

// Track WebRTC peers per room
const webrtcPeers = new Map(); // roomId -> Map<peerId, { ws, userId, userName }>

// Create HTTP server with CORS support
const server = http.createServer((req, res) => {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') {
    res.writeHead(200);
    res.end();
    return;
  }

  if (req.url === '/health') {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ status: 'ok', connections: wss.clients.size }));
    return;
  }

  res.writeHead(404);
  res.end();
});

const wss = new WebSocket.Server({ noServer: true });

// Track connections per room
const roomConnections = new Map();

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

// Handle WebRTC signaling messages
function handleWebRTCMessage(ws, roomId, messageType, payload) {
  try {
    const message = JSON.parse(payload.toString());
    const { targetPeerId, fromPeerId } = message;

    // Get room peers
    const roomPeerMap = webrtcPeers.get(roomId);
    if (!roomPeerMap) return;

    if (targetPeerId) {
      // Send to specific peer
      const targetPeer = roomPeerMap.get(targetPeerId);
      if (targetPeer && targetPeer.ws.readyState === WebSocket.OPEN) {
        const forwardMessage = Buffer.concat([
          Buffer.from([messageType]),
          Buffer.from(JSON.stringify({ ...message, fromPeerId }))
        ]);
        targetPeer.ws.send(forwardMessage);
      }
    } else {
      // Broadcast to all peers in room except sender
      roomPeerMap.forEach((peer, peerId) => {
        if (peerId !== fromPeerId && peer.ws.readyState === WebSocket.OPEN) {
          const forwardMessage = Buffer.concat([
            Buffer.from([messageType]),
            Buffer.from(JSON.stringify({ ...message, fromPeerId }))
          ]);
          peer.ws.send(forwardMessage);
        }
      });
    }
  } catch (e) {
    console.error(`[${new Date().toISOString()}] WebRTC message error:`, e.message);
  }
}

// Register a WebRTC peer
function registerWebRTCPeer(ws, roomId, peerId, userId, userName) {
  if (!webrtcPeers.has(roomId)) {
    webrtcPeers.set(roomId, new Map());
  }
  webrtcPeers.get(roomId).set(peerId, { ws, userId, userName });
  console.log(`[${new Date().toISOString()}] WebRTC peer ${peerId} registered in room ${roomId}`);
}

// Unregister a WebRTC peer
function unregisterWebRTCPeer(roomId, peerId) {
  const roomPeerMap = webrtcPeers.get(roomId);
  if (roomPeerMap) {
    roomPeerMap.delete(peerId);
    if (roomPeerMap.size === 0) {
      webrtcPeers.delete(roomId);
    }
    console.log(`[${new Date().toISOString()}] WebRTC peer ${peerId} unregistered from room ${roomId}`);
  }
}

wss.on('connection', (ws, req) => {
  // Extract room ID from URL path (e.g., /room-123)
  const roomId = req.url?.slice(1) || 'default';
  let webrtcPeerId = null;

  console.log(`[${new Date().toISOString()}] New connection to room: ${roomId}`);

  // Track connection
  if (!roomConnections.has(roomId)) {
    roomConnections.set(roomId, new Set());
  }
  roomConnections.get(roomId).add(ws);

  // Intercept messages for WebRTC signaling
  const originalSend = ws.send.bind(ws);

  // Add message handler for WebRTC signaling
  ws.on('message', (data) => {
    try {
      // Check if it's a binary message with WebRTC type
      if (Buffer.isBuffer(data) && data.length > 0) {
        const messageType = data[0];

        // WebRTC message types (2-5)
        if (messageType >= 2 && messageType <= 5) {
          const payload = data.slice(1);

          // Handle peer registration from control messages
          if (messageType === WEBRTC_MESSAGE_TYPES.CONTROL) {
            try {
              const controlMsg = JSON.parse(payload.toString());
              if (controlMsg.action === 'join' && controlMsg.peerId) {
                webrtcPeerId = controlMsg.peerId;
                registerWebRTCPeer(ws, roomId, controlMsg.peerId, controlMsg.userId, controlMsg.userName);
              } else if (controlMsg.action === 'leave' && controlMsg.peerId) {
                unregisterWebRTCPeer(roomId, controlMsg.peerId);
                webrtcPeerId = null;
              }
            } catch (e) {}
          }

          handleWebRTCMessage(ws, roomId, messageType, payload);
          return; // Don't pass to Yjs
        }
      }
    } catch (e) {
      // Not a WebRTC message, let Yjs handle it
    }
  });

  // Setup Yjs WebSocket connection
  setupWSConnection(ws, req, { docName: roomId });

  ws.on('close', () => {
    console.log(`[${new Date().toISOString()}] Connection closed for room: ${roomId}`);

    // Cleanup WebRTC peer
    if (webrtcPeerId) {
      unregisterWebRTCPeer(roomId, webrtcPeerId);
    }

    const connections = roomConnections.get(roomId);
    if (connections) {
      connections.delete(ws);
      if (connections.size === 0) {
        roomConnections.delete(roomId);
        console.log(`[${new Date().toISOString()}] Room ${roomId} is now empty`);
      }
    }
  });

  ws.on('error', (error) => {
    console.error(`[${new Date().toISOString()}] WebSocket error in room ${roomId}:`, error.message);
  });
});

// Log stats every 30 seconds
setInterval(() => {
  const totalConnections = wss.clients.size;
  const roomCount = roomConnections.size;
  if (totalConnections > 0) {
    console.log(`[${new Date().toISOString()}] Stats: ${totalConnections} connections across ${roomCount} rooms`);
  }
}, 30000);

server.listen(port, '0.0.0.0', () => {
  console.log(`[${new Date().toISOString()}] Yjs WebSocket server running on port ${port}`);
  console.log(`[${new Date().toISOString()}] Health check available at http://localhost:${port}/health`);
});

// Graceful shutdown
process.on('SIGTERM', () => {
  console.log('[SHUTDOWN] Received SIGTERM, closing server...');
  wss.clients.forEach(client => {
    client.close(1001, 'Server shutting down');
  });
  server.close(() => {
    console.log('[SHUTDOWN] Server closed');
    process.exit(0);
  });
});
