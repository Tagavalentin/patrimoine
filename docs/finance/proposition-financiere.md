# Proposition financière — Projet « Gestion du Patrimoine de l'État »

Document d'estimation pour l'évaluation orale. Montants indicatifs en
FCFA, à adapter au contexte de formation.

## Hypothèses de chiffrage

- Durée totale du projet : ~10 semaines (conception → recette).
- Équipe type : 1 chef de projet / architecte à mi-temps, 2 développeurs
  full-stack, 1 testeur à mi-temps.
- Coût journalier moyen chargé : 35 000 FCFA (développeur),
  50 000 FCFA (lead).

## Décomposition par lot

| Lot | Contenu | Jours-homme | Coût estimé |
|---|---|---|---|
| Analyse & conception | Cahier des charges, modélisation UML, choix d'architecture | 15 | 600 000 |
| Infra & socle | Docker Compose, gateway, auth/JWT, squelettes services | 20 | 900 000 |
| Asset-service | Référentiels, biens, affectations, cessions, amortissement | 35 | 1 500 000 |
| Maintenance-service | Workflow, interventions, synchro asset-service | 15 | 700 000 |
| Inventory-service | Campagnes, lignes, écarts, clôture | 15 | 700 000 |
| Reporting-service | Dashboard, exports CSV, journal d'audit | 12 | 550 000 |
| Frontend | Layout, écrans par domaine, exports | 30 | 1 300 000 |
| Qualité & doc | Tests, durcissement, documentation, UML, démo | 12 | 550 000 |
| **Total développement** | | **154 j-h** | **≈ 6 800 000 FCFA** |

## Coûts récurrents (exploitation)

| Poste | Estimation annuelle |
|---|---|
| Serveur (2 vCPU / 4 Go, mutualisé ou VPS) | 300 000 – 600 000 |
| Sauvegardes PostgreSQL (stockage + rétention) | 100 000 – 200 000 |
| Domaine + certificat TLS | 50 000 – 100 000 |
| **Total annuel** | **≈ 500 000 – 900 000 FCFA** |

## Points de vigilance budgétaire

- L'architecture microservices **augmente le coût d'infrastructure**
  (plusieurs conteneurs) mais reste dérisible sur un seul hôte —
  adapté à l'échelle d'un ministère.
- Redis est provisionné mais inutilisé : coût nul aujourd'hui,
  valeur ajoutée future (files, cache dashboard).
- Scénario minimal : tout sur un seul VPS ≈ 1,2 M FCFA/an —
  scénario de référence pour la démo.

## Valeur ajoutée à mettre en avant à l'oral

- **Traçabilité** : chaque transition de statut, affectation et
  intervention est historisée → auditabilité comptable.
- **Isolation** : une base par service → scalabilité et sécurité
  des données par domaine.
- **Réduction des pertes** : inventaires physiques avec taux
  d'écarts → détection du détournement/perte de matériel.
- **Valorisation** : amortissement linéaire intégré → tableau
  de bord décisionnel en temps réel.
