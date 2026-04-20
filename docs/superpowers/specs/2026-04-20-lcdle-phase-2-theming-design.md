---
id: SPEC-002
type: design-spec
status: draft
last-updated: 2026-04-20
supersedes: []
related: [SPEC-001, ADR-004]
phase: "2 — Theming + lancement"
---

# Phase 2 — Theming MVP + lancement

> **Umbrella spec** : cadre la direction visuelle, la découpe en sous-phases, et détaille la sous-phase **2A — Foundation**. Les sous-phases suivantes (2B1, 2B2, 2C, 2D) feront chacune l'objet d'un brainstorm ciblé aboutissant à leur propre spec + plan.

## 1. Contexte

- **Phase 1 — migration WordPress — terminée** (tag `phase-1-complete`, 2026-04-20). 496 articles, 19 contributeurs, 1219 médias, 496 redirects 301 en staging.
- **Phase 2 — theming + launch** commence avec le theme custom public **zéro** (seul Gin pour l'admin, Stark par défaut).
- **ADR-004** (stack theming) reste valide : SDC + vanilla CSS moderne + BEM + design tokens CSS custom properties.
- **Spec de référence** : `2026-04-14-lcdle-migration-design.md` §7 (theming) + §12.1 (conditions bascule DNS).

## 2. Décomposition Phase 2

Phase 2 est décomposée en **5 sous-phases séquentielles**. Chacune produit un livrable verifiable ; la bascule DNS n'a lieu qu'en fin de 2D.

| Sous-phase | Portée | Livrable / critère de fin |
|---|---|---|
| **2A — Foundation** | Theme `lcdle` scaffold (starterkit), design tokens (W3C-inspired, implémentés en CSS custom properties), polices self-hostées (Playfair Display + Inter), cascade layers, dark mode (auto + toggle), pipeline responsive images WebP | Theme public actif, pages par défaut rendues avec tokens, dark mode fonctionnel, aucun composant custom encore |
| **2B1 — Atomes + shell** | SDC atomiques (Header, Footer, Card article, Author chip, Pullquote, Lettrine, Tag, Meta bar), pages système (404, 403, maintenance) | Shell du site en place, navigation fonctionnelle, atomes réutilisables documentés |
| **2B2 — Pages de contenu** | SDC pages (Homepage, Page article, Page profil contributeur, Page thème, Page chronique, Liste articles, Newsletter UI themed), microformats h-entry + h-card | Site public visuellement complet sur staging avec contenu migré |
| **2C — SEO & feeds** | Modules `metatag`, `simple_sitemap`, `pathauto` (tuning), `redirect` (déjà installé) ; canonical URLs, robots.txt, Schema.org JSON-LD, feeds RSS (global/auteur/thème), audit 100 % redirects automatisé, Core Web Vitals benchmark | Conditions SEO §12.1 vertes |
| **2D — Ops & bascule DNS** | Déploiement prod VPS OVH (Docker Compose), SPF/DKIM/DMARC, backup `pg_dump` + `uploads/` automatisé + test restore, monitoring uptime + erreurs, bascule DNS, veille GSC 30 j | DNS basculé, site LCDLE en prod, WP extinguible |

**Différé explicitement** : onboarding ouvert (formulaire `/contribute`), envoi newsletter, IndieWeb/Fediverse → Phase 3+.

## 3. Direction visuelle

### 3.1 Esthétique générale

- **Éditorial, minimaliste, centré texte.** Inspiration Medium / Ghost / The Verge.
- **Typographie-first** : serif pour titres + noms de marque, sans-serif pour corps de texte et UI.
- **Palette sobre** : tons crème/graphite, accent rouge brique (hérité du logo historique).
- **Pas d'images décoratives** : toute image a une fonction (illustration d'article, cover, avatar). Pas de fond, pas de pattern.
- **Interactions minimales** : hover color shift, transitions dark mode, pas d'animations décoratives.
- **Container centré 900 px max**, pages système full-width. Mobile-first.

### 3.2 Typographie

| Rôle | Famille | Justification |
|---|---|---|
| Serif (titres, site name, pullquote) | **Playfair Display** | Haute lisibilité éditoriale, italique expressive pour les sous-titres et citations, large gamme de poids (400 à 900) |
| Sans (corps, UI, nav) | **Inter** | Sans-serif humaniste neutre, excellente lisibilité écran, très bon rendu petite taille (meta, captions), couvre latin + cyrillique |

**Poids self-hostés (WOFF2 dans `web/themes/custom/lcdle/fonts/`)** :

- Playfair Display : 400 Regular, 400 Italic, 500 Medium, 900 Black Italic (logo).
- Inter : 300 Light, 400 Regular, 500 Medium.

**Stratégie de chargement** :

- `@font-face` avec `font-display: swap`.
- `<link rel="preload" as="font" type="font/woff2" crossorigin>` pour **Playfair 400** et **Inter 400** (poids critiques).
- Fallback stack : `'Playfair Display', Georgia, 'Times New Roman', serif` et `'Inter', system-ui, -apple-system, sans-serif`.
- **Pas** de Google Fonts CDN (RGPD).

### 3.3 Palette

**Light mode** (extrait des maquettes validées) :

| Token | Valeur | Usage |
|---|---|---|
| `--color-bg` | `#FAFAF8` | Fond page |
| `--color-surface` | `#F2F0EB` | Cartes, sidebar, placeholder images |
| `--color-text` | `#1A1A18` | Texte principal |
| `--color-text-muted` | `#6B6B62` | Texte secondaire, body des articles |
| `--color-text-subtle` | `#9B9B90` | Meta, dates, captions |
| `--color-border` | `rgba(0,0,0,0.1)` | Séparateurs, outlines subtils |
| `--color-accent` | `#C84B2A` | Hover, liens, tags, pullquote rail, logo (text color) |
| `--color-accent-shadow` | `#2D7A4A` *(à affiner depuis le PNG logo)* | `text-shadow` du logo en type-setting |

**Dark mode** :

| Token | Valeur |
|---|---|
| `--color-bg` | `#141412` |
| `--color-surface` | `#1E1E1B` |
| `--color-text` | `#EDEDE8` |
| `--color-text-muted` | `#A8A89F` |
| `--color-text-subtle` | `#6B6B62` |
| `--color-border` | `rgba(255,255,255,0.1)` |
| `--color-accent` | `#E06040` |
| `--color-accent-shadow` | `#4DA371` *(à affiner)* |

**La palette peut changer.** Tout passe par CSS custom properties dans un seul fichier `tokens.css` (light) + `tokens-dark.css` (dark override via `.dark` ou `@media (prefers-color-scheme: dark)`). Un changement de palette = édition d'un fichier.

### 3.4 Traitement du logo

- **Type-setting pur**, pas d'image PNG dans le header.
- Rendu : `<h1 class="site-logo">` avec texte "La Culture de l'Écran" en **Playfair Display 900 Italic**, `color: var(--color-accent)` + `text-shadow: 3px 3px 0 var(--color-accent-shadow)`.
- La teinte verte `--color-accent-shadow` est extraite du PNG logo original (opération manuelle, hors plan d'implémentation).
- **Favicon SVG** : vectorisé par l'humain à partir du PNG (outil dédié type Inkscape trace-bitmap), non généré par code.

### 3.5 Layout

- **Container principal** : `max-width: 900px`, centré, padding horizontal `1.5rem`.
- **Page article** : grille 2-col (main `minmax(0, 1fr)` + sidebar `220px`), sidebar sticky, gap `4rem` (desktop). Effondré en 1-col sous `768px`.
- **Homepage** : hero + grille 3-cards (`repeat(3, 1fr)`) + wide-grid (`1.4fr 1fr`) + strip contributeurs. Effondré en 1-col sous `768px`.
- **Pages système** : full-width, content centré, pas de sidebar.

### 3.6 Microformats

- `h-entry` (articles) : `.h-entry`, `.p-name` (titre), `.e-content` (corps), `.dt-published` (date), `.p-author h-card` (auteur).
- `h-card` (profils contributeurs) : `.h-card`, `.p-name`, `.p-note` (bio), `.u-url` (slug URL), `.u-photo` (avatar).
- **Implémentation** : attributs de classes sur templates Twig (SDC), pas de JS.
- **Validation** : testé via [microformats.io](https://microformats.io/) et [indiewebify.me](https://indiewebify.me/).

## 4. Phase 2A — Foundation (détaillée)

### 4.1 Objectif

Poser l'infrastructure du thème : scaffold, tokens, polices, dark mode, pipeline responsive images. Aucun composant éditorial custom (ça commence en 2B1). À la fin de 2A, les pages par défaut Drupal rendent avec la bonne typo, la bonne palette, le dark mode fonctionne, les images générées sont WebP.

### 4.2 Scaffold du thème

- Nom machine : `lcdle`.
- Package : `LCDLE`.
- Généré via `drush theme:generate lcdle --name="La Culture de l'Écran"` (Drupal 11 starterkit). Résultat : thème 100 % autonome, aucune dépendance à `stable9` runtime.
- Emplacement : `web/themes/custom/lcdle/`.
- Activation : default theme = `lcdle`. Admin theme reste `gin`.

### 4.3 Structure des fichiers 2A

```text
web/themes/custom/lcdle/
├── lcdle.info.yml
├── lcdle.libraries.yml
├── lcdle.theme                 # minimal, OOP-first : délégation à des services si logique non triviale
├── config/install/             # configuration installée (si settings spécifiques)
├── config/schema/              # schéma des settings du thème
├── css/
│   ├── base/
│   │   ├── reset.css           # modern minimal reset
│   │   ├── tokens.css          # design tokens light mode (CSS custom properties)
│   │   ├── tokens-dark.css     # override dark mode
│   │   ├── fonts.css           # @font-face declarations
│   │   └── base.css            # html, body, elements HTML bruts
│   └── layers.css              # déclaration @layer (ordre cascade)
├── fonts/                      # WOFF2 self-hostés
│   ├── PlayfairDisplay-Regular.woff2
│   ├── PlayfairDisplay-Italic.woff2
│   ├── PlayfairDisplay-Medium.woff2
│   ├── PlayfairDisplay-BlackItalic.woff2
│   ├── Inter-Light.woff2
│   ├── Inter-Regular.woff2
│   └── Inter-Medium.woff2
├── js/
│   └── dark-mode.js            # toggle vanilla JS, attaché via Drupal Behaviors
├── templates/                  # vide en 2A, remplie en 2B1/2B2
├── logo.svg                    # favicon + logo fallback, fourni par l'humain
└── screenshot.png              # preview du thème (exigence Drupal)
```

### 4.4 Design tokens — catégories

Scope validé :

| Catégorie | Variables | Notes |
|---|---|---|
| `color` | bg, surface, text, text-muted, text-subtle, border, accent, accent-shadow | × 2 modes |
| `font-family` | serif, sans | pas de mono au MVP |
| `font-size` | `--fs-xs` à `--fs-5xl` | scale modulaire, ratio 1.25 (majeur tierce) sur base 16px |
| `font-weight` | light 300, regular 400, medium 500, black 900 | cohérent avec les poids self-hostés |
| `line-height` | tight 1.2, normal 1.55, loose 1.8 | tight pour titres, loose pour corps article |
| `letter-spacing` | tight -0.02em, normal 0, wide 0.05em, wider 0.15em | wider = labels small-caps |
| `space` | scale 4px-based : 4, 8, 12, 16, 24, 32, 48, 64, 96 px | nommés `--space-1` à `--space-9` |
| `radius` | sm 3px, md 4px, lg 8px | les maquettes utilisent 3-4px partout |
| `border-width` | hairline 0.5px, default 1px, emphasis 2px | |
| `breakpoint` | 480 / 768 / 1024 / 1400 | utilisés pour container queries + media queries |
| `duration` | fast 150ms, default 300ms | transitions dark mode, hover |

**Pas** de tokens :

- Shadow : mockup minimaliste n'en a pas.
- Z-index scale : site éditorial plat, pas de layers multiples.
- Opacity scale : cas marginaux traités inline.

### 4.5 Cascade layers

Déclaration dans `layers.css` (chargé en premier) :

```css
@layer reset, tokens, base, components, utilities;
```

**Ordre d'importance croissante** : `reset` (le plus faible) → `utilities` (le plus fort, pour overrides minimalistes éventuels). Les composants SDC (2B) écrivent dans `@layer components`.

### 4.6 Dark mode

**Stratégie combinée** (option C validée) :

1. **Auto par défaut** : `@media (prefers-color-scheme: dark) { :root { /* tokens-dark */ } }`.
2. **Toggle manuel** : clic sur bouton header → `.dark` class sur `<html>`, override les tokens.
3. **Persistance** : `localStorage.setItem('lcdle-theme', 'dark'|'light'|'auto')`.
4. **Hydration** : petit snippet JS inline en `<head>` qui applique la classe AVANT le premier paint (évite le FOUC).

Implémentation : ~20 lignes de vanilla JS dans `dark-mode.js`, attaché via `Drupal.behaviors.lcdleDarkMode`.

Bouton toggle : **placé en 2B1** (c'est un atome Header). En 2A, on teste avec `prefers-color-scheme` OS-level uniquement.

### 4.7 Polices — self-host et preload

1. Télécharger directement les WOFF2 depuis **fontsource.org** (téléchargement HTTP, aucun npm, aucun node_modules dans le repo). Committer dans `web/themes/custom/lcdle/fonts/`.
2. Déclarer les 7 fichiers via `@font-face` dans `css/base/fonts.css`.
3. `lcdle.libraries.yml` déclare la lib `fonts` avec `dependencies: []` et preload via `html_head_link` dans `lcdle.theme` (hook `preprocess_html`).
4. `font-display: swap` partout pour éviter le FOIT.
5. **Ajouter ADR-011** : décision self-host polices pour RGPD.

### 4.8 Pipeline responsive images

- **Module core `responsive_image`** activé.
- **Module core `image`** gère la conversion WebP via le **Convert effect** des image styles (pas besoin de `image_effects` contrib).
- **Breakpoints module** : déclaration d'un breakpoint group `lcdle` dans `lcdle.breakpoints.yml` (4 breakpoints : 480 / 768 / 1024 / 1400).
- **Image styles** créés en 2A (via config YAML) :
  - `webp_480`, `webp_768`, `webp_1024`, `webp_1400` — conversion + resize.
  - `jpeg_fallback_1024` — fallback pour navigateurs sans WebP (rare en 2026).
- **Responsive image style** `lcdle_article_cover` mappe les breakpoints aux image styles via `<picture>` + `srcset`.
- L'assignation de ces responsive image styles aux champs `field_cover_image` etc. se fait en 2B2 (quand les pages d'article sont construites).

### 4.9 Modules à installer en 2A

- **Aucun module contrib** à ajouter (tout est déjà là ou dans core).
- Vérifier que **`responsive_image`** est activé (core, désactivé par défaut).
- Le thème `lcdle` lui-même est activé comme default.

### 4.10 OOP dans le code custom

- **`lcdle.theme`** reste **fin** : ne contient que les hooks procéduraux Drupal imposés (ex. `lcdle_preprocess_html`).
- Toute logique non triviale (calcul, transformation de données, sélection de variables) est **déléguée à un service** dans un module compagnon `lcdle_theme_helpers` (créé en 2A si nécessaire, sinon repoussé à 2B1 au premier besoin).
- Les hooks PHP 8 modernes (`#[Hook]`) ne s'appliquent pas aux thèmes (seulement aux modules), donc on vit avec les hooks procéduraux **minimalistes**, mais toute logique est dans des classes.

## 5. Fitness functions Phase 2A

- [ ] `web/themes/custom/lcdle/` contient le scaffold starterkit, pas de fichier superflu.
- [ ] `drush theme:list` montre `lcdle` activé comme default.
- [ ] Aucun `.scss`, aucun `tailwind.config.*`, aucun `package.json` dans `web/themes/custom/lcdle/`.
- [ ] `css/base/tokens.css` contient toutes les variables énoncées en §3.3 et §4.4.
- [ ] `prefers-color-scheme: dark` bascule les couleurs (test manuel avec DevTools emulation).
- [ ] `<link rel="preload">` présent dans `<head>` pour Playfair Regular + Inter Regular (curl | grep).
- [ ] Les WOFF2 self-hostés sont chargés via `@font-face` depuis `/themes/custom/lcdle/fonts/` (DevTools Network tab montre 0 requête vers fonts.googleapis.com).
- [ ] Les 4 image styles WebP + la responsive_image style `lcdle_article_cover` sont exportés dans `config/sync/`.
- [ ] Un test Drupal Kernel vérifie que les tokens CSS sont chargés sur une page anonyme (pas d'assertion visuelle, juste présence des variables).

## 6. Out of scope (Phase 2A)

- Tout composant SDC éditorial (Header, Footer, Card, Article, etc.) → 2B1/2B2.
- Microformats h-entry / h-card → 2B2.
- Tout template Twig non trivial (pages.html.twig, node--article.html.twig) → 2B1/2B2.
- Metatag, sitemap, Schema.org, feeds RSS → 2C.
- SPF/DKIM/DMARC, backup, monitoring, bascule DNS → 2D.

## 7. Références

- **SPEC-001** — `2026-04-14-lcdle-migration-design.md` §7 (theming) + §12.1 (conditions DNS).
- **ADR-004** — stack theming (SDC + vanilla CSS + BEM + tokens).
- **Maquettes validées** (non versionnées) : `~/Downloads/laculturedelecran_homepage_mockup.html` + `laculturedelecran_article_mockup.html`.
- Nouvelle ADR à rédiger au démarrage de 2A : **ADR-011 — Self-host des polices** (justification RGPD).

## 8. Décisions prises durant le brainstorm

- Polices : Playfair Display (serif) + Inter (sans), **self-hostées** (RGPD).
- Dark mode : **auto + toggle manuel + localStorage** (option C).
- Theme scaffold : **starterkit Drupal 11** via `drush theme:generate` (pas de parent stable9).
- Logo : **type-setting pur** (Playfair Display 900 Italic + text-shadow vert), pas d'image PNG dans le header. Favicon SVG fourni par l'humain.
- Découpage : **5 sous-phases** (2A → 2D avec 2B split 2B1/2B2), séquentielles, bascule DNS en fin de 2D.
- Niveau de finition avant DNS : **B (§7.3 complet)**.
- Stack CSS : **vanilla CSS confirmé**, pas de Tailwind, pas de SCSS. ADR-004 reste valide.
- WebP : via `image` core (pas `image_effects` contrib).
- Palette accent actuelle (`#C84B2A` brique + vert logo à extraire) **mutable** via les tokens, sans refactoring requis.
