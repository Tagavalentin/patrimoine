# ============================================================
# Makefile — Gestion du projet Patrimoine de l'État
# Usage : make <cible>   (nécessite Docker Desktop + Docker Compose v2)
# ============================================================

COMPOSE := docker compose
SERVICE ?= all

.PHONY: up down build logs ps migrate seed test shell clean

## Démarre tous les conteneurs (postgres, redis, gateway, services)
up:
	$(COMPOSE) up -d

## Arrête tous les conteneurs (les volumes sont conservés)
down:
	$(COMPOSE) down

## Arrête et supprime les volumes (⚠️ perte des données)
clean:
	$(COMPOSE) down -v

## Reconstruit les images sans cache
build:
	$(COMPOSE) build --no-cache

## Affiche les logs (SERVICE=<nom> pour un seul service)
logs:
	$(COMPOSE) logs -f $(SERVICE)

## Liste l'état des conteneurs
ps:
	$(COMPOSE) ps

## Exécute les migrations sur tous les services Laravel
migrate:
	$(COMPOSE) exec auth-service php artisan migrate --force
	$(COMPOSE) exec asset-service php artisan migrate --force
	$(COMPOSE) exec maintenance-service php artisan migrate --force
	$(COMPOSE) exec inventory-service php artisan migrate --force
	$(COMPOSE) exec reporting-service php artisan migrate --force
	$(COMPOSE) exec frontend php artisan migrate --force || true

## Exécute les seeders de démonstration
seed:
	$(COMPOSE) exec auth-service php artisan db:seed --force
	$(COMPOSE) exec asset-service php artisan db:seed --force
	$(COMPOSE) exec maintenance-service php artisan db:seed --force
	$(COMPOSE) exec inventory-service php artisan db:seed --force
	$(COMPOSE) exec reporting-service php artisan db:seed --force

## Lance les tests PHPUnit de tous les services
test:
	$(COMPOSE) exec auth-service php artisan test
	$(COMPOSE) exec asset-service php artisan test
	$(COMPOSE) exec maintenance-service php artisan test
	$(COMPOSE) exec inventory-service php artisan test
	$(COMPOSE) exec reporting-service php artisan test

## Ouvre un shell dans un service (SERVICE=<nom>, défaut auth-service)
shell:
	$(COMPOSE) exec $(or $(SERVICE),auth-service) sh
