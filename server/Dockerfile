# ─── Stage: dev ──────────────────────────────────────────────────────────────
FROM node:20 AS dev

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .

EXPOSE 3001
CMD ["node", "unified-server.cjs"]

# ─── Stage: prod (Alpine, non-root) ─────────────────────────────────────────
FROM node:20-alpine AS prod

WORKDIR /app

COPY package*.json ./
RUN npm ci --omit=dev

COPY . .

USER node

EXPOSE 3001
CMD ["node", "unified-server.cjs"]
