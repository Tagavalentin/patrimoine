# Frontend — Gestion du Patrimoine de l'État

Application web Laravel 12 (Blade + Livewire + Alpine.js + Tailwind CSS v4)
consommant les microservices **exclusivement via l'API Gateway**.

## Prérequis

- PHP 8.2+ (local) ou Docker Compose
- Node.js 20+ (build des assets Vite/Tailwind)

## Installation

```bash
cd frontend
composer install
npm install
cp .env.example .env   # puis php artisan key:generate
npm run build          # obligatoire : manifest Vite (aussi requis pour Docker)
```

## Configuration

| Variable | Rôle |
|---|---|
| `GATEWAY_URL` | URL de l'API Gateway (`http://localhost` en local, `http://gateway` dans Docker Compose) |
| `SESSION_DRIVER` | `file` (pas de BDD côté frontend) |

Aucune base de données n'est utilisée : toute la donnée vient des API.

## Authentification

1. `POST /login` → `AuthController@login` appelle `POST {GATEWAY_URL}/api/auth/login`.
2. En cas de succès, le JWT et le profil (`id`, `name`, `email`, `role`) sont stockés en session.
3. `App\Support\GatewayClient` propage le token (`Authorization: Bearer …`) sur chaque appel API.
4. Le middleware `frontend.auth` redirige vers `/login` sans session valide.
5. `POST /logout` appelle `/api/auth/logout` (best effort) puis invalide la session.

## Lancement

```bash
# Dev
php artisan serve            # http://localhost:8000

# Docker (depuis la racine du dépôt)
npm run build                # dans frontend/ d'abord
docker compose up -d         # frontend sur http://localhost:8080 (FRONTEND_PORT)
```

## Tests

```bash
php artisan test
```

Les tests `Feature/AuthTest` simulent la gateway avec `Http::fake` —
aucun service réel requis.

## Structure

```
app/
├── Http/
│   ├── Controllers/AuthController.php   # login/logout via gateway
│   └── Middleware/EnsureFrontendAuth.php
├── Livewire/Dashboard.php               # tableau de bord (socle)
└── Support/GatewayClient.php            # client HTTP unique vers la gateway
resources/views/
├── layouts/app.blade.php                # layout principal (sidebar + topbar)
├── auth/login.blade.php                 # écran de connexion
└── livewire/dashboard.blade.php
```
