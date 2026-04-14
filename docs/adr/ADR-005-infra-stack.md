---
id: ADR-005
type: adr
title: Stack infrastructure — PHP 8.4, PostgreSQL 16, Docker, OVH, GitHub Actions
status: Accepted
last-updated: 2026-04-14
date: 2026-04-14
deciders: [jerome]
consulted: []
---

# ADR-005 — Stack infrastructure

## Contexte

Le propriétaire héberge sur un VPS OVH. Le projet démarre à ~10 contributeurs avec une cible de scalabilité à plus. Pas de contrainte de budget managé (l'ops est internalisé).

## Décision

- **Langage** : PHP 8.4.
- **Base de données** : PostgreSQL 16.
- **Cache** : Redis (module `redis` pour cache bins + sessions).
- **Serveur web** : Nginx + PHP-FPM.
- **CDN** : Cloudflare (tier gratuit au MVP), purge via modules `purge` + `cloudflare`.
- **Dev local** : DDEV.
- **Prod** : VPS OVH + Docker Compose (services : nginx, php-fpm, postgres, redis).
- **CI/CD** : GitHub Actions.
- **Config** : Drupal Config Management (YAML en git).

## Alternatives considérées

- **MariaDB** au lieu de PostgreSQL : compatibilité équivalente pour Drupal 11. PostgreSQL préféré pour JSONB robuste, full-text natif, types stricts.
- **PHP 8.3** : stable aussi. PHP 8.4 choisi pour property hooks, asymmetric visibility, `#[\Deprecated]` — utiles dans les modules custom OOP.
- **Platform.sh / Pantheon (managé)** : zéro ops mais coût mensuel significatif (~50€+/mois). Rejeté — l'équipe maîtrise l'ops OVH.
- **GitLab CI** : équivalent fonctionnellement. GitHub Actions choisi car le repo vit sur GitHub.

## Conséquences

**Positives :**
- Coût d'hébergement maîtrisé.
- Maîtrise totale de la stack.
- Docker Compose = parité dev/prod.
- GitHub Actions = intégration naturelle avec le repo + tier gratuit généreux.

**Négatives / coûts :**
- Ops à assurer : mises à jour OS, sauvegardes, monitoring, TLS renewal.
- Pas de preview environments gratuits (contrairement à Platform.sh).

## Réversibilité

Élevée pour Redis/Nginx/PHP-FPM (remplaçables sans impact métier). Moyenne pour PostgreSQL → MySQL (migration de schéma). Élevée pour CI/CD (fichiers YAML de workflows portables).

## Fitness functions

- L'environnement local DDEV et l'image Docker de prod utilisent la même version PHP.
- Le backup PostgreSQL quotidien est testé par une restauration trimestrielle (procédure documentée).
- La CI tourne en < 5 minutes (objectif qualité DX).
