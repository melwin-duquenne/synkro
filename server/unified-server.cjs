const http = require('http');
const WebSocket = require('ws');
const Y = require('yjs');
const { setupWSConnection } = require('y-websocket/bin/utils');

const port = process.env.PORT || 3001;
const server = http.createServer();

const wss = new WebSocket.Server({ server });

wss.on('connection', (ws, req) => {
  setupWSConnection(ws, req);
});

server.listen(port, () => {
  console.log(`Yjs WebSocket server running on port ${port}`);
});
