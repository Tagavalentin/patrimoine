# Inventory Service

Micro-service Laravel de l'architecture Patrimoine de l'État.

- **Base de données** : `inventory_db` (PostgreSQL, voir `docker/postgres/init.sql`)
- **API** : JSON uniquement, préfixe `/api`
- **Santé** : `GET /api/health`
- **Accès** : via la gateway Nginx — `/api/inventory/*`

## Domaine

Campagnes d'inventaire physique (`inventory_campaigns`) et lignes
de contrôle (`inventory_lines`, instantanés des biens).

Workflow : `draft → in_progress → completed`.

À la création, les biens de l'**asset-service** sont chargés comme
lignes (filtres optionnels `department_id` / `location_id`).
Chaque ligne passe `pending → found | missing | damaged` ;
à la clôture, les lignes non contrôlées deviennent `missing`.
La synthèse expose totaux et taux d'écarts.

## Endpoints (tous sous `/api/inventory`, JWT requis)

| Méthode | Route | Rôle |
|---|---|---|
| GET | `/campaigns` | liste paginée (filtres `status`, `search`) |
| POST | `/campaigns` | créer + charger les biens (admin/asset_manager) |
| GET | `/campaigns/{id}` | détail |
| DELETE | `/campaigns/{id}` | admin, brouillon uniquement |
| POST | `/campaigns/{id}/start` | admin/asset_manager |
| POST | `/campaigns/{id}/complete` | admin/asset_manager |
| GET | `/campaigns/{id}/lines` | lignes paginées (filtre `check_status`) |
| PATCH | `/campaigns/{id}/lines/{lineId}` | contrôle (admin/asset_manager/auditor) |
| GET | `/campaigns/{id}/summary` | totaux + taux d'écarts |

## Développement

```bash
cd services/inventory-service
composer install
php artisan test
```

Variables clés : `ASSET_SERVICE_URL`, `JWT_SECRET` identique aux
autres services.
