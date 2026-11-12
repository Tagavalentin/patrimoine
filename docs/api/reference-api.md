# Référence de l'API — Gestion du Patrimoine de l'État

Toutes les API sont exposées via la **gateway** (`http://localhost` en local,
`GATEWAY_PORT` dans Docker). Préfixes :

| Préfixe gateway | Service | Routes internes |
|---|---|---|
| `/api/auth/*` | auth-service | `/api/*` |
| `/api/assets/*` | asset-service | `/api/*` |
| `/api/maintenance/*` | maintenance-service | `/api/*` |
| `/api/inventory/*` | inventory-service | `/api/*` |
| `/api/reports/*` | reporting-service | `/api/*` |

La gateway retire le préfixe de routage : `/api/assets/categories` →
asset-service `/api/categories`.

## Authentification

JWT **HS256** (`firebase/php-jwt`), claims : `sub`, `email`, `role`, `iss`,
`iat`, `exp`, `jti`. Durée de vie : `JWT_TTL` (minutes, défaut 60).
Le même `JWT_SECRET` doit être configuré dans tous les services.

```
Authorization: Bearer <token>
```

Rôles : `admin`, `asset_manager`, `service_head`, `accountant`, `auditor`.

Codes d'erreur courants : `401` non authentifié / token invalide ou expiré,
`403` rôle insuffisant, `404` ressource absente, `422` validation,
`429` rate limiting, `503` service interne indisponible.

---

## auth-service — `/api/auth`

| Méthode | Route | Accès | Description |
|---|---|---|---|
| POST | `/login` | public (throttle 5/min) | `{email, password}` → `{token_type, access_token, user}` |
| POST | `/refresh` | JWT valide | Nouveau token |
| POST | `/logout` | JWT | No-op (JWT sans état) |
| GET | `/me` | JWT | Profil courant `{data: user}` |
| GET/POST | `/users` | admin | Liste / création d'utilisateurs |
| GET/PUT/DELETE | `/users/{id}` | admin | Fiche / modification / suppression |

## asset-service — `/api/assets`

> La gateway accepte **deux formes** pour ce service :
> `/api/assets/{id}/...` (routes natives `assets/*` du service) et
> `/api/assets/{ressource}/...` pour les ressources racines
> (`categories`, `locations`, `departments`, `assignments`,
> `disposals`, `depreciation`) qui sont réécrites en `/api/{ressource}`.
> Les chemins ci-dessous sont relatifs au préfixe `/api/assets`.

### Référentiels (lecture : tout JWT ; écriture : admin)

| Méthode | Route | Description |
|---|---|---|
| GET | `/categories` | Catégories de biens (recherche, `is_active`) |
| GET | `/locations` | Sites/localisations (`type`, `city`) |
| GET | `/departments` | Services (`parent_id` hiérarchie) |
| POST/PUT/DELETE | `/{categories,locations,departments}[/{id}]` | CRUD admin |

### Biens

| Méthode | Route | Description |
|---|---|---|
| GET | `/assets` | Liste paginée : `search`, `status`, `category_id`, `location_id`, `department_id`, `acquisition_date_from/to`, `value_min/max`, `sort`, `direction`, `per_page` |
| POST | `/assets` | Création (admin/asset_manager) — code auto `AST-AAAA-NNNN` |
| GET | `/assets/{id}` | Fiche complète (+ `status_histories` si chargé) |
| PUT/PATCH | `/assets/{id}` | Modification (code et statut exclus) |
| DELETE | `/assets/{id}` | Soft delete (refusé si `disposed`) |
| POST | `/assets/{id}/status` | `{status, reason}` — machine à états |
| GET | `/assets/{id}/depreciation` | Valorisation `?date=YYYY-MM-DD` |
| GET | `/assets/{id}/depreciation/schedule` | Échéancier annuel |
| GET | `/depreciation/summary` | Synthèse portefeuille `?date=` |

### Affectations

| Méthode | Route | Description |
|---|---|---|
| GET | `/assignments` | Liste : `asset_id`, `department_id`, `responsible_user_id`, `active`, `per_page` |
| POST | `/assets/{id}/assign` | `{department_id, responsible_name/email?, start_date?, reason?}` |
| POST | `/assets/{id}/transfer` | Idem — clôt + nouvelle ligne |
| POST | `/assets/{id}/return` | `{end_date?, reason?}` — retour au parc |
| GET | `/assets/{id}/assignments` | Historique complet du bien |

### Cessions / déclassements

| Méthode | Route | Description |
|---|---|---|
| GET | `/disposals` | Liste : `status`, `disposal_type`, `asset_id` |
| POST | `/assets/{id}/disposals` | Demande `{disposal_type, reason, estimated_value?}` |
| GET | `/assets/{id}/disposals` | Historique du bien |
| GET | `/disposals/{id}` | Fiche |
| POST | `/disposals/{id}/approve` | admin — bien → `decommissioned` |
| POST | `/disposals/{id}/reject` | admin — `{reason}` obligatoire |
| POST | `/disposals/{id}/complete` | admin — `{disposal_date, sale_value?}` → `disposed` |
| DELETE | `/disposals/{id}` | admin, `requested` uniquement |

`disposal_type` : `sale`, `donation`, `destruction`, `written_off`.

## maintenance-service — `/api/maintenance`

| Méthode | Route | Description |
|---|---|---|
| GET | `/requests` | Liste : `status`, `asset_id`, `priority` — non-gestionnaires : leurs demandes |
| POST | `/requests` | `{asset_id, description (≥10), priority?}` — vérifie le bien via asset-service |
| GET | `/requests/{id}` | Fiche + interventions |
| DELETE | `/requests/{id}` | admin |
| POST | `/requests/{id}/schedule` | `{scheduled_date (≥ today), technician_name?}` |
| POST | `/requests/{id}/start` | Bien → `in_maintenance` |
| POST | `/requests/{id}/complete` | Statut antérieur du bien restauré |
| POST | `/requests/{id}/interventions` | `{description, cost?, performed_at, technician_name?}` |
| GET | `/assets/{assetId}/summary` | Synthèse maintenance d'un bien |

`status` : `requested` → `scheduled` → `in_progress` → `completed`.
`priority` : `low`, `normal`, `high`, `urgent`.

## inventory-service — `/api/inventory`

| Méthode | Route | Description |
|---|---|---|
| GET | `/campaigns` | Liste : `status`, `search`, `per_page` |
| POST | `/campaigns` | `{name (≥5), department_id?, department_name?, location_id?, location_name?}` — génère les lignes |
| GET | `/campaigns/{id}` | Fiche |
| DELETE | `/campaigns/{id}` | admin, brouillon |
| POST | `/campaigns/{id}/start` | `draft → in_progress` |
| POST | `/campaigns/{id}/complete` | pending → missing, puis `completed` |
| GET | `/campaigns/{id}/lines` | Lignes : `check_status`, `search`, `per_page` (≤200) |
| PATCH | `/campaigns/{id}/lines/{lineId}` | `{check_status: found|missing|damaged|pending, notes?}` — admin/asset_manager/auditor |
| GET | `/campaigns/{id}/summary` | Totaux, écarts, taux d'écarts % |

## reporting-service — `/api/reports`

| Méthode | Route | Accès | Description |
|---|---|---|---|
| GET | `/dashboard` | admin, asset_manager, accountant, auditor | Agrégats temps réel |
| GET | `/exports/assets.csv` | + accountant | CSV `;` + BOM UTF-8, journalisé |
| GET | `/exports/maintenance.csv` | idem | |
| GET | `/exports/inventory.csv` | idem | |
| GET | `/audit-logs` | admin, auditor | Journal des exports (ajout seul) |

## Formats de réponse

- Listes : `{data: [...], links: {...}, meta: {total, current_page, last_page, per_page}}`
- Fiches : `{data: {...}}`
- Erreurs : `{message, errors?}`
- Chaque service expose `GET /api/health` (public via la gateway : `/api/{service}/health`).

## Exemples

```bash
# Login
TOKEN=$(curl -s -X POST http://localhost/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@patrimoine.local","password":"password"}' \
  | python -c "import sys,json;print(json.load(sys.stdin)['access_token'])")

# Dashboard consolidé
curl http://localhost/api/reports/dashboard -H "Authorization: Bearer $TOKEN"

# Créer une demande de maintenance
curl -X POST http://localhost/api/maintenance/requests \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"asset_id":5,"description":"Écran tactile ne répond plus","priority":"high"}'

# Export CSV
curl -OJ http://localhost/api/reports/exports/assets.csv -H "Authorization: Bearer $TOKEN"
```
