---
id: ADR-001
type: adr
title: Choix de la plateforme — Drupal 11 Core
status: Accepted
last-updated: 2026-04-14
date: 2026-04-14
deciders: [jerome]
consulted: []
---

# ADR-001 — Choix de la plateforme : Drupal 11 Core

## Contexte

Le projet migre `laculturedelecran.com` (WordPress multi-contributeurs) vers une nouvelle plateforme, avec repositionnement produit : chaque contributeur reçoit un espace éditorial personnel, les chroniques deviennent un outil optionnel, ouverture future à IndieWeb / Fediverse. Scalable de 10 à beaucoup plus de contributeurs. Préservation SEO totale (redirects 301). L'équipe a une compétence Drupal établie.

## Décision

**Drupal 11 Core** (et non Drupal CMS / Starshot, et non Ghost, Hugo, 11ty).

## Alternatives considérées

- **Drupal CMS / Starshot** : preset opinionated pour cas standards (marketing, events). Incompatible avec certains modules multi-tenant dont on aura besoin (Domain, Group). Rejeté.
- **Ghost (self-hosted ou Pro)** : UX d'écriture excellente, ActivityPub natif. Modèle "multi-auteurs" ≠ "multi-espaces" — l'auteur est un attribut d'un post, pas un sous-site. Extensibilité limitée (Node, plugins rares). Rejeté pour les besoins à moyen terme.
- **Hugo / 11ty + Decap CMS** : excellent pour un site statique simple. Inadapté au workflow éditorial évolué (validation a priori), IndieWeb interactif (réception de Webmentions = besoin d'un backend dynamique), newsletters per-author. Rejeté.

## Conséquences

**Positives :**
- Modèle de contenu sur mesure, sans contorsion.
- IndieWeb + ActivityPub disponibles via modules contrib matures.
- Compétences équipe alignées.
- Écosystème Drupal large : Migrate API, Pathauto, Simplenews, Profile, Content Moderation, etc.
- Réversibilité des choix internes élevée (config YAML, modules remplaçables).

**Négatives / coûts :**
- UX d'écriture Drupal < Ghost/Medium par défaut — investissement requis sur CKEditor 5 + Gin + Paragraphs.
- Ops à maintenir (vs SaaS géré).
- Surface de code plus large à maintenir vs un site statique.

## Réversibilité

Élevé pour les choix internes (theme, modules, content model). Faible pour le choix de plateforme lui-même : changer Drupal → autre chose en cours de route = quasi-reboot. D'où l'importance de cette décision comme fondation.

## Fitness functions

- Test smoke CI : `drush status` répond sans erreur, site démarre.
- Temps d'installation local : `ddev start && ddev drush si` < 5 minutes.
- Couverture tests kernel sur le content model > 80% (Phase 0B+).
