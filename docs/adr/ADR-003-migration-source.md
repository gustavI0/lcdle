---
id: ADR-003
type: adr
title: Source de migration — backup SQL + wp-content local
status: Accepted
last-updated: 2026-04-14
date: 2026-04-14
deciders: [jerome]
consulted: []
---

# ADR-003 — Source de migration : backup SQL + wp-content local

## Contexte

Migration complète (articles, auteurs, médias, redirects) depuis `laculturedelecran.com`. Le propriétaire dispose localement d'un dump SQL WordPress récent + d'une copie du dossier `wp-content/`. Deux sources candidates : WXR (export XML natif WP) ou la base MySQL directe.

## Décision

**Source = backup SQL (MySQL) + copie `wp-content/` locale**, lus par les plugins Migrate API `migrate_source_sql` + filesystem.

## Alternatives considérées

- **WXR (XML)** : autonome, zéro dépendance DB. Mais perd certaines métadonnées (custom meta, taxonomie termes hiérarchiques, attachements liés), et rend le parsing de bodies + shortcodes plus fragile. Utile quand on n'a pas la DB — ici on l'a.
- **Accès live à la DB de prod** : rejeté — pollue la prod pendant les itérations de migration, couplage fort, risque de requêtes lourdes sur le site vivant.

## Conséquences

**Positives :**
- Fidélité maximale (tout ce que WP a en base est accessible).
- Itérations de migration zéro-risque (prod intacte).
- Reproductible : n'importe qui peut rejouer la migration sur un dump figé.

**Négatives / coûts :**
- Environnement de migration plus lourd à monter (MySQL + filesystem images).
- Le dump doit être rafraîchi avant la bascule finale pour capturer les dernières publications.

## Réversibilité

Élevée. Changer de source en cours de Phase 1 n'impacte que les plugins Migrate, pas le modèle cible.

## Fitness functions

- Un `drush migrate:status` sur l'environnement de migration liste toutes les migrations avec un nombre de `total` items > 0 (preuve que la source est lisible).
- Le dump source n'est jamais commité (vérifié par `.gitignore` : patterns `*.sql`, `*.sql.gz`, `wp-content/`).
