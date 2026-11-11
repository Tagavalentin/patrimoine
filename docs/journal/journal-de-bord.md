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
