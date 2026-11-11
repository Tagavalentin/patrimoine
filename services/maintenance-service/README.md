# Maintenance Service

Micro-service Laravel de l'architecture Patrimoine de l'État.

- **Base de données** : `maintenance_db` (PostgreSQL, voir `docker/postgres/init.sql`)
- **API** : JSON uniquement, préfixe `/api`
- **Santé** : `GET /api/health`
- **Accès** : via la gateway Nginx — `/api/maintenance/*`

## Domaine

Demandes de maintenance (`maintenance_requests`) et interventions
(`interventions`, avec coût et date).

Workflow : `requested → scheduled → in_progress → completed`.

Au démarrage d'une intervention, le statut du bien passe à
`in_maintenance` dans l'**asset-service** (appel HTTP, token JWT
propagé) ; à la clôture, le statut antérieur est restauré
(`asset_previous_status`).

## Endpoints (tous sous `/api/maintenance`, JWT requis)

| Méthode | Route | Rôle |
|---|---|---|
| GET | `/requests` | liste paginée (filtres `status`, `asset_id`, `priority`) ; non-gestionnaires : leurs demandes uniquement |
| POST | `/requests` | créer une demande (vérifie le bien via asset-service) |
| GET | `/requests/{id}` | détail + interventions + coût total |
| DELETE | `/requests/{id}` | admin, demandes `requested` uniquement |
| POST | `/requests/{id}/schedule` | admin/asset_manager |
| POST | `/requests/{id}/start` | admin/asset_manager → bien `in_maintenance` |
| POST | `/requests/{id}/complete` | admin/asset_manager → statut restauré |
| POST | `/requests/{id}/interventions` | ajouter coût/intervention |
| GET | `/assets/{assetId}/summary` | synthèse maintenance d'un bien |

## Développement

```bash
cd services/maintenance-service
composer install
php artisan test
```

Variables clés : `ASSET_SERVICE_URL` (nginx interne de l'asset-service),
`JWT_SECRET` identique aux autres services.
