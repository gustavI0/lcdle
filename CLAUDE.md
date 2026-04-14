# CLAUDE.md — La Culture de l'Écran

Ce fichier est lu automatiquement par Claude Code au démarrage de chaque session sur ce projet. Il contient le contexte minimum indispensable.

## Projet

Migration de `laculturedelecran.com` (WordPress multi-contributeurs) vers une nouvelle plateforme Drupal 11 positionnée comme "espaces éditoriaux personnels" (type Medium).

Source unique de vérité pour le design : [`docs/superpowers/specs/2026-04-14-lcdle-migration-design.md`](docs/superpowers/specs/2026-04-14-lcdle-migration-design.md).

## Stack

- Drupal 11 Core / PHP 8.4 / PostgreSQL 16 / Redis / Nginx
- DDEV en local, Docker Compose + VPS OVH en prod
- Composer-first, Drush 13, Config Management en git
- GitHub Actions (CI)

## Conventions critiques

- **Code en anglais** : machine names (content types, fields, vocabularies, modules), identifiants, variables, commentaires. UI labels et contenu éditorial en français.
- **OOP moderne** : services + DI, attributes PHP (`#[Hook]`, `#[FieldType]`…), pas de hook procédural sauf nécessité. PHP 8.4 features (property hooks, asymmetric visibility, readonly) utilisées.
- **TDD strict** : Red → Green → Refactor → Review pour tout code custom.
- **Tidy First** : séparer comportement et structure, jamais dans le même commit.
- **Config en YAML versionné** : toute modification de la structure Drupal passe par `drush config:export` et un commit.
- **Commits orientés "pourquoi"** : le quoi est lisible dans le diff.

## Structure du repo

| Chemin | Rôle |
|---|---|
| `docs/superpowers/specs/` | Specs de design (une par grand sujet) |
| `docs/superpowers/plans/` | Plans d'implémentation par phase |
| `docs/adr/` | Architecture Decision Records |
| `docs/domain/glossary.md` | Glossaire projet (vocabulaire obligatoire) |
| `web/modules/custom/` | Modules custom (préfixe `lcdle_`) |
| `web/themes/custom/` | Theme(s) custom |
| `config/sync/` | Config Drupal exportée (YAML) |

## Phases

1. **Phase 0A — Foundations** (en cours) : scaffolding, CI, docs.
2. **Phase 0B — Content model** : module `lcdle_core` (content types, vocabs, rôles, workflow).
3. **Phase 0C — Custom entities** : modules `lcdle_contributor` (Profile) + `lcdle_newsletter`.
4. **Phase 1 — Migration de données** : Migrate API, redirects 301.
5. **Phase 2 — Theming + lancement**.
6. **Phase 3 — Newsletter + onboarding ouvert**.
7. **Phase 4 — IndieWeb + Fediverse**.

## Ressources

- Spec : [`docs/superpowers/specs/2026-04-14-lcdle-migration-design.md`](docs/superpowers/specs/2026-04-14-lcdle-migration-design.md)
- Glossaire : [`docs/domain/glossary.md`](docs/domain/glossary.md)
- ADR : [`docs/adr/`](docs/adr/)
- Plan en cours : [`docs/superpowers/plans/`](docs/superpowers/plans/)

## Ce que Claude ne fait PAS sur ce projet

- Trancher un arbitrage métier à la place du propriétaire.
- Valider un livrable (humain uniquement).
- Exécuter une commande destructive en prod (`drush sql:drop`, suppression de fichiers…) sans confirmation explicite.
- Ignorer le glossaire en introduisant des synonymes non déclarés.
