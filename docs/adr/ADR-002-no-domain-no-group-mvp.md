---
id: ADR-002
type: adr
title: Pas de Domain Access / pas de Group au MVP
status: Accepted
last-updated: 2026-04-14
date: 2026-04-14
deciders: [jerome]
consulted: []
---

# ADR-002 — Pas de Domain Access / pas de Group au MVP

## Contexte

Les espaces contributeurs sont l'axe éditorial principal. Deux façons classiques de les matérialiser techniquement dans Drupal :

- Module **Domain Access** → vrai sous-site par contributeur (`alice.domaine.fr`).
- Module **Group** → groupes Drupal, permissions par groupe, contenus rattachés à un groupe.

## Décision

Au MVP, **ni Domain ni Group**. Un espace contributeur est matérialisé par :

- Le champ natif `uid` sur chaque Article.
- Une entité `ContributorProfile` (module `profile`) avec un champ `slug`.
- Une route custom `/{slug}` qui génère la page profil via une Views + contrôleur.

## Alternatives considérées

- **Domain Access dès le MVP** : permet vrais sous-domaines, identité forte. Coûts élevés dès le lancement (DNS wildcard, TLS, cookies cross-domain, sessions, config de rendu par domain). YAGNI — l'utilisateur a explicitement accepté de différer les sous-domaines (spec §2).
- **Group dès le MVP** : permet permissions fines par groupe (co-auteurs d'une chronique…). Complexité non justifiée tant qu'un contributeur = un User.

## Conséquences

**Positives :**
- Architecture triviale au MVP : un site, un User par contributeur, Views + Pathauto font le reste.
- Dette technique minimale.
- Moins de surface de permissions à sécuriser.

**Négatives / coûts :**
- Pas de vrais sous-sites au MVP (assumé).
- Si la demande "sous-domaine custom" émerge vite, il faudra ajouter Domain (changement de phase, pas refonte).
- Collaboration multi-auteurs sur un même contenu via Group serait à rajouter plus tard.

## Réversibilité

Ajouter Domain ou Group plus tard ne casse rien : ils se superposent au modèle existant. Le seul coût est la migration des données (ex : créer un domain par slug à partir des ContributorProfile existants).

## Fitness functions

- Une Pull Request qui ajoute `drupal/domain` ou `drupal/group` échoue en review sans ADR superseding celui-ci.
- Les permissions de `contributor_*` n'introduisent pas de concept de groupe (vérifié à l'import config).
