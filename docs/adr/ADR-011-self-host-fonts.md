---
id: ADR-011
type: adr
title: Self-host des polices (RGPD + perf)
status: Accepted
last-updated: 2026-04-20
date: 2026-04-20
deciders: [jerome]
consulted: []
---

# ADR-011 — Self-host des polices

## Contexte

Le thème custom `lcdle` utilise deux familles : **Playfair Display** (serif, titres, marque) et **Inter** (sans, corps + UI). La question : CDN (Google Fonts) ou self-host ?

Jurisprudence RGPD 2022-2025 en UE (notamment tribunal de Munich 2022, prolongée par d'autres décisions nationales) : charger Google Fonts via `fonts.googleapis.com` transmet l'adresse IP du visiteur à un serveur US sans consentement, ce qui constitue un transfert de données personnelles hors UE non conforme. Le site est public, francophone, la conformité RGPD est non-négociable.

Côté performance : le CDN tiers ajoute un round-trip DNS + TLS handshake dès la première visite. Le self-host permet `<link rel="preload">` sur les poids critiques sans coût réseau externe.

## Décision

Toutes les polices du thème `lcdle` sont **self-hostées** au format **WOFF2** dans `web/themes/custom/lcdle/fonts/`, servies depuis le même domaine que le site. Déclarations via `@font-face` dans `css/base/fonts.css` avec `font-display: swap`.

Poids retenus (minimisation de la charge réseau) :

- Playfair Display : 400, 400 italic, 500, 900 italic (logo).
- Inter : 300, 400, 500.

Source : [fontsource.org](https://fontsource.org/) (packages npm sous licence OFL, miroirés sur jsDelivr).

## Alternatives considérées

- **Google Fonts CDN** — risque juridique RGPD avéré, connexion à un tiers US. Écartée.
- **Bunny Fonts** (alternative RGPD-friendly) — ajoute une dépendance externe pour un bénéfice marginal par rapport au self-host. Écartée.
- **fontsource.org via npm `@fontsource`** — ajoute une toolchain Node/npm au theme. Écartée pour rester cohérent avec ADR-004 (zéro toolchain CSS).

## Conséquences

**Positives :**

- Conformité RGPD sans bandeau de consentement spécifique aux fonts.
- Performance maîtrisée : pas de round-trip externe, preload possible.
- Cache HTTP partagé avec le reste du domaine.

**Négatives / coûts :**

- ~200 Ko de WOFF2 committés dans le repo (~7 fichiers).
- Pas de cache inter-sites Google Fonts (négligeable, la majorité des navigateurs partitionnent déjà le cache par site).

## Réversibilité

Haute. Changer de police = remplacer les WOFF2, mettre à jour `fonts.css` et les tokens `--font-family-*`.

## Fitness functions

- DevTools Network panel sur `https://lcdle.ddev.site/` : **0 requête** vers `fonts.googleapis.com`, `fonts.gstatic.com`, ou tout CDN tiers pour des polices.
- `grep -r "fonts.google" web/themes/custom/` retourne vide.
