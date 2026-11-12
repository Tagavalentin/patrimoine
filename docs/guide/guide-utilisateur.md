# Guide d'utilisation — Gestion du Patrimoine de l'État

## Accès

1. Démarrer la plateforme : `docker compose up -d` (puis
   `cd frontend && npm run build` une première fois).
2. Ouvrir `http://localhost:8080` (frontend) — la gateway API est
   sur `http://localhost` (port 80).
3. Se connecter avec un compte de démonstration (mot de passe
   `password` pour tous) :

| Compte | Rôle | Peut faire |
|---|---|---|
| `admin@patrimoine.local` | Administrateur | Tout, y compris utilisateurs, référentiels, exports, audit |
| `asset.manager@patrimoine.local` | Gestionnaire | Biens, affectations, maintenance, inventaires, exports |
| `service.head@patrimoine.local` | Responsable service | Consultation + demandes de maintenance |
| `accountant@patrimoine.local` | Comptable | Valorisation, cessions, exports |
| `auditor@patrimoine.local` | Auditeur | Lecture seule, contrôle des inventaires, journal d'audit |

## Navigation

La barre latérale donne accès aux écrans : **Tableau de bord**,
**Biens**, **Affectations**, **Maintenance**, **Inventaires**,
**Rapports**.

## Scénarios courants

### Consulter le patrimoine

- **Tableau de bord** : indicateurs consolidés (biens par statut,
  valeur nette comptable, maintenance ouverte, dernier inventaire).
- **Biens** : recherche par code/désignation, filtres statut et
  catégorie. Cliquez sur un bien pour sa fiche : informations,
  valorisation (amortissement), historique des affectations.

### Affecter un bien

1. **Affectations → Nouvelle affectation** : choisir un bien au
   statut « Acquis », le service destinataire, le responsable.
2. Pour transférer ou restituer : **Affectations**, puis
   « Transférer » / « Restituer » sur la ligne active
   (gestionnaire/admin uniquement).

### Maintenance

1. **Maintenance → Nouvelle demande** : choisir le bien, la priorité,
   décrire le problème.
2. Sur la fiche de la demande : **Planifier** (date + technicien),
   **Démarrer** (le bien passe « En maintenance »),
   **Ajouter une intervention** (description, coût, date),
   **Terminer** (le statut du bien est restauré).

### Inventaire physique

1. **Inventaires → Nouvelle campagne** : nom + périmètre
   (département et/ou localisation).
2. **Démarrer** la campagne : les lignes (biens concernés) sont
   générées.
3. Sur la fiche : **Contrôler** chaque ligne → Trouvé / Manquant /
   Endommagé + note (auditeur inclus).
4. **Clôturer** : les lignes non contrôlées deviennent « Manquantes » ;
   la synthèse affiche le taux d'écarts.

### Cession d'un bien

1. Côté API : `POST /api/assets/{id}/disposals` (demande),
   puis `approve` (admin) puis `complete` (admin, avec date et prix).
   Le passage UI de cette étape est prévu dans une version ultérieure ;
   l'API et les tests couvrent le workflow complet.

### Rapports

- **Rapports** : cartes d'export CSV (biens, maintenance,
  inventaires) — le fichier est téléchargé et **chaque export est
  journalisé** (visible dans « Journal des exports », admin/auditeur).

## Dépannage

| Symptôme | Cause probable | Solution |
|---|---|---|
| « Service indisponible » | Un microservice est arrêté | `docker compose ps` puis `docker compose up -d` |
| 401 après login | `JWT_SECRET` différent entre services | Vérifier `.env` racine, régénérer si besoin |
| Page blanche frontend | Assets non compilés | `cd frontend && npm run build` |
| Erreur 429 | Rate limiting gateway | Attendre quelques secondes |
