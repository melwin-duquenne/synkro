# Architecture Kubernetes — Synkro

```mermaid
graph TB
    %% ─── EXTERNE ───────────────────────────────────────────────
    Browser([Navigateur])
    LB["LoadBalancer\n195.15.195.73:80"]
    LBGrafana["LoadBalancer\n84.234.27.2:3000"]

    Browser -->|HTTP| LB
    Browser -->|HTTP| LBGrafana

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
        subgraph postgresGroup["PostgreSQL — Streaming Replication"]
            PG_PRI[(postgres-primary\nStatefulSet :5432\nécriture + lecture)]
            PG_REP[(postgres-replica\nStatefulSet :5432\nlecture seule)]
            PG_PRI -->|streaming replication\nwal_level=replica| PG_REP
        end
        SVC_PG["Service postgres-synkro\nClusterIP :5432"]
        BE1 & BE2 & BE3 -->|write| SVC_PG
        SVC_PG --> PG_PRI

        %% ── Stockage ──
        subgraph storage["Volumes persistants (PVC)"]
            PVC_JWT[PVC jwt-keys\n100Mi]
            PVC_PG[PVC postgres-data\n10Gi]
            PVC_REP[PVC postgres-replica\n5Gi]
            PVC_PROM[PVC prometheus-data\n10Gi]
            PVC_GRAF[PVC grafana-data\n2Gi]
        end
        BE1 & BE2 & BE3 -.->|clés RSA JWT| PVC_JWT
        PG_PRI -.-> PVC_PG
        PG_REP -.-> PVC_REP

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

        SVC_GRAF["Service grafana\nLoadBalancer :3000"]
        GRAF --- SVC_GRAF
        LBGrafana --> SVC_GRAF

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
    style LBGrafana fill:#4a6cf7,color:#fff,stroke:#3451b2
    style frontendGroup fill:#e8f5e9,stroke:#43a047
    style backendGroup fill:#e3f2fd,stroke:#1e88e5
    style postgresGroup fill:#fff3e0,stroke:#fb8c00
    style monitoring fill:#f3e5f5,stroke:#8e24aa
    style storage fill:#fafafa,stroke:#9e9e9e
    style config fill:#fce4ec,stroke:#e91e63
    style gatekeeper fill:#ffebee,stroke:#e53935
    style GK fill:#ffcdd2,stroke:#e53935
    style PG_PRI fill:#ffe0b2,stroke:#fb8c00
    style PG_REP fill:#fff9c4,stroke:#f9a825
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
| PostgreSQL primary | 5432 | Non exposé |
| Prometheus | 9090 | Non exposé |
| Grafana | 3000 | `84.234.27.2:3000` |
