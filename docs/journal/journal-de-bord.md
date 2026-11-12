# Journal de bord

Journal des problèmes rencontrés et des décisions techniques prises pendant le
projet.

Format d'une entrée :

```
## AAAA-MM-JJ — Étape X : <titre>
- Problème : ...
- Hypothèses : ...
- Recherches : ...
- Solution retenue : ...
```

---

## 2026-11-11 — Étape 0 : Initialisation du projet

- Problème : le dossier de travail contenait un squelette Laravel 12 à la racine,
  hérité de l'initialisation du dépôt GitHub `Tagavalentin/patrimoine`.
- Hypothèses : il s'agissait soit d'un ancien essai, soit d'une base réutilisable.
- Recherches : inspection du dépôt → un seul commit « Initialisation du projet
  Patrimoine », aucun code custom (seuls les fichiers par défaut de
  `laravel new` sont présents).
- Solution retenue : conserver le squelette tel quel (décision du porteur du
  projet) et créer la structure micro-services autour de lui. Son sort sera
  tranché à l'étape 13 (frontend).

- Problème : Docker Desktop et le CLI `gh` ne sont pas installés sur la machine
  de développement.
- Hypothèses : Docker peut être disponible via Docker Desktop sans être dans le
  PATH du shell Git Bash.
- Recherches : `docker --version` absent en bash et en PowerShell.
- Solution retenue : le signaler comme prérequis bloquant pour l'étape 1 ;
  poursuite de l'étape 0 qui n'exige pas Docker. Les PR seront ouvertes via les
  URL de comparaison GitHub en l'absence de `gh`.

## 2026-11-11 — Étape 1 : Infrastructure Docker

- Problème : Docker Desktop absent de la machine → impossible d'exécuter
  `docker compose up -d` pour valider le critère d'acceptation.
- Hypothèses : l'utilisateur peut installer Docker Desktop entre-temps.
- Recherches : vérification `docker --version` en bash et PowerShell → absent.
- Solution retenue : écrire et valider statiquement toute l'infrastructure
  (syntaxe YAML vérifiée avec PyYAML), documenter les ports, et reporter la
  validation runtime à l'utilisateur.

- Problème : `docker-compose.yml` — un ancrage YAML (`x-service-nginx`) avec
  variables `${SERVICE_DIR}`/`${SERVICE_NAME}` ne fonctionne pas, car les
  variables d'environnement de service ne servent pas à l'interpolation des
  champs compose.
- Hypothèses : utiliser des ancres avec champs paramétrés par service.
- Recherches : documentation Docker Compose sur les ancres YAML et
  l'interpolation des variables.
- Solution retenue : ancres limitées aux champs strictement communs
  (image, réseaux, restart) ; volumes, ports, depends_on et environment
  écrits explicitement pour chaque service nginx.

## 2026-11-11 — Étape 3 : API Gateway

- Problème : les nginx internes des services exposaient les ports 8001–8005
  sur l'hôte, alors que la gateway doit être le point d'entrée unique.
- Hypothèses : garder les ports pour le débogage.
- Recherches : critère d'acceptation « joignables uniquement via la gateway ».
- Solution retenue : suppression des `ports:` des 5 nginx internes ; accès
  direct possible uniquement dans le réseau Docker (documenté dans le README).

- Problème : nginx ne peut pas être validé (`nginx -t`) sans Docker.
- Hypothèses : la configuration peut être relue manuellement.
- Recherches : vérification des directives utilisées (if/return, add_header
  always, limit_req_zone, proxy_pass avec URI).
- Solution retenue : revue manuelle attentive ; validation `docker compose
  up` + `nginx -t` reportée à l'utilisateur (Docker absent).

## 2026-11-11 — Étape 4 : auth-service

- Problème : choix du mécanisme JWT — `tymon/jwt-auth` (lourd, pensant
  sessions) vs implémentation dédiée.
- Hypothèses : un JWT simple HS256 suffit pour 5 services internes.
- Recherches : `firebase/php-jwt` (lib stable, sans dépendance Laravel).
- Solution retenue : `firebase/php-jwt` + `JwtService` maison dans un « kit
  JWT » copié dans chaque service (`shared/jwt/`), claims `sub/email/role`,
  middlewares `jwt.auth` et `role:` sans accès BDD.

- Problème : les autres services n'ont pas la table `users` de
  l'auth-service — un middleware qui recharge l'utilisateur en base
  renverrait toujours 401.
- Hypothèses : chaque service pourrait dupliquer la table users.
- Recherches : principe micro-services = pas de BDD partagée.
- Solution retenue : les services font confiance aux claims signés
  (`JwtUser`), seul l'auth-service connaît les utilisateurs.

- Problème : `JwtUser implements Authenticatable` → méthode abstraite
  `getAuthPasswordName()` manquante (contrat Laravel 12).
- Solution retenue : implémentation explicite de toutes les méthodes du
  contrat.

## 2026-11-11 — Étape 5 : asset-service, référentiels

- Problème : `$this->authorize()` (Gate) renvoyait 403 partout alors que le
  token était valide — Gate lit l'utilisateur via le guard `auth()`, pas via
  le user resolver de la requête.
- Hypothèses : `setUserResolver` suffisait pour les policies.
- Recherches : `Gate::userResolver` utilise `AuthManager`, distinct du
  resolver de `Request`.
- Solution retenue : `AuthenticateJwt` appelle aussi `auth()->setUser($jwtUser)`
  (ajout propagé au kit partagé `shared/jwt/` et à l'auth-service).

- Problème : `is_active` valait `null` dans la réponse de création (défaut
  SQL non reflété sur l'instance Eloquent).
- Solution retenue : `$attributes = ['is_active' => true]` sur les modèles.

## 2026-11-11 — Étape 6 : asset-service, gestion des biens

- Problème : génération du code métier `AST-AAAA-NNNN` sans collision en
  concurrence.
- Hypothèses : `count()+1` suffit.
- Recherches : table dédiée + `lockForUpdate` en transaction.
- Solution retenue : table `asset_counters` (année → dernier numéro)
  verrouillée dans `DB::transaction` — scalable et sans trou de concurrence.

- Problème : le statut ne doit pas être modifiable via le CRUD classique.
- Solution retenue : enum `AssetStatus` adossé à une table de transitions
  explicite, endpoint dédié `POST /assets/{id}/status`, historique complet
  dans `asset_status_histories` (from/to/motif/auteur).

## 2026-11-11 — Étape 7 : affectation et transfert

- Problème : erreurs de syntaxe PHP (`'.` orphelin, `new X()->response()`
  sans parenthèses) détectées par `php -l` avant exécution des tests.
- Solution retenue : `php -l` systématique sur les nouveaux fichiers ;
  parenthésage `(new Resource($model))->response()`.

- Problème : `responsible_user_id` référence un utilisateur de
  l'auth-service — pas de clé étrangère possible entre bases.
- Hypothèses : dupliquer la table users ou exposer une API users.
- Recherches : cohérence micro-services (pas de jointure inter-bases).
- Solution retenue : stocker l'id + un instantané `responsible_name` /
  `responsible_email` dans `assignments` — traçabilité sans couplage.

## 2026-11-11 — Étape 8 : maintenance-service

- Problème : le bien (`assets`) vit dans `asset_db`, inaccessible en SQL
  depuis `maintenance_db` — la demande doit quand même vérifier son
  existence et faire évoluer son statut.
- Hypothèses : duplication de la table assets dans maintenance_db.
- Recherches : communication service-à-service en REST avec propagation
  du JWT (exigence du cahier des charges).
- Solution retenue : `AssetServiceClient` (Http client, timeout 5 s,
  token propagé) + instantané `asset_code`/`asset_designation`/
  `asset_status` dans `maintenance_requests` pour l'affichage sans
  appel supplémentaire. Indisponibilité → 503 explicite, rollback
  transactionnel de la demande.

- Problème : `JwtUser` expose `id()`, `email()`, `role()` en méthodes —
  `$user->id` / `$user->role` provoquent des erreurs.
- Solution retenue : `getAuthIdentifier()` et `role()` partout ;
  documenté dans `shared/jwt` pour les prochains services.

- Problème : le statut de la demande n'était pas persisté lors du
  passage `requested → scheduled` (seule la validation de transition
  était faite).
- Solution retenue : chaque transition écrit explicitement le nouveau
  statut + horodatages (`scheduled_date`, `started_at`, `completed_at`).

## 2026-11-11 — Étape 9 : inventory-service

- Problème : l'inventaire physique doit lister les biens de
  l'asset-service sans jointure inter-bases.
- Hypothèses : requête SQL directe sur asset_db.
- Recherches : pagination de l'API assets (100/page, borne 50 pages).
- Solution retenue : `AssetServiceClient::listAssets` pagine toute
  l'API et chaque campagne stocke des **lignes-instantané**
  (`inventory_lines`) — l'inventaire reste cohérent même si les
  biens évoluent ensuite.

- Problème : la policy `CampaignPolicy` n'était pas découverte par
  Gate (convention `InventoryCampaignPolicy` attendue).
- Solution retenue : renommer la policy selon le modèle ;
  `check` autorise aussi l'auditeur (il constate, ne gère pas).

- Problème : index dupliqué `check_status` (inline + explicite)
  → erreur SQLite en test.
- Solution retenue : index inline conservé, explicite retiré.

## 2026-11-11 — Étape 10 : cessions et déclassements

- Problème : la sortie de patrimoine doit respecter le cycle de vie
  du bien (acquis → … → réformé → sorti) et interdire les sorties
  d'un bien encore affecté.
- Hypothèses : suppression directe du bien ou statut manuel.
- Recherches : workflow de validation (demande → approbation →
  réalisation) cohérent avec le RBAC (asset_manager demande,
  admin décide).
- Solution retenue : table `disposals` (référence DSP-AAAA-NNNN,
  type sale/donation/destruction/written_off, valeurs estimée et
  de cession) ; à l'approbation le bien passe `decommissioned`
  (historisé), à la réalisation `disposed` (terminal). Une seule
  demande active par bien ; rejet avec motif obligatoire.

- Problème : `assertJsonPath` compare strictement — `300000.0`
  attendu vs `300000` reçu (JSON n'a pas de distinction int/float).
- Solution retenue : comparer via `(float)` sur la valeur décodée.

## 2026-11-11 — Étape 11 : amortissement linéaire

- Problème : faut-il une table `depreciation_entries` pour figer les
  dotations annuelles ?
- Hypothèses : table obligatoire pour l'audit.
- Recherches : la valorisation est une projection déterministe de
  `acquisition_date` / `acquisition_value` / `useful_life_years`.
- Solution retenue : **calcul à la volée** (dotation mensuelle
  proratisée, plancher 0, durée de vie bornée) — pas de table, pas de
  risque de désynchronisation ; l'échéancier annuel est généré à la
  demande. Une table figée pourra être ajoutée si une clôture
  comptable annuelle est exigée (noté comme évolution).

- Problème : `diffInMonths` compte les mois pleins — une date
  d'acquisition au 1er et une valorisation au 31/12 de la 3e année
  donnent 35 mois, pas 36.
- Solution retenue : convention « mois pleins écoulés » documentée
  dans `DepreciationService` ; test aligné sur ce comportement.

## 2026-11-11 — Étape 12 : reporting-service

- Problème : le reporting doit agréger des données de 3 services
  sans base partagée.
- Hypothèses : chaque service expose ses données ; le reporting
  interroge les API.
- Recherches : endpoints de comptage par statut (`meta.total` des
  listes paginées) + endpoint dédié `/depreciation/summary`.
- Solution retenue : `ServiceClient` générique (token propagé,
  timeout 10 s, 503 si indisponible) ; dashboard = comptages par
  statut + valorisation + synthèse maintenance + dernière campagne
  d'inventaire. Exports CSV en français (séparateur `;`, BOM UTF-8
  pour Excel) sur toutes les pages.

- Problème : journaliser qui a généré quel rapport (exigence audit).
- Solution retenue : table `report_audit_logs` en ajout seul
  (`UPDATED_AT = null`) : type, format, filtres JSON, nb lignes,
  auteur, IP — consultable par admin/auditor.

- Problème : les callbacks `Http::fake` Laravel doivent retourner
  `Http::response()`, pas un tableau.
- Solution retenue : closures `function ($request) { return
  Http::response([...]); }` ; fixture dédiée quand `per_page=100`.

## 2026-11-11 — Étape 13 : frontend (socle + authentification)

- Problème : où placer le frontend sans toucher au squelette Laravel racine ?
- Hypothèses : le squelette racine doit rester intact ; un second projet
  Laravel est acceptable.
- Solution retenue : application dédiée `frontend/` (Laravel 12), le
  squelette racine est conservé tel quel. Le frontend ne parle qu'à la
  **gateway** (`GATEWAY_URL`) — jamais aux services directement.

- Problème : comment propager l'identité JWT dans une app Blade/Livewire ?
- Hypothèses : sessions Laravel classiques + appels serveur-à-serveur.
- Solution retenue : `AuthController` appelle `POST /api/auth/login` sur la
  gateway, stocke `jwt_token` + `jwt_user` (id/nom/email/rôle) en session ;
  `GatewayClient` injecte le token dans `Authorization: Bearer` à chaque
  appel ; middleware `frontend.auth` protège les pages ; logout = appel
  `/api/auth/logout` (best effort) + invalidation de session.

- Problème : le manifest Vite fait échouer les tests feature.
- Solution retenue : `npm run build` avant les tests ; `public/build`
  gitignoré — documenté dans le README frontend (requis aussi pour Docker).

- Problème : `SESSION_DRIVER=database` du squelette exige une table sessions.
- Solution retenue : `SESSION_DRIVER=file` — le frontend n'a pas besoin de BDD.

## 2026-11-11 — Étape 14 : frontend — écrans (dashboard + biens)

- Problème : pas de maquettes Figma disponibles.
- Solution retenue : charte sobre cohérente avec le layout de l'étape 13
  (cartes blanches, badges de statut colorés, tableaux) — validée par
  l'utilisateur.

- Problème : les composants Livewire full-page ne s'affichaient pas
  (layout vide).
- Recherches : `->layout()` de Livewire 4 injecte via `@component` +
  `{{ $slot }}` ; `->extends()` injecte via `@section('content')`.
- Solution retenue : `->extends('layouts.app')` — compatible avec le
  `@yield('content')` du layout Blade existant, sans le convertir en
  composant Blade.

- Problème : collision entre le paramètre de route `{asset}` et la
  propriété publique `$asset` du composant (Livewire hydrate les deux).
- Solution retenue : propriété renommée `$assetData`.

- Problème : `StatusPresenter` renvoyait des tableaux positionnels pour
  les maps et associatifs pour le défaut → `Undefined array key`.
- Solution retenue : toutes les entrées sont associatives
  `['label','class']`.

- Problème : les hooks `updated*` Livewire ne rechargeaient pas les
  données après changement de filtre.
- Solution retenue : `reload()` mutualisé (reset page + appel gateway).

## 2026-11-11 — Étape 15 : frontend — écrans maintenance

- Réalisé : liste des demandes (`/maintenance`, filtres statut/priorité,
  pagination), création (`/maintenance/nouvelle`, sélecteur de bien via
  l'asset-service), fiche (`/maintenance/{id}`) avec workflow complet :
  planifier → démarrer → interventions → terminer.

- Problème : Laravel `ControllerDispatcher` voit `method_exists($c,
  'callAction')` et dispatche vers `callAction` au lieu de `__invoke` —
  une méthode privée nommée `callAction` cassait le rendu des pages
  Livewire (`BadMethodCallException`).
- Solution retenue : renommée en `performAction` — tout nom de méthode
  sur un composant page doit éviter les noms réservés du dispatcheur.

- Problème : les actions Livewire (`schedule`, `start`, `complete`,
  `addIntervention`) doivent être réservées aux gestionnaires.
- Solution retenue : `canManage()` lit `session('jwt_user.role')`
  (admin/asset_manager) — boutons masqués pour les autres rôles et
  garde-fou dans `performAction` ; l'API reste l'autorité finale.

- Problème : les payloads de test `Http::fake` incomplets provoquaient
  `Undefined array key` dans Blade.
- Solution retenue : `?? null` sur les clés optionnelles des vues
  (dates, noms) — les resources API omettent les champs null.

## 2026-11-11 — Étape 16 : frontend — écrans inventaires + rapports

- Réalisé : inventaires (liste filtrable, création avec périmètre
  département/localisation, fiche avec synthèse des écarts, contrôle
  des lignes en ligne — found/missing/damaged + notes — et actions
  start/complete réservées aux gestionnaires) ; rapports (cartes
  d'export CSV + journal d'audit des exports pour admin/auditor).

- Problème : un `<a href>` ne peut pas envoyer le JWT en header —
  les exports CSV exigeaient un accès authentifié.
- Solution retenue : route web `/rapports/export/{type}`
  (`ExportController`) qui récupère le CSV auprès de la gateway avec
  le token de session et le sert en pièce jointe ; le type est
  validé contre une liste blanche et l'audit reste tracé côté
  reporting-service (acteur = claims JWT propagés).

- Problème : `GatewayClient` ne savait retourner que du JSON.
- Solution retenue : méthode `download()` retournant la
  `Http\Client\Response` brute pour le relais CSV ; `patch()` ajouté
  pour le contrôle des lignes d'inventaire.

- Problème : le contrôle des lignes doit être possible pour
  l'auditeur (il constate) mais pas start/complete.
- Solution retenue : deux gardes distincts `canManage()`
  (admin/asset_manager) et `canCheck()` (+ auditor), reflétant la
  policy `InventoryCampaignPolicy` du service.

## 2026-11-11 — Étape 17 : frontend — écran des affectations

- Réalisé : liste globale des affectations (`/affectations`, filtres
  actif/service, pagination), création (`/affectations/nouvelle`,
  sélecteur de biens limités au statut `acquired`), actions en ligne
  **Transférer** (service destination + responsable + motif) et
  **Restituer** (motif) sur les affectations actives — réservées
  admin/asset_manager.

- Problème : l'index des affectations ne propose pas de recherche par
  bien — le endpoint n'expose que asset_id/department_id/active.
- Solution retenue : filtres disponibles côté API uniquement ; la
  recherche fine reste possible depuis la fiche bien
  (`/biens/{id}` affiche l'historique complet des affectations).

## 2026-11-11 — Étape 18 : qualité, sécurité & documentation

- Réalisé : middleware `SecurityHeaders` frontend (nosniff,
  SAMEORIGIN, Referrer-Policy, Permissions-Policy, HSTS si HTTPS) +
  test ; documentation `docs/api/reference-api.md`,
  `docs/architecture/architecture-technique.md`,
  `docs/securite.md` (mesures + limites assumées + recommandations
  production).

- Problème : CSP stricte non posée côté frontend.
- Solution retenue : documentée comme limite — Livewire/Vite
  nécessitent une politique ajustée, à définir avant exposition.
  Les autres en-têtes sont déjà en place.

- Problème : la doc technique était éclatée entre README et journal.
- Solution retenue : 3 documents dédiés (API, architecture, sécurité)
  pour l'oral — chaque décision renvoie au journal de bord.

## 2026-11-11 — Étape 19 : UML, finance, guide & script de démo

- Réalisé : 4 diagrammes PlantUML (`cas-utilisation`, `classes`
  par domaine, `sequence-maintenance`, `deploiement`),
  `proposition-financiere.md` (≈6,8 M FCFA dev + ≈0,5–0,9 M/an
  exploitation), `guide-utilisateur.md`, `script-demo.md` 5 min
  avec comptes de démo.

- Problème : l'écran cession n'existe pas côté frontend (workflow
  API complet mais pas d'UI).
- Solution retenue : assumé dans le guide (« prévu version
  ultérieure ») — la démo couvre affectation → maintenance →
  inventaire → rapport, sans cession UI.

## 2026-11-11 — Audit pré-release : routage gateway assets

- Problème : la gateway ne gérait que le « double préfixe »
  (`/api/assets/assets/{id}` → `/api/assets/{id}`) ; les formes
  `/api/assets` exact et `/api/assets/{id}` retournaient 404,
  et `/api/assets/` trailing slash aussi.
- Hypothèses : bug de proxy_pass avec partie URI (`/api/`) qui
  écrase le préfixe ; les tests feature ne passent pas par nginx.
- Recherches : mapping frontend (toujours `/api/assets/assets/*`),
  routes natives asset-service (`assets/*` + ressources racines).
- Solution retenue : `location /api/assets` + `rewrite` — les
  ressources racines sont réécrites en `/api/{ressource}` tandis
  que `/api/assets/{id}/*` est relayé tel quel. Les deux formes
  fonctionnent désormais. Documenté dans reference-api.md.
- Correctif annexe : catch redondant `HttpException|\Throwable`
  simplifié dans AuthController frontend.
