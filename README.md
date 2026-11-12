# Gestion du Patrimoine de l'État

Solution numérique de gestion du patrimoine de l'État, développée en architecture
**micro-services** avec **Laravel**, **PostgreSQL** et **Docker**.

Ce projet constitue le support d'une présentation orale : la qualité du code, la
propreté de l'architecture et la justification des choix techniques sont aussi
importantes que les fonctionnalités elles-mêmes.

## Architecture

```
                 ┌──────────────┐
   Navigateur ──▶│   Gateway    │  Nginx — point d'entrée unique
                 │  (Nginx)     │  /api/auth, /api/assets, ...
                 └──────┬───────┘
        ┌───────────────┼────────────────────┬──────────────┐
        ▼               ▼                    ▼              ▼
 ┌────────────┐  ┌────────────┐   ┌──────────────────┐  ┌───────────┐
 │ auth-      │  │ asset-     │   │ maintenance-     │  │ reporting │
 │ service    │  │ service    │   │ service          │  │ -service  │
 │ (auth_db)  │  │ (asset_db) │   │ (maintenance_db) │  │ (report_db│
 └────────────┘  └────────────┘   └──────────────────┘  └───────────┘
        │               │                    │              │
        ▼               ▼                    ▼              ▼
 ┌────────────────────────────────────────────────────────────────┐
 │                    PostgreSQL (une base par service)           │
 │  auth_db · asset_db · maintenance_db · inventory_db · reporting_db │
 └────────────────────────────────────────────────────────────────┘
```

- **Un conteneur PostgreSQL**, une base de données par service (isolation des données).
- **API Gateway** Nginx : seul point d'entrée, routage par préfixe, headers de
  sécurité, rate limiting.
- **Authentification JWT** propagée entre services (claims : utilisateur, rôle).
- **Frontend unique** (Blade + Livewire + Alpine.js + Tailwind CSS) consommant
  uniquement la gateway.
- Communication inter-services en HTTP REST avec propagation du token.

## Structure du dépôt

```
patrimoine-etat/
├── docs/            # Analyse, UML, compétences, finance, guide utilisateur, journal
├── design/          # Captures et éléments de design (maquettes Figma)
├── gateway/         # Configuration Nginx de l'API Gateway
├── frontend/        # Application Laravel front (Blade + Livewire)
├── services/        # Les 5 micro-services Laravel
│   ├── auth-service/
│   ├── asset-service/
│   ├── maintenance-service/
│   ├── inventory-service/
│   └── reporting-service/
├── docker/          # Dockerfiles, init.sql PostgreSQL
├── docker-compose.yml
├── Makefile
├── .env.example
└── README.md
```

> Note : une application Laravel « squelette » subsiste à la racine du dépôt
> (héritée de l'initialisation du projet) et est volontairement conservée.
> L'application frontend vit dans `frontend/` (Laravel 12, Blade + Livewire +
> Alpine + Tailwind) et consomme l'API **exclusivement via la gateway**.

## Acteurs et rôles

| Rôle              | Responsabilités                                             |
|-------------------|-------------------------------------------------------------|
| `admin`           | Utilisateurs, rôles, référentiels                           |
| `asset_manager`   | Biens, affectations, cessions                               |
| `service_head`    | Biens de son service, demandes de transfert et maintenance  |
| `accountant`      | Valeurs, amortissements, validation des cessions            |
| `auditor`         | Lecture seule, journal d'audit                              |

## Prérequis

- **Docker Desktop** (avec Docker Compose v2)
- **Make** (optionnel — les commandes `docker compose` restent utilisables directement)
- **Git**
- PHP 8.3+ et Composer (uniquement pour le développement local hors Docker)

## Démarrage rapide

```bash
# 1. Copier la configuration
git clone <url-du-depot> patrimoine-etat
cd patrimoine-etat
cp .env.example .env

# 2. Démarrer l'infrastructure
docker compose up -d

# 3. Suivre les logs
docker compose logs -f

# 4. Lancer les migrations (une fois les services initialisés)
make migrate
make seed
```

## Ports exposés

| Port hôte | Service                | Usage                              |
|-----------|------------------------|------------------------------------|
| 80        | gateway                | **Point d'entrée unique de l'API** |
| 5432      | postgres               | Base de données                   |
| 6379      | redis                  | Cache / événements                 |
| 5050      | pgadmin (profil admin) | `docker compose --profile admin up -d` |

Les nginx internes des services (ports 8001–8005) ne sont **plus exposés** sur
l'hôte : tout le trafic passe par la gateway. Ils restent joignables à
l'intérieur du réseau Docker pour le débogage (`docker compose exec gateway
wget -qO- http://asset-service-nginx/api/health`).

## Commandes utiles

| Commande        | Action                                        |
|-----------------|-----------------------------------------------|
| `make up`       | Démarre tous les conteneurs                   |
| `make down`     | Arrête tous les conteneurs                    |
| `make build`    | Reconstruit les images                        |
| `make logs`     | Affiche les logs de tous les services         |
| `make migrate`  | Exécute les migrations sur tous les services  |
| `make seed`     | Exécute les seeders de démonstration          |
| `make test`     | Lance la suite de tests PHPUnit               |

## Plan de réalisation

Le projet est découpé en 22 étapes (0 à 21), chacune livrée sur une branche
dédiée avec Pull Request vers `develop`. Le détail figure dans le brief de
mission et le suivi est tenu dans `docs/`.

## Documentation

- `docs/analyse/` — cahier des charges et analyse fonctionnelle
- `docs/uml/` — diagrammes PlantUML
- `docs/competences/suivi-competences.md` — suivi des compétences mobilisées
- `docs/journal/journal-de-bord.md` — journal des problèmes et décisions
- `docs/guide/` — guide d'utilisation
- `docs/fiche-technique.md` — documentation technique (étape 18)
