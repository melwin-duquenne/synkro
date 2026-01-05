FROM node:20

WORKDIR /app

# Copier les fichiers de configuration npm
COPY package*.json ./

# Installer les dépendances
RUN npm install

# Copier le code source
COPY . .

# Exposer le port pour le serveur unifié
EXPOSE 3001

# Démarrer le serveur unifié (Yjs + Whiteboard)
CMD ["node", "unified-server.cjs"]
