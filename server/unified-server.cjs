const http = require('http');
const WebSocket = require('ws');
const { setupWSConnection } = require('y-websocket/bin/utils');

const port = process.env.PORT || 3001;

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

const wss = new WebSocket.Server({ server });

// Track connections per room
const roomConnections = new Map();

wss.on('connection', (ws, req) => {
  // Extract room ID from URL path (e.g., /room-123)
  const roomId = req.url?.slice(1) || 'default';

  console.log(`[${new Date().toISOString()}] New connection to room: ${roomId}`);

  // Track connection
  if (!roomConnections.has(roomId)) {
    roomConnections.set(roomId, new Set());
  }
  roomConnections.get(roomId).add(ws);

  // Setup Yjs WebSocket connection
  setupWSConnection(ws, req, { docName: roomId });

  ws.on('close', () => {
    console.log(`[${new Date().toISOString()}] Connection closed for room: ${roomId}`);
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
