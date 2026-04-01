# Infrastructure Kubernetes — Synkro

Cluster hébergé chez **Infomaniak**, namespace `synkro`.  
IP publique du cluster : `195.15.195.73`

---

## Vue d'ensemble de l'architecture

```
Internet
    │
    ▼
[ LoadBalancer 195.15.195.73 ]
    │
    ├──► frontend-synkro (nginx :80)
    │         │ proxy_pass vers backend-synkro:8000
    │
    ├──► backend-synkro (nginx :8000 → PHP-FPM :9000)
    │         │ API Symfony + API Platform
    │
    ├──► websocket-synkro (:3001)
    │         │ Serveur Node.js temps réel
    │
    ├──► grafana (LoadBalancer 84.234.27.2 :3000)
    │
    └──► postgres-synkro (:5432) — primaire
              └──► postgres-replica (:5432) — réplique lecture seule
```

---

## Fichiers de base

### `namespace.yaml`
Crée le namespace `synkro` qui isole toutes les ressources du projet.

### `secrets.yaml`
Stocke les données sensibles chiffrées en base64 dans Kubernetes :
- Credentials PostgreSQL (`POSTGRES_USER`, `POSTGRES_PASSWORD`, `POSTGRES_DB`)
- Clé secrète Symfony (`APP_SECRET`)
- Passphrase JWT (`JWT_PASSPHRASE`)
- OAuth Google (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`)
- SMTP (`MAILER_DSN`)
- Mot de passe réplication PostgreSQL (`REPLICATION_PASSWORD`)
- Credentials Grafana (`GRAFANA_ADMIN_USER`, `GRAFANA_ADMIN_PASSWORD`)

### `configmap.yaml`
Variables d'environnement non-sensibles partagées entre les pods :
- `APP_ENV=prod`
- URLs de l'application (frontend, redirect OAuth, CORS)

### `jwt-pvc.yaml`
PersistentVolumeClaim de 100Mi pour stocker les clés RSA JWT générées au démarrage du backend. Permet de ne pas regénérer les clés à chaque redémarrage de pod.

---

## Application

### Frontend — `frontend-deployment.yaml` + `frontend-service.yaml`

**Image** : `moignon/synkro-frontend:latest`

- Conteneur **nginx** qui sert l'application Vue 3 compilée (SPA)
- Proxy les appels `/api/*` et `/ws/*` vers les services internes
- Service de type **LoadBalancer** exposé sur le port 80

**ConfigMap nginx** (`frontend-nginx-configmap.yaml`) :
- Utilise le DNS interne K8s (`resolver 10.96.0.10`) avec une variable `$backend` pour éviter les erreurs de démarrage si le backend n'est pas encore prêt

---

### Backend — `backend-deployment.yaml` + `backend-service.yaml`

**Image** : `moignon/synkro-backend:latest`

Le pod contient **2 conteneurs** (sidecar pattern) :
- `backend-fpm` : PHP-FPM Symfony qui traite les requêtes API
- `backend-nginx` : nginx qui reçoit les requêtes HTTP et les passe à PHP-FPM via FastCGI

**3 init containers** s'exécutent dans l'ordre avant le démarrage :

1. **`generate-jwt-keys`** : génère les clés RSA 4096 bits (privée + publique) si elles n'existent pas encore, stockées dans le PVC `jwt-keys`
2. **`copy-public`** : copie le dossier `/public` de l'image vers un volume partagé avec nginx (nécessaire car les deux conteneurs partagent les fichiers statiques)
3. **`migrations`** : exécute `doctrine:migrations:migrate` pour appliquer les migrations SQL au démarrage

**ConfigMap nginx backend** (`backend-nginx-configmap.yaml`) :
- Configure le virtual host nginx pour passer les requêtes à PHP-FPM
- Contient aussi le fichier `.env` Symfony minimal pour le mode production

---

### WebSocket — `websocket-deployment.yaml` + `websocket-service.yaml`

**Image** : `moignon/synkro-websocket:latest`

- Serveur Node.js gérant les connexions temps réel (WebSockets)
- Exposé sur le port 3001
- Health checks sur `/health`

---

## Base de données PostgreSQL

### Architecture : Primary + Replica (streaming replication)

PostgreSQL 16 est déployé en mode **réplication streaming** : toutes les écritures vont sur le primary, la réplique suit en temps réel et peut servir des lectures.

```
backend-synkro ──write──► postgres-primary (postgres-synkro:5432)
                                   │
                          streaming replication
                                   │
                                   ▼
                          postgres-replica:5432
                          (lecture seule)
```

### `postgres/postgres-configmap.yaml`

ConfigMap `postgres-config` contenant :
- `primary.conf` : paramètres de réplication (`wal_level=replica`, `max_wal_senders=3`, `hot_standby=on`)
- `pg_hba_extra.conf` : autorise les connexions de réplication depuis le réseau interne `10.0.0.0/8`
- `init-replication.sh` : script d'initialisation qui :
  - Ajoute la règle `pg_hba.conf` pour le replicator
  - Crée le rôle `replicator` avec les droits de réplication

### `postgres/postgres-primary.yaml`

**StatefulSet** `postgres-primary` (1 replica) :
- Réutilise le PVC existant `postgres-data` (subPath `pgdata`)
- Démarre PostgreSQL avec les options de réplication passées en arguments (`-c wal_level=replica`, etc.)
- Le script `init-replication.sh` est monté dans `/docker-entrypoint-initdb.d/` et s'exécute automatiquement à la création de la base
- **Service** `postgres-synkro` : point d'accès unique pour le backend (rétrocompatible)

### `postgres/postgres-replica.yaml`

**StatefulSet** `postgres-replica` (1 replica) :
- **Init container `clone-primary`** : exécute `pg_basebackup` pour faire une copie complète du primary avant le premier démarrage. Crée aussi le fichier `standby.signal` pour que PostgreSQL démarre en mode standby
- Démarre ensuite en mode lecture seule, connecté en streaming au primary
- **Service** `postgres-replica` : accessible en interne pour les futures lectures

---

## Sécurité — GateKeeper OPA

**OPA GateKeeper** est un contrôleur d'admission Kubernetes qui intercepte chaque déploiement et vérifie qu'il respecte les politiques définies.

### `gatekeeper/gatekeeper-install.yaml`

Installation complète de GateKeeper v3.15.0 (CRDs + controller + audit) dans le namespace `gatekeeper-system`. Fichier issu de la release officielle.

### `gatekeeper/constraints.yaml`

3 politiques de sécurité, toutes en mode **`dryrun`** (audit sans bloquer) :

| Politique | Règle |
|---|---|
| `K8sRequiredResourceLimits` | Tous les conteneurs doivent définir `resources.limits.cpu` et `resources.limits.memory` |
| `K8sBlockPrivileged` | Interdit les conteneurs avec `securityContext.privileged: true` |
| `K8sRequiredLabels` | Tous les Deployments/StatefulSets doivent avoir le label `app` |

> Pour passer en mode bloquant, changer `enforcementAction: dryrun` en `enforcementAction: deny`.

---

## Monitoring — Prometheus + Grafana

### Architecture

```
kube-state-metrics ──► expose métriques K8s (pods, deployments...)
postgres-exporter  ──► expose métriques PostgreSQL
cadvisor (kubelet) ──► expose métriques CPU/mémoire des conteneurs
        │
        ▼
   Prometheus (scrape toutes les 15s)
        │
        ▼
   Grafana (dashboards visuels)
        │
        ▼
   Accessible sur http://84.234.27.2:3000
```

### `monitoring/kube-state-metrics.yaml`

**kube-state-metrics** est un service qui lit l'état du cluster K8s (pods, deployments, nodes...) et l'expose en métriques Prometheus.

- RBAC : ClusterRole avec accès lecture sur toutes les ressources K8s
- Image : `registry.k8s.io/kube-state-metrics/kube-state-metrics:v2.11.0`
- Port 8080 (métriques), 8081 (télémétrie interne)
- RunAsUser 65534 (nobody)

### `monitoring/postgres-exporter.yaml`

**postgres-exporter** se connecte à PostgreSQL et expose ses métriques (connexions, transactions, taille des tables, lag de réplication...) pour Prometheus.

- Image : `prometheuscommunity/postgres-exporter:v0.15.0`
- Connexion via `DATA_SOURCE_NAME` avec les credentials du secret
- Port 9187
- Annotation `prometheus.io/scrape: "true"` pour être auto-découvert

### `monitoring/prometheus.yaml`

**Prometheus** collecte (scrape) les métriques de toutes les sources configurées.

**Jobs de scrape configurés** :

| Job | Source | Ce qu'il collecte |
|---|---|---|
| `prometheus` | localhost:9090 | Métriques de Prometheus lui-même |
| `kube-state-metrics` | kube-state-metrics:8080 | État des pods, deployments, nodes |
| `postgres` | postgres-exporter:9187 | Métriques PostgreSQL |
| `kubernetes-nodes` | API server proxy | Métriques kubelet des nodes |
| `kubernetes-cadvisor` | API server proxy | CPU et mémoire des conteneurs |
| `kubernetes-pods` | Annotation-based | Pods avec `prometheus.io/scrape: "true"` |
| `kubernetes-service-endpoints` | Annotation-based | Services avec `prometheus.io/scrape: "true"` |

- Rétention des données : **15 jours**
- PVC `prometheus-data` : 10Gi
- Service ClusterIP (accès interne Grafana uniquement)

### `monitoring/grafana.yaml`

**Grafana** permet de créer des dashboards visuels à partir des données Prometheus.

- Image : `grafana/grafana:10.4.0`
- Datasource Prometheus auto-provisionnée via ConfigMap (pointe vers `prometheus.synkro.svc.cluster.local:9090`)
- PVC `grafana-data` : 2Gi (pour sauvegarder les dashboards)
- Service **LoadBalancer** → accessible sur `http://84.234.27.2:3000`
- Credentials dans les secrets K8s (`GRAFANA_ADMIN_USER` / `GRAFANA_ADMIN_PASSWORD`)

**Dashboards recommandés à importer** (Grafana → Dashboards → Import) :
- `15661` — Kubernetes Views Global (vue d'ensemble du cluster)
- `6417` — Kubernetes Cluster (pods, CPU, mémoire par namespace)
- `9628` — PostgreSQL Database (connexions, transactions, réplication)

---

## Ordre de déploiement

```bash
# 1. Namespace + secrets + configmap
kubectl apply -f k8s/namespace.yaml
kubectl apply -f k8s/secrets.yaml
kubectl apply -f k8s/configmap.yaml
kubectl apply -f k8s/jwt-pvc.yaml

# 2. PostgreSQL
kubectl apply -f k8s/postgres/postgres-configmap.yaml
kubectl apply -f k8s/postgres/postgres-primary.yaml
kubectl apply -f k8s/postgres/postgres-replica.yaml

# 3. Application
kubectl apply -f k8s/backend-nginx-configmap.yaml
kubectl apply -f k8s/backend-deployment.yaml
kubectl apply -f k8s/backend-service.yaml
kubectl apply -f k8s/frontend-nginx-configmap.yaml
kubectl apply -f k8s/frontend-deployment.yaml
kubectl apply -f k8s/frontend-service.yaml
kubectl apply -f k8s/websocket-deployment.yaml
kubectl apply -f k8s/websocket-service.yaml

# 4. GateKeeper
kubectl apply -f k8s/gatekeeper/gatekeeper-install.yaml
# Attendre ~30s que les CRDs soient prêts
kubectl apply -f k8s/gatekeeper/constraints.yaml

# 5. Monitoring
kubectl apply -f k8s/monitoring/kube-state-metrics.yaml
kubectl apply -f k8s/monitoring/postgres-exporter.yaml
kubectl apply -f k8s/monitoring/prometheus.yaml
kubectl apply -f k8s/monitoring/grafana.yaml
```

## Vérification

```bash
# Tous les pods Running
kubectl get pods -n synkro
kubectl get pods -n gatekeeper-system

# Services et IPs
kubectl get svc -n synkro

# Réplication PostgreSQL active
kubectl exec -n synkro postgres-primary-0 -- \
  psql -U synkro -c "SELECT client_addr, state, replay_lag FROM pg_stat_replication;"

# Violations GateKeeper (mode dryrun)
kubectl get constraints -A
```
