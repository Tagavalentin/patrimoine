# Script de démonstration — 5 minutes

> Préparation : `docker compose up -d`, frontend buildé,
> `php artisan migrate --seed` sur auth-service (comptes ci-dessous).
> Navigateur ouvert sur `http://localhost:8080`.

## Comptes de démonstration (mot de passe : `password`)

| Rôle | Email |
|---|---|
| Administrateur | `admin@patrimoine.local` |
| Gestionnaire patrimoine | `asset.manager@patrimoine.local` |
| Auditeur | `auditor@patrimoine.local` |

## Déroulé chronométré

### 0:00–0:45 — Vision et architecture

- Montrer le README : **microservices Laravel + gateway + frontend**.
- `docker compose ps` : 1 PostgreSQL, 5 services × (php-fpm + nginx),
  gateway, frontend, Redis.
- Point clé oral : **une base par service**, JWT partagé, pas de
  jointure inter-services.

### 0:45–1:45 — Consultation (compte gestionnaire)

1. Login `asset.manager@patrimoine.local`.
2. **Tableau de bord** : KPIs — biens par statut, VNC, maintenance
  ouverte, dernier inventaire. *« Les chiffres viennent en temps réel
  des 3 services via la gateway. »*
3. **Biens** : recherche « portable », filtre statut, ouvrir une fiche
   → valorisation amortissement + historique d'affectation.

### 1:45–2:45 — Cycle de vie d'un bien

4. **Affectations → Nouvelle affectation** : affecter un bien acquis
   à un service.
5. **Maintenance → Nouvelle demande** sur ce bien → **Planifier** →
   **Démarrer** : montrer que le bien passe « En maintenance »
   (retourner sur sa fiche bien).
6. **Ajouter une intervention** avec coût → **Terminer** : le statut
   du bien est restauré automatiquement.

### 2:45–3:45 — Inventaire et écarts

7. (compte `auditor` ou gestionnaire) **Inventaires → Nouvelle
   campagne** sur un département → **Démarrer**.
8. **Contrôler** quelques lignes : marquer une « Manquante » avec note.
9. **Clôturer** : montrer la synthèse — taux d'écarts calculé.

### 3:45–4:15 — Traçabilité et rapports

10. **Rapports** : télécharger l'export CSV des biens.
11. Compte `admin` → **Rapports** : montrer le **journal d'audit** —
    l'export qui vient d'être généré y figure avec auteur et IP.
12. Rappel : chaque transition de statut est historisée
    (`asset_status_histories`, `assignments`, `interventions`).

### 4:15–5:00 — Points forts et limites

- **Points forts** : architecture défendable (isolation, JWT sans
  état, machines à états, audit), tests (100+), documentation complète.
- **Limites assumées** : JWT non révocable avant expiration, CORS `*`
  en local, pas de CSP — voir `docs/securite.md`.

## Plan B si un service est indisponible

- `docker compose restart <service>` ou montrer les tests
  `php artisan test` (36 frontend + services) comme preuve de
  fonctionnement.
