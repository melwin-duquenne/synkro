import { createServer } from 'http';
import { WebSocketServer } from 'ws';
import * as Y from 'yjs';
import { setupWSConnection } from 'y-websocket/server';

const port = process.env.PORT || 3001;

const wss = new WebSocketServer({ port });

wss.on('connection', (ws, req) => {
  console.log('New WebSocket connection established');

  // Setup Yjs WebSocket connection
  setupWSConnection(ws, req);

  ws.on('close', () => {
    console.log('WebSocket connection closed');
  });

  ws.on('error', (error) => {
    console.error('WebSocket error:', error);
  });
});

console.log(`WebSocket server running on port ${port}`);