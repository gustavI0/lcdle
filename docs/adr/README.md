---
id: ADR-INDEX
type: index
status: living
last-updated: 2026-04-14
---

# Architecture Decision Records

Ce dossier contient les décisions d'architecture du projet, une par fichier, sous le format Michael Nygard enrichi (frontmatter YAML + sections structurées).

## Index

| ID | Titre | Statut |
|---|---|---|
| [ADR-001](ADR-001-platform-choice.md) | Choix de la plateforme — Drupal 11 Core | Accepted |
| [ADR-002](ADR-002-no-domain-no-group-mvp.md) | Pas de Domain / Group au MVP | Accepted |
| [ADR-003](ADR-003-migration-source.md) | Source de migration — SQL dump + wp-content local | Accepted |
| [ADR-004](ADR-004-theming-stack.md) | Stack de theming — SDC + vanilla CSS moderne + BEM + design tokens | Accepted |
| [ADR-005](ADR-005-infra-stack.md) | Stack infrastructure — PHP 8.4 + PostgreSQL 16 + Docker + OVH | Accepted |

## Template

Copier le template ci-dessous dans `docs/adr/ADR-XXX-<slug>.md` en incrémentant l'ID.

```markdown
---
id: ADR-XXX
title: <Titre court>
status: Proposed | Accepted | Deprecated | Superseded by ADR-YYY
date: YYYY-MM-DD
deciders: [<noms>]
consulted: [<noms>]
---

# ADR-XXX — <Titre>

## Contexte

<Qu'est-ce qui motive la décision ? Quelles contraintes ?>

## Décision

<Ce qu'on a décidé, en une phrase.>

## Alternatives considérées

- **<Option A>** : <description + raison rejet>
- **<Option B>** : <description + raison rejet>

## Conséquences

**Positives :**
- <...>

**Négatives / coûts :**
- <...>

## Réversibilité

<Coût estimé pour défaire la décision.>

## Fitness functions

<Tests automatisés / indicateurs qui vérifient en continu que la décision tient.>
```
