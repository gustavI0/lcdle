---
id: ADR-004
type: adr
title: Stack de theming — SDC + vanilla CSS moderne + BEM + design tokens
status: Accepted
last-updated: 2026-04-14
date: 2026-04-14
deciders: [jerome]
consulted: []
---

# ADR-004 — Stack de theming

## Contexte

Le theme custom doit être simple, moderne, léger, évolutif, accessible (WCAG 2.1 AA), et préparer IndieWeb/Fediverse (microformats). Pas de personnalisation visuelle par contributeur au MVP.

## Décision

- **Parent** : `stable9` ou thème vierge (pas Bootstrap/Olivero comme parent).
- **Composants** : Single Directory Components (SDC) natif Drupal 11.
- **CSS** : vanilla CSS moderne (cascade layers, `@property`, container queries, nesting natif, custom properties). Pas de Sass.
- **Nommage** : BEM (Block Element Modifier), cohérent avec la structure SDC (un composant = un block).
- **Design tokens** : alignés sur la spec W3C Design Tokens 2025, implémentés en CSS custom properties.
- **Microformats** : h-entry / h-card intégrés dès le MVP.
- **JS** : vanilla ou Alpine.js via Drupal Behaviors, uniquement pour interactions légères.
- **Responsive images** : `responsive_image` + `image_style` core, WebP via `convert` effect, `<picture>` + `srcset`.

## Alternatives considérées

- **Tailwind CSS v4** : utility-first, DX excellente. Ajoute une étape de build et pollue le HTML avec le vocabulaire Tailwind. Pour un site de lecture (peu d'interactions), l'overhead n'est pas justifié.
- **Sass + Gulp/Webpack** : legacy 2026. Le CSS moderne couvre tout.
- **Framework front JS (React/Vue)** : YAGNI. Le site est essentiellement du contenu à lire.

## Conséquences

**Positives :**
- Zéro toolchain CSS : le CSS est livré tel quel, lisible, débogable directement.
- SDC encourage des composants isolés, testables, documentables.
- Microformats gratuits dès le MVP = pas de rétrofit pour la phase IndieWeb.

**Négatives / coûts :**
- BEM sur SDC demande de la discipline (pas de classes génériques comme `.container`).
- Support IE11 / vieux navigateurs abandonné (cohérent avec 2026).

## Réversibilité

Moyenne. Changer de technologie CSS à mi-parcours implique une réécriture du theme. Le modèle SDC + design tokens (séparation structure / apparence) limite l'impact.

## Fitness functions

- Tout nouveau composant SDC est accompagné de son fichier `*.component.yml` + template Twig + CSS + tests a11y Pa11y/axe.
- Le theme custom n'importe jamais de Sass (`.scss` absent du repo).
- Un test CI échoue si un composant utilise une classe CSS qui ne respecte pas BEM (règle stylelint à ajouter en Phase 2).
