# Architecture Kubernetes — Synkro

## Vue simplifiée

```mermaid
graph TB
    Browser([Navigateur]) -->|HTTP| LB["LoadBalancer\n195.15.195.73 :80"]

    LB --> FE

    subgraph synkro["Namespace : synkro"]

        subgraph app["Application"]
            FE["Frontend — nginx\n× 3 replicas"]
            BE["Backend — Symfony + PHP-FPM\n× 3 replicas"]
            WS["WebSocket — Node.js\n× 1 replica"]
        end

        subgraph db["Base de données — Patroni HA"]
            PG0[("patroni-0\nLeader")]
            PG1[("patroni-1\nReplica")]
            PG2[("patroni-2\nReplica")]
            PG0 -->|réplication streaming| PG1 & PG2
        end

        subgraph monitoring["Monitoring"]
            PROM["Prometheus\nscrape 15s"]
            GRAF["Grafana\ndashboards"]
            PROM --> GRAF
        end

        FE -->|/api /auth /uploads| BE
        FE -->|/ws| WS
        BE -->|écriture| PG0
        app & db -->|métriques| PROM
    end

    subgraph gk["Namespace : gatekeeper-system"]
        GK["OPA GateKeeper\nadmission webhook"]
    end

    GK -. "audit chaque déploiement" .-> synkro

    Admin([Administrateur]) -->|kubectl port-forward :3000| GRAF
```

---

## Vue complète

```mermaid
graph TB
    %% ─── EXTERNE ───────────────────────────────────────────────
    Browser([Navigateur])
    LB["LoadBalancer\n195.15.195.73:80"]
    Admin([Administrateur\nport-forward])

    Browser -->|HTTP| LB
    Admin -->|kubectl port-forward\nlocalhost:3000| SVC_GRAF

    %% ─── NAMESPACE SYNKRO ──────────────────────────────────────
    subgraph synkro["Namespace : synkro"]

        %% ── Frontend ──
        subgraph frontendGroup["Frontend — 3 replicas (HA stateless)"]
            FE1[frontend-pod-1\nnginx :80]
            FE2[frontend-pod-2\nnginx :80]
            FE3[frontend-pod-3\nnginx :80]
        end
        SVC_FE["Service frontend\nLoadBalancer :80"]
        LB --> SVC_FE
        SVC_FE --> FE1 & FE2 & FE3

        %% ── Backend ──
        subgraph backendGroup["Backend — 3 replicas (HA stateless + JWT)"]
            BE1[backend-pod-1\nnginx+php-fpm :8000]
            BE2[backend-pod-2\nnginx+php-fpm :8000]
            BE3[backend-pod-3\nnginx+php-fpm :8000]
        end
        SVC_BE["Service backend\nClusterIP :8000"]
        FE1 & FE2 & FE3 -->|/api/*| SVC_BE
        SVC_BE --> BE1 & BE2 & BE3

        %% ── WebSocket ──
        WS[websocket-pod\nNode.js :3001]
        SVC_WS["Service websocket\nClusterIP :3001"]
        FE1 & FE2 & FE3 -->|/ws/*| SVC_WS
        SVC_WS --> WS

        %% ── PostgreSQL ──
        subgraph postgresGroup["PostgreSQL — Patroni HA (Spilo)"]
            PG0[(patroni-0\nLeader :5432)]
            PG1[(patroni-1\nReplica :5432)]
            PG2[(patroni-2\nReplica :5432)]
            PG0 -->|streaming replication| PG1 & PG2
        end
        SVC_PG["Service postgres-synkro\nClusterIP :5432\nspilo-role=master"]
        SVC_PGREP["Service postgres-replica\nClusterIP :5432\nspilo-role=replica"]
        BE1 & BE2 & BE3 -->|write| SVC_PG
        SVC_PG --> PG0
        SVC_PGREP --> PG1 & PG2

        %% ── Stockage ──
        subgraph storage["Volumes persistants (PVC)"]
            PVC_JWT[PVC jwt-keys\n100Mi]
            PVC_PG0[PVC patroni-data-0\n5Gi]
            PVC_PG1[PVC patroni-data-1\n5Gi]
            PVC_PG2[PVC patroni-data-2\n5Gi]
            PVC_PROM[PVC prometheus-data\n10Gi]
            PVC_GRAF[PVC grafana-data\n2Gi]
        end
        BE1 & BE2 & BE3 -.->|clés RSA JWT| PVC_JWT
        PG0 -.-> PVC_PG0
        PG1 -.-> PVC_PG1
        PG2 -.-> PVC_PG2

        %% ── Monitoring ──
        subgraph monitoring["Monitoring"]
            KSM[kube-state-metrics\n:8080\nétat pods/deployments]
            PG_EXP[postgres-exporter\n:9187\nmétriques PostgreSQL]
            PROM[Prometheus\n:9090\nscrape + stockage]
            GRAF[Grafana\n:3000\ndashboards]
        end

        KSM -->|métriques K8s| PROM
        PG_EXP -->|métriques PG| PROM
        PROM -->|datasource| GRAF
        PROM -.-> PVC_PROM
        GRAF -.-> PVC_GRAF
        PG_EXP -->|connexion| SVC_PG

        SVC_GRAF["Service grafana\nClusterIP :3000\n(port-forward uniquement)"]
        GRAF --- SVC_GRAF

        Kubelet([kubelet / cadvisor\nCPU + mémoire containers]) -->|scrape| PROM

        %% ── Config ──
        subgraph config["Configuration"]
            SEC[Secret synkro-secrets\nmdp BDD, JWT, OAuth, SMTP]
            CM[ConfigMap synkro-config\nAPP_ENV, URLs]
        end
        SEC -.->|injecté| BE1 & BE2 & BE3
        CM -.->|injecté| BE1 & BE2 & BE3
    end

    %% ─── NAMESPACE GATEKEEPER ──────────────────────────────────
    subgraph gatekeeper["Namespace : gatekeeper-system"]
        GK[OPA GateKeeper\nadmission webhook\ndryrun]
    end

    GK -.->|intercepte chaque\nnouvel déploiement| synkro

    %% ─── STYLES ─────────────────────────────────────────────────
    style Browser fill:#f0f4ff,stroke:#4a6cf7
    style LB fill:#4a6cf7,color:#fff,stroke:#3451b2
    style Admin fill:#f0f4ff,stroke:#4a6cf7
    style frontendGroup fill:#e8f5e9,stroke:#43a047
    style backendGroup fill:#e3f2fd,stroke:#1e88e5
    style postgresGroup fill:#fff3e0,stroke:#fb8c00
    style monitoring fill:#f3e5f5,stroke:#8e24aa
    style storage fill:#fafafa,stroke:#9e9e9e
    style config fill:#fce4ec,stroke:#e91e63
    style gatekeeper fill:#ffebee,stroke:#e53935
    style GK fill:#ffcdd2,stroke:#e53935
    style PG0 fill:#ffe0b2,stroke:#fb8c00
    style PG1 fill:#fff9c4,stroke:#f9a825
    style PG2 fill:#fff9c4,stroke:#f9a825
```

---

## Légende

| Symbole | Signification |
|---|---|
| `→` (flèche pleine) | Flux réseau actif (requêtes HTTP, scrape, réplication) |
| `-.->` (flèche pointillée) | Montage de volume ou injection de config |
| `Service ClusterIP` | Accessible uniquement en interne au cluster |
| `Service LoadBalancer` | Exposé sur une IP publique |
| `StatefulSet` | Pod avec identité stable et stockage persistant |
| `Deployment` | Pods stateless, scalables horizontalement |

## Ports clés

| Service | Port interne | Exposition externe |
|---|---|---|
| Frontend | 80 | `195.15.195.73:80` |
| Backend | 8000 | Non exposé (interne uniquement) |
| WebSocket | 3001 | Non exposé (via frontend) |
| PostgreSQL (Patroni leader) | 5432 | Non exposé |
| PostgreSQL (Patroni replicas) | 5432 | Non exposé |
| Prometheus | 9090 | Non exposé |
| Grafana | 3000 | `kubectl port-forward` localhost:3000 |
