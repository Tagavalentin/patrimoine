# Kit JWT partagé — authentification inter-services

Ce dossier contient la **source de référence** du mécanisme d'authentification
JWT utilisé par tous les micro-services. Chaque service possède sa propre copie
des fichiers (les applications Laravel sont indépendantes, sans dépendance
composer commune) — **les copies doivent rester identiques**.

## Fichiers du kit

| Fichier source (dans chaque service)              | Rôle |
|---------------------------------------------------|-----|
| `app/Support/Jwt/JwtService.php`                  | Émission et validation des tokens (HS256) |
| `app/Support/Jwt/JwtUser.php`                     | Principal authentifié reconstruit depuis les claims |
| `app/Http/Middleware/AuthenticateJwt.php`         | Middleware `jwt.auth` : valide le Bearer token |
| `app/Http/Middleware/EnsureRole.php`              | Middleware `role:` : autorisation par claim `role` |
| `config/jwt.php`                                  | Configuration (secret, TTL, issuer) |

## Contrat

- **Token** : JWT HS256, claims `iss`, `sub` (id utilisateur), `email`,
  `role` (slug), `iat`, `nbf`, `exp`, `jti`.
- **Secret partagé** : `JWT_SECRET` identique dans le `.env` de chaque service
  (voir `.env.example` racine). Ne jamais committer la vraie valeur.
- **Propagation** : le frontend envoie `Authorization: Bearer <token>` à la
  gateway ; la gateway transmet l'en-tête tel quel ; chaque service le valide
  localement — **aucun appel réseau à l'auth-service** n'est nécessaire.
- **Autorisation** : `->middleware('role:admin')` ou `'role:admin,accountant'`
  lit le claim `role` — aucune base de données requise.
- **auth-service** est le seul à émettre les tokens (`POST /api/login`,
  `/api/refresh`) et à connaître les utilisateurs en base.

## Enregistrer les middlewares (bootstrap/app.php)

```php
$middleware->alias([
    'jwt.auth' => \App\Http\Middleware\AuthenticateJwt::class,
    'role' => \App\Http\Middleware\EnsureRole::class,
]);
```

## Dépendance

`firebase/php-jwt` (composer) — version fixée dans chaque `composer.lock`.

## Évolution possible

Si le kit grossit (refresh token, blacklist, introspection), le migrer vers un
vrai package Composer local (`packages/jwt`) ou un service d'authentification
centralisé type OAuth2.
