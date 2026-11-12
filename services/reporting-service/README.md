# Reporting Service

Micro-service Laravel de l'architecture Patrimoine de l'État.

- **Base de données** : `reporting_db` (PostgreSQL, voir `docker/postgres/init.sql`)
- **API** : JSON uniquement, préfixe `/api`
- **Santé** : `GET /api/health`
- **Accès** : via la gateway Nginx — `/api/reports/*`

## Domaine

- **Tableau de bord consolidé** : volumes de biens par statut,
  valorisation nette comptable, demandes de maintenance par statut,
  dernière campagne d'inventaire — agrégation HTTP des services
  asset / maintenance / inventory (token propagé).
- **Exports CSV** (séparateur `;`, BOM UTF-8, français) :
  biens, demandes de maintenance, campagnes d'inventaire.
- **Journal d'audit** : chaque rapport/export généré est consigné
  (type, filtres, nb lignes, auteur, IP) — ajout seul.

## Endpoints (JWT requis)

| Méthode | Route | Rôle |
|---|---|---|
| GET | `/dashboard` | admin, asset_manager, accountant, auditor |
| GET | `/exports/assets.csv` | admin, asset_manager, accountant |
| GET | `/exports/maintenance.csv` | admin, asset_manager, accountant |
| GET | `/exports/inventory.csv` | admin, asset_manager, accountant |
| GET | `/audit-logs` | admin, auditor |

## Développement

```bash
cd services/reporting-service
composer install
php artisan test   # 11 tests
```

Variables : `ASSET_SERVICE_URL`, `MAINTENANCE_SERVICE_URL`,
`INVENTORY_SERVICE_URL`, `JWT_SECRET` identique aux autres services.
