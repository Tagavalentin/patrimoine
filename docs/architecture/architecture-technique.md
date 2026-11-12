# Architecture technique — Gestion du Patrimoine de l'État

## Vue d'ensemble

Architecture **microservices** Laravel 12 / PHP 8.3 / PostgreSQL 16,
conteneurisée par Docker Compose, frontée par une API Gateway Nginx.

```
Navigateur
   │  HTTP (session + JWT côté serveur)
   ▼
frontend (Laravel 12 — Blade/Livewire/Alpine/Tailwind)
   │  GATEWAY_URL — seul point d'accès aux API
   ▼
API Gateway (Nginx)  ── rate limiting, CORS, headers, routage /api/{domaine}
   ├── /api/auth/*        → auth-service
   ├── /api/assets/*      → asset-service
   ├── /api/maintenance/* → maintenance-service
   ├── /api/inventory/*   → inventory-service
   └── /api/reports/*     → reporting-service
```

Chaque service = conteneur **php-fpm** + conteneur **nginx** dédié,
partageant l'image `docker/php/Dockerfile`. Un seul conteneur
PostgreSQL 16 héberge **une base par service** (`auth_db`, `asset_db`,
`maintenance_db`, `inventory_db`, `reporting_db`).

## Décisions structurantes

### 1. Isolation des bases de données

Aucune jointure ni clé étrangère inter-services. Quand un service a
besoin d'une donnée d'un autre, deux stratégies :

- **Appel HTTP synchrone** avec propagation du JWT (ex. :
  maintenance-service vérifie le bien auprès d'asset-service ;
  inventory-service charge les biens à la création d'une campagne ;
  reporting-service agrège à la volée).
- **Instantané** persisté localement (ex. : `asset_code`,
  `asset_designation`, `responsible_name` dans les lignes) pour
  l'historisation fidèle et la résilience en lecture.

### 2. Authentification par JWT sans état

- `auth-service` émet le token (HS256, `firebase/php-jwt`) avec les
  claims `sub`, `email`, `role`.
- Chaque service embarque une **copie du kit JWT**
  (`JwtService`, `JwtUser`, middlewares `jwt.auth`/`role:`) : la
  signature est vérifiée localement, **sans requête vers l'auth-service**
  ni table `users` locale. `JwtUser` implémente `Authenticatable` et
  est injecté via `auth()->setUser()`, ce qui rend les Policies et
  Gates Laravel opérationnelles.
- Le **frontend ne manipule jamais le JWT côté navigateur** : il est
  stocké en session serveur après `POST /api/auth/login` et propagé
  par `GatewayClient` à chaque appel.

### 3. Machines à états explicites

Enums PHP + tables de transitions (biens, demandes de maintenance,
campagnes d'inventaire, cessions). Chaque transition est historisée
(`asset_status_histories`, dates/heures du workflow).

### 4. Reporting sans base dédiée

Le reporting-service **agrège à la volée** via les API internes
(15 appels HTTP en parallèle logique pour le dashboard) : pas de table
de faits, pas de désynchronisation possible. Les exports CSV sont
journalisés en **ajout seul** (`report_audit_logs`) avec auteur, filtres
et nombre de lignes.

### 5. Frontend = client de la gateway uniquement

Le frontend ne connaît pas les URLs des services ; il appelle
`{GATEWAY_URL}/api/{domaine}/...`. Les exports CSV passent par un
relais serveur (`ExportController`) car le navigateur ne peut pas
émettre le header `Authorization` sur un téléchargement.

## Flux typiques

### Création d'une demande de maintenance

1. `POST /api/maintenance/requests` → maintenance-service
2. Vérification du bien : `GET /api/assets/{id}` (token propagé)
3. Instantané `asset_code/designation/status` en base locale
4. Réponse `201`

### Passage en maintenance

1. `POST /requests/{id}/start` → statut local `in_progress`,
   `asset_previous_status` mémorisé
2. `POST /api/assets/{id}/status {status: "in_maintenance"}`
   → asset-service (historique de statut tracé)
3. À la clôture : statut local `completed` **et** restauration du
   statut antérieur du bien — tout est rollbacké (503) si
   l'asset-service est indisponible.

## Choix techniques notables

| Choix | Alternative écartée | Justification |
|---|---|---|
| `firebase/php-jwt` | tymon/jwt-auth, Sanctum | Léger, sans dépendance session, suffisant service-à-service |
| Kit JWT dupliqué (5 services) | Package composer partagé | Pas de couplage de version, Docker simple |
| Claims `role` (pas de permissions dans le token) | Permissions embarquées | 5 rôles suffisent ; noté comme évolution |
| Machine à états = enum + table | Package workflow | Lisible, testable, 5 états |
| `asset_counters` + `lockForUpdate` | `count()+1` | Génération de code sans collision en concurrence |
| Instantané (snapshots) | Jointures live | Historisation fidèle, résilience, découplage |
| Livewire 4 | Inertia/Vue SPA | Rendu serveur, pas de double API front, à itérer |
| Amortissement calculé | Table `depreciation_entries` | Projection déterministe, pas de désync (évolution notée) |
| Redis présent mais inutilisé | Files d'attente | Réservé pour événements/snapshots futurs |

## Limites assumées (à assumer à l'oral)

- Rate limiting par IP côté gateway (30 req/s, 5 req/s sur `/auth`).
- CORS `*` en local — à restreindre à l'origine frontend en production.
- Logout = no-op serveur (JWT non révocable avant expiration).
- `/api/refresh` exige un token encore valide.
- Pas de CSP côté frontend (Livewire/Vite à ajuster) — les autres
  en-têtes de sécurité sont posés par `SecurityHeaders`.
- Dashboard = ~15 appels internes : acceptable en démo, cache Redis
  possible si volumétrie.
