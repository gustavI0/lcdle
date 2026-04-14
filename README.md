# La Culture de l'Écran — Plateforme Drupal 11

Migration et refonte de `laculturedelecran.com` (WordPress → Drupal 11) — plateforme éditoriale multi-contributeurs.

## Documentation

- **Spec de design** : [`docs/superpowers/specs/2026-04-14-lcdle-migration-design.md`](docs/superpowers/specs/2026-04-14-lcdle-migration-design.md)
- **Plans d'implémentation** : [`docs/superpowers/plans/`](docs/superpowers/plans/)
- **ADR** : [`docs/adr/`](docs/adr/)
- **Glossaire** : [`docs/domain/glossary.md`](docs/domain/glossary.md)

## Quickstart local

Pré-requis : Docker + DDEV ≥ 1.22.

    ddev start
    ddev composer install
    ddev drush site:install minimal --existing-config -y
    ddev launch

## Stack

- Drupal 11 / PHP 8.4 / PostgreSQL 16
- DDEV (local), Docker + OVH (prod)
- GitHub Actions (CI)
