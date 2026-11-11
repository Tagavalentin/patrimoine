# Inventory Service

Micro-service Laravel de l'architecture Patrimoine de l'État.

- **Base de données** : `inventory_db` (PostgreSQL, voir `docker/postgres/init.sql`)
- **API** : JSON uniquement, préfixe `/api`
- **Santé** : `GET /api/health`
- **Accès** : via la gateway Nginx (étape 3) — port dédié temporaire : voir `docker-compose.yml`

## Développement

```bash
docker compose up -d inventory-service
docker compose exec inventory-service php artisan test
```
