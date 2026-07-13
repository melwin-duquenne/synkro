#!/usr/bin/env bash
#
# seed-demo.sh — charge (ou retire) le jeu de données de démonstration sur un
# environnement DÉPLOYÉ (staging par défaut : dev.synkro.ovh).
#
# À exécuter SUR LE VPS. La commande est ADDITIVE et IDEMPOTENTE : elle n'efface
# jamais les données existantes et peut être relancée sans créer de doublon.
#
# PRÉREQUIS : l'image déployée doit contenir la commande `app:demo:seed`.
#   → Merge ta branche sur `develop` (staging) ou `main` (prod) et laisse la CI
#     reconstruire + redéployer AVANT de lancer ce script. Le script vérifie ce
#     point et te préviendra si la commande n'est pas encore déployée.
#
# Usage :
#   ./seed-demo.sh                  # seed sur STAGING (dev.synkro.ovh)
#   ./seed-demo.sh remove           # retire la démo de STAGING
#   ENV=prod ./seed-demo.sh         # seed sur PROD (synkro.ovh)
#   ENV=prod ./seed-demo.sh remove  # retire la démo de PROD
#
# Comptes créés (mot de passe : Demo1234!) :
#   admin@demo.synkro.ovh (owner/admin) · user@demo.synkro.ovh (éditeur) · guest@demo.synkro.ovh (invité)

set -euo pipefail

ENV="${ENV:-staging}"
ACTION="${1:-seed}"

case "$ENV" in
  staging) APP_DIR="/opt/synkro-staging"; COMPOSE_SRC="docker-compose.staging.yml"; PROJECT="synkro-staging"; BRANCH="develop" ;;
  prod)    APP_DIR="/opt/synkro";         COMPOSE_SRC="docker-compose.prod.yml";    PROJECT="synkro";         BRANCH="main" ;;
  *) echo "✗ ENV inconnu : '$ENV' (attendu : staging | prod)" >&2; exit 1 ;;
esac

COMPOSE_FILE="$APP_DIR/$COMPOSE_SRC"
COMPOSE=(docker compose -p "$PROJECT" --env-file "$APP_DIR/.env" -f "$COMPOSE_FILE")

echo "▶ Environnement : $ENV  ($APP_DIR)"

if [ ! -f "$COMPOSE_FILE" ]; then
  echo "✗ Fichier compose introuvable : $COMPOSE_FILE" >&2
  echo "  L'application est-elle bien déployée sur cet environnement ?" >&2
  exit 1
fi

# La commande existe-t-elle dans l'image actuellement déployée ?
if ! "${COMPOSE[@]}" exec -T backend-fpm php bin/console list app 2>/dev/null | grep -q 'app:demo:seed'; then
  echo "✗ La commande 'app:demo:seed' n'est pas présente dans l'image déployée." >&2
  echo "  → Merge ta branche sur '$BRANCH', laisse la CI reconstruire + redéployer," >&2
  echo "    puis relance ce script." >&2
  exit 1
fi

case "$ACTION" in
  seed)
    echo "▶ Chargement du jeu de démonstration (additif, idempotent)…"
    "${COMPOSE[@]}" exec -T backend-fpm php bin/console app:demo:seed
    ;;
  remove)
    echo "▶ Retrait du jeu de démonstration (les données réelles ne sont pas touchées)…"
    "${COMPOSE[@]}" exec -T backend-fpm php bin/console app:demo:seed --remove
    ;;
  *)
    echo "✗ Action inconnue : '$ACTION' (attendu : seed | remove)" >&2
    exit 1
    ;;
esac

echo "✔ Terminé sur $ENV."
