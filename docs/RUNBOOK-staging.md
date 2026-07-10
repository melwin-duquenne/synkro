# Runbook — Mise en service du staging dev.synkro.ovh

## Pré-requis (manuel, une fois)
1. **DNS** : créer un enregistrement `A` `dev.synkro.ovh` → IP du VPS (152.228.131.12).
   Vérifier : `dig +short dev.synkro.ovh` renvoie l'IP. ⚠️ certbot est tout-ou-rien :
   sans ce DNS, l'émission du cert entière échoue.
2. **Google OAuth** : console Google Cloud → identifiants OAuth du projet Synkro →
   ajouter l'URI de redirection autorisée `https://dev.synkro.ovh/...` (même chemin de
   callback que la prod, host remplacé). Sinon : `redirect_uri_mismatch` sur staging.

## Ordre de déploiement (une fois le code mergé sur develop)
1. **Infra** (action manuelle) : onglet Actions → « Provision VPS » → scope **`stack`**,
   `dry_run` décoché. Applique : certbot (ajoute dev au SAN) + vhosts (pose synkro-dev).
   > Le scope `stack` couvre certbot (tag stack) ET vhosts (tag stack).
2. **App staging** : pousser sur `develop` (ou `calista`). La CI build les images
   `:staging` et déploie le stack staging via `deploy.yml -e deploy_env=staging`.

## Vérifications
- `https://dev.synkro.ovh` demande le Basic-auth (compte monitoring), puis sert l'app en TLS valide.
- `docker ps` sur le VPS montre `synkro_staging_*` ET `synkro_*` sans collision.
- Une donnée créée sur dev n'apparaît pas sur synkro.ovh (DB isolée).
- Un push `main` déploie la prod à l'identique (non-régression).

## Bascule pentest (plus tard)
- Passer `dev_modsec_engine: On` dans group_vars puis rejouer provision scope `web`
  pour éprouver le WAF sur le vhost dev.
- Allowlister l'IP testeuse dans UFW + fail2ban avant la campagne (défenses partagées
  avec la prod).
