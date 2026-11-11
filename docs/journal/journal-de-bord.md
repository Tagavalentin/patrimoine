# Journal de bord

Journal des problèmes rencontrés et des décisions techniques prises pendant le
projet.

Format d'une entrée :

```
## AAAA-MM-JJ — Étape X : <titre>
- Problème : ...
- Hypothèses : ...
- Recherches : ...
- Solution retenue : ...
```

---

## 2026-11-11 — Étape 0 : Initialisation du projet

- Problème : le dossier de travail contenait un squelette Laravel 12 à la racine,
  hérité de l'initialisation du dépôt GitHub `Tagavalentin/patrimoine`.
- Hypothèses : il s'agissait soit d'un ancien essai, soit d'une base réutilisable.
- Recherches : inspection du dépôt → un seul commit « Initialisation du projet
  Patrimoine », aucun code custom (seuls les fichiers par défaut de
  `laravel new` sont présents).
- Solution retenue : conserver le squelette tel quel (décision du porteur du
  projet) et créer la structure micro-services autour de lui. Son sort sera
  tranché à l'étape 13 (frontend).

- Problème : Docker Desktop et le CLI `gh` ne sont pas installés sur la machine
  de développement.
- Hypothèses : Docker peut être disponible via Docker Desktop sans être dans le
  PATH du shell Git Bash.
- Recherches : `docker --version` absent en bash et en PowerShell.
- Solution retenue : le signaler comme prérequis bloquant pour l'étape 1 ;
  poursuite de l'étape 0 qui n'exige pas Docker. Les PR seront ouvertes via les
  URL de comparaison GitHub en l'absence de `gh`.

## 2026-11-11 — Étape 1 : Infrastructure Docker

- Problème : Docker Desktop absent de la machine → impossible d'exécuter
  `docker compose up -d` pour valider le critère d'acceptation.
- Hypothèses : l'utilisateur peut installer Docker Desktop entre-temps.
- Recherches : vérification `docker --version` en bash et PowerShell → absent.
- Solution retenue : écrire et valider statiquement toute l'infrastructure
  (syntaxe YAML vérifiée avec PyYAML), documenter les ports, et reporter la
  validation runtime à l'utilisateur.

- Problème : `docker-compose.yml` — un ancrage YAML (`x-service-nginx`) avec
  variables `${SERVICE_DIR}`/`${SERVICE_NAME}` ne fonctionne pas, car les
  variables d'environnement de service ne servent pas à l'interpolation des
  champs compose.
- Hypothèses : utiliser des ancres avec champs paramétrés par service.
- Recherches : documentation Docker Compose sur les ancres YAML et
  l'interpolation des variables.
- Solution retenue : ancres limitées aux champs strictement communs
  (image, réseaux, restart) ; volumes, ports, depends_on et environment
  écrits explicitement pour chaque service nginx.

## 2026-11-11 — Étape 3 : API Gateway

- Problème : les nginx internes des services exposaient les ports 8001–8005
  sur l'hôte, alors que la gateway doit être le point d'entrée unique.
- Hypothèses : garder les ports pour le débogage.
- Recherches : critère d'acceptation « joignables uniquement via la gateway ».
- Solution retenue : suppression des `ports:` des 5 nginx internes ; accès
  direct possible uniquement dans le réseau Docker (documenté dans le README).

- Problème : nginx ne peut pas être validé (`nginx -t`) sans Docker.
- Hypothèses : la configuration peut être relue manuellement.
- Recherches : vérification des directives utilisées (if/return, add_header
  always, limit_req_zone, proxy_pass avec URI).
- Solution retenue : revue manuelle attentive ; validation `docker compose
  up` + `nginx -t` reportée à l'utilisateur (Docker absent).

## 2026-11-11 — Étape 4 : auth-service

- Problème : choix du mécanisme JWT — `tymon/jwt-auth` (lourd, pensant
  sessions) vs implémentation dédiée.
- Hypothèses : un JWT simple HS256 suffit pour 5 services internes.
- Recherches : `firebase/php-jwt` (lib stable, sans dépendance Laravel).
- Solution retenue : `firebase/php-jwt` + `JwtService` maison dans un « kit
  JWT » copié dans chaque service (`shared/jwt/`), claims `sub/email/role`,
  middlewares `jwt.auth` et `role:` sans accès BDD.

- Problème : les autres services n'ont pas la table `users` de
  l'auth-service — un middleware qui recharge l'utilisateur en base
  renverrait toujours 401.
- Hypothèses : chaque service pourrait dupliquer la table users.
- Recherches : principe micro-services = pas de BDD partagée.
- Solution retenue : les services font confiance aux claims signés
  (`JwtUser`), seul l'auth-service connaît les utilisateurs.

- Problème : `JwtUser implements Authenticatable` → méthode abstraite
  `getAuthPasswordName()` manquante (contrat Laravel 12).
- Solution retenue : implémentation explicite de toutes les méthodes du
  contrat.

## 2026-11-11 — Étape 5 : asset-service, référentiels

- Problème : `$this->authorize()` (Gate) renvoyait 403 partout alors que le
  token était valide — Gate lit l'utilisateur via le guard `auth()`, pas via
  le user resolver de la requête.
- Hypothèses : `setUserResolver` suffisait pour les policies.
- Recherches : `Gate::userResolver` utilise `AuthManager`, distinct du
  resolver de `Request`.
- Solution retenue : `AuthenticateJwt` appelle aussi `auth()->setUser($jwtUser)`
  (ajout propagé au kit partagé `shared/jwt/` et à l'auth-service).

- Problème : `is_active` valait `null` dans la réponse de création (défaut
  SQL non reflété sur l'instance Eloquent).
- Solution retenue : `$attributes = ['is_active' => true]` sur les modèles.

## 2026-11-11 — Étape 6 : asset-service, gestion des biens

- Problème : génération du code métier `AST-AAAA-NNNN` sans collision en
  concurrence.
- Hypothèses : `count()+1` suffit.
- Recherches : table dédiée + `lockForUpdate` en transaction.
- Solution retenue : table `asset_counters` (année → dernier numéro)
  verrouillée dans `DB::transaction` — scalable et sans trou de concurrence.

- Problème : le statut ne doit pas être modifiable via le CRUD classique.
- Solution retenue : enum `AssetStatus` adossé à une table de transitions
  explicite, endpoint dédié `POST /assets/{id}/status`, historique complet
  dans `asset_status_histories` (from/to/motif/auteur).
