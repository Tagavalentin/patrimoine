# Asset Service

Micro-service Laravel de l'architecture Patrimoine de l'État.

- **Base de données** : `asset_db` (PostgreSQL, voir `docker/postgres/init.sql`)
- **API** : JSON uniquement, préfixe `/api`
- **Santé** : `GET /api/health`
- **Accès** : via la gateway Nginx — `/api/assets/*`

## Domaine

- **Référentiels** : catégories, localisations, départements (hiérarchie).
- **Biens** : code `AST-AAAA-NNNN` généré, cycle de vie
  `acquired → assigned / in_maintenance / decommissioned → disposed`,
  historique des statuts.
- **Affectations** : affecter / transférer / restituer, historique
  des périodes.
- **Sorties de patrimoine** (`disposals`) : demande (admin/asset_manager)
  → approbation admin (bien → `decommissioned`) → réalisation admin
  (bien → `disposed`, prix de cession possible) ; rejet avec motif.

## Endpoints principaux (JWT requis)

`/categories`, `/locations`, `/departments`, `/assets` (CRUD +
`POST /assets/{id}/status`), `/assignments`, `/disposals`
(détail dans `routes/api.php`).

## Développement

```bash
cd services/asset-service
composer install
php artisan test   # 54 tests
```
