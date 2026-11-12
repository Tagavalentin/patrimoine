# Sécurité — Gestion du Patrimoine de l'État

## Authentification et autorisation

- **JWT HS256** signé par `auth-service`, secret partagé (`JWT_SECRET`)
  entre tous les services — à générer avec `openssl rand -base64 32`,
  jamais commité (`.env.example` contient un placeholder).
- Chaque service vérifie la **signature** localement : un token forgé
  ou expiré est rejeté (`401`) sans appel réseau.
- Claims : `sub` (id utilisateur), `email`, `role`. Les Policies Laravel
  s'appuient sur `JwtUser` injecté par `auth()->setUser()`.
- **RBAC** : matrice rôles ↔ permissions en base (`roles`, `permissions`,
  `permission_role`) côté auth-service ; les endpoints sensibles
  appliquent `role:` et/ou `authorize()` (Policies).
- Mot de passe : `bcrypt` (Laravel Hash). Login throttlé à 5 requêtes/min.

## Surface d'attaque

| Couche | Mesure |
|---|---|
| Gateway | Rate limiting (`api_limit` 30 req/s, `auth_limit` 5 req/s), timeouts proxy, `proxy_next_upstream off`, logs JSON avec request-id |
| Headers (gateway) | `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `server_tokens off` |
| Headers (frontend) | `SecurityHeaders` : nosniff, SAMEORIGIN, Referrer-Policy, Permissions-Policy, HSTS si HTTPS — CSP à définir en prod |
| Services | API-only (`ForceJsonResponse`), erreurs JSON homogènes, pas de routes web |
| Données | Soft deletes, historisation (statuts, affectations, interventions), audit des exports en ajout seul |
| Validation | FormRequests sur toutes les écritures (types, bornes, `Rule::exists`, messages FR) |
| Secrets | Aucun secret dans le code ; `.env*` ignorés par git (`**/.env`) |

## Frontend

- JWT **jamais exposé au navigateur** : session serveur uniquement ;
  `GatewayClient` est le seul à poser `Authorization: Bearer`.
- CSRF : protection native Laravel (`@csrf`, middleware web).
- XSS : échappement Blade `{{ }}` partout ; pas de `{!! !!}` sur données
  utilisateur.
- Autorisation UI indicative seulement (boutons masqués par rôle) —
  **l'API reste l'autorité finale** (un appel direct est rejeté 403).
- Exports CSV via relais serveur (`ExportController`) : liste blanche
  des types, Content-Disposition forcé, audit côté reporting.

## Limites connues (à assumer à l'oral)

1. **JWT non révocable** avant expiration (pas de blacklist ni de
   refresh token rotatif) — logout = no-op côté serveur.
2. `/api/refresh` exige un token encore valide — pas de fenêtre
   de grâce après expiration.
3. **CORS `*`** en local — restreindre à l'origine frontend en prod.
4. Pas de CSP frontend (Livewire/Vite) — à durcir avant exposition.
5. Secrets par défaut dans `docker-compose.yml`
   (`POSTGRES_PASSWORD:-patrimoine`) — **dev uniquement** ;
   variables `.env` obligatoires en déploiement.
6. Rate limiting par IP : un attaquant distribué contourne —
   suffisant pour le contexte formation.
7. Journal d'audit = exports uniquement ; l'audit métier est assuré
   par les historiques propres à chaque service.

## Recommandations production (non implémentées)

- HTTPS partout + HSTS, restriction CORS, CSP frontend.
- Rotation de `JWT_SECRET`, refresh tokens avec rotation + blacklist Redis.
- WAF / fail2ban sur la gateway, alerting sur les 4xx/5xx.
- Chiffrement des sauvegardes PostgreSQL, plan de reprise.
