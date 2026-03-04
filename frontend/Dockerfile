# ─── Stage: deps ──────────────────────────────────────────────────────────────
FROM node:20-alpine AS deps
WORKDIR /app
COPY package*.json ./
RUN npm ci

# ─── Stage: dev (HMR via volume mount) ───────────────────────────────────────
FROM node:20-alpine AS dev
WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
# Le code source est monté via un volume docker-compose (./frontend:/app)
EXPOSE 5173
CMD ["npm", "run", "dev"]

# ─── Stage: builder ───────────────────────────────────────────────────────────
FROM node:20-alpine AS builder
WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
COPY . .
RUN npm run build

# ─── Stage: prod (Nginx) ──────────────────────────────────────────────────────
FROM nginx:1.27-alpine AS prod
COPY --from=builder /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
