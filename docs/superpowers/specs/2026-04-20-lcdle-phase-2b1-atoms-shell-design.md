---
id: SPEC-003
type: design-spec
status: draft
last-updated: 2026-04-20
supersedes: []
related: [SPEC-001, SPEC-002, ADR-004, ADR-011]
phase: "2B1 — Atoms + Shell"
---

# Phase 2B1 — Atomes + shell du thème `lcdle`

> **Sous-spec** de SPEC-002 (Phase 2 umbrella). Hérite de toute la direction visuelle et des tokens figés en Phase 2A.

## 1. Contexte

- **Phase 2A livrée** (tag `phase-2a-complete`, 2026-04-20). Le thème custom `lcdle` est default, tokens CSS / cascade layers / fonts self-hostées / pipeline WebP en place. Zéro composant SDC pour l'instant — les pages Drupal par défaut rendent avec la bonne typo + palette mais sans layout éditorial.
- **Phase 2B1** pose **le shell du site** (header + footer + container) et **les atomes réutilisables** consommés par les pages qui arriveront en 2B2.
- Les pages de contenu (article, profil, homepage réelle, listes thème/chronique, content types `page`) sont **hors scope 2B1**, explicitement traitées en 2B2.

## 2. Périmètre

| | 2B1 (ici) | 2B2 (plus tard) |
|---|---|---|
| SDC atomiques + utilitaires CSS | ✅ | — |
| `page.html.twig` + `html.html.twig` override | ✅ | — |
| Dark mode toggle (UI + JS + anti-FOUC) | ✅ | — |
| Pages système 404 / 403 / maintenance | ✅ | — |
| Placeholder homepage (route `/`, shell vide) | ✅ | — |
| Content type `page` (About / Contact / Legal) | — | ✅ |
| Homepage réelle (Views ou Controller agrégateur) | — | ✅ |
| Article page template | — | ✅ |
| Profil contributeur template | — | ✅ |
| Page thème / chronique / liste | — | ✅ |
| Microformats h-entry / h-card | — | ✅ (consomment les atomes de 2B1) |
| Variants d'articles media-centric vs texte | — | Phase 3 (décidé sur contenu réel) |

## 3. Décisions prises durant le brainstorm

1. **Granularité SDC = pragmatique** (règle B du brainstorm) : un SDC si au moins l'un de :
   - a une API de slots/props,
   - inclut un autre SDC,
   - nécessite du JS,
   - a des variants conditionnels.
   Sinon = classe CSS utilitaire (BEM, dans `css/components/`).
2. **`wide-card` = SDC séparé** de `article-card` (structure HTML différente, pas d'image). Laisse la porte ouverte à d'autres types de cards en Phase 3.
3. **Homepage Phase 2B1 = placeholder controller**, juste pour tester le shell sans bloquer sur le content model. Remplacé en 2B2.
4. **Tests = minimaliste A** : `component.yml` avec example data (utilisé par la preview SDC core `/admin/structure/component`) + 1 kernel test de rendu par SDC. Pa11y + Backstop reportés à 2C.
5. **Dark mode toggle** : 2-états `light` ↔ `dark`, `localStorage['lcdle-theme']` source de vérité, absent = auto (`prefers-color-scheme`), SVG inline, anti-FOUC via snippet `<script>` inline dans `html.html.twig`.
6. **Pages système** = overrides Twig des templates core, pas des nodes.

## 4. SDC catalog (8 composants)

Tous les SDC vivent dans `web/themes/custom/lcdle/components/<name>/`. Chaque dossier contient :

- `<name>.component.yml` (schéma props + slots + **example data**)
- `<name>.twig` (template)
- `<name>.css` (styles scopés, `@layer components`)
- `<name>.js` (uniquement si interactivité)

### 4.1 `header`

- **Slots** : `nav` (liste de liens), `toggle` (le `dark-mode-toggle` SDC inclus).
- **Props** : `site_name` (string), `site_sub` (string optional — tagline sous le site name).
- **Compose** : `dark-mode-toggle`.
- **Logo** : rendu en type-setting `<h1 class="header__site-name">` avec `font-family: var(--font-family-serif)`, `font-weight: var(--fw-black)`, `font-style: italic`, `color: var(--color-accent)`, `text-shadow: 3px 3px 0 var(--color-accent-shadow)`.
- **Rendu** : flex horizontal (topbar), bord inférieur `--bw-hairline`.

### 4.2 `footer`

- **Slots** : `links` (liste de liens textuels).
- **Props** : `site_name`.
- **Rendu** : flex horizontal, site-name à gauche, links à droite (RSS, À propos, Contact).
- Pas d'image, pas de réseaux sociaux au MVP.

### 4.3 `article-card`

- **Props** : `title`, `url`, `tag` (string, ex. "Musique"), `author_label`, `reading_time` (string, ex. "8 min"), `cover` (render array d'image responsive optionnel — si absent, placeholder surface).
- **Compose** : optionnellement `author-chip` (via prop `show_author_chip: bool`, défaut `false` sur homepage).
- **Classes BEM** : `article-card`, `article-card__cover`, `article-card__tag`, `article-card__title`, `article-card__meta`.
- **Rendu** : vertical stack (image 4:3 → tag → titre serif → meta auteur + durée).

### 4.4 `wide-card`

- **Props** : `title`, `url`, `tag`, `author_label`, `published_date` (ex. "14 avr."), `reading_time`.
- **Rendu** : layout horizontal (ou stacked sur mobile), pas d'image, bord supérieur `hairline`, title serif, meta sur une ligne.
- Utilisé dans la section "Ailleurs" de la homepage.

### 4.5 `author-chip`

- **Props** : `name`, `url` (slug profil), `avatar_initials` (string, ex. "LM"), `avatar_src` (optional — si présent prime sur initials), `size` (enum `sm` / `md` / `lg`, défaut `md`), `role` (string optional, ex. "Musique classique").
- **Variants** :
  - `sm` (14x14, utilisé meta article inline)
  - `md` (26x26, utilisé meta bar)
  - `lg` (38x38, utilisé sidebar article + strip homepage)
- **Fallback** : si pas d'avatar_src, cercle `--color-surface` avec initiales `--fw-medium`.

### 4.6 `pullquote`

- **Slots** : `quote` (le texte), `cite` (attribution optional).
- **Rendu** : `<blockquote>` avec rail gauche `--bw-emphasis` en `--color-accent`, quote en serif italique, cite en sans petite capitale.

### 4.7 `meta-bar`

- **Slots** : `items` (ensemble de `<span>` séparés par des dots).
- **Rendu** : flex horizontal, dots `--color-text-subtle` insérés automatiquement par CSS (`::before` sur `:not(:first-child)`), font sans petite taille.

### 4.8 `dark-mode-toggle`

- **Props** : aucune (stateless — l'état vit dans `localStorage` et `document.documentElement.classList`).
- **Rendu initial** : bouton rond, SVG soleil par défaut (état clair), SVG lune si dark. JS remplace l'icône au toggle.
- **A11y** : `<button aria-label="...">` dynamique, `aria-pressed="true|false"` reflétant l'état courant.
- **JS** : `Drupal.behaviors.lcdleDarkMode` — lit `localStorage`, applique classe, écoute clic, ~30 lignes.

## 5. Utilitaires CSS (classes BEM, pas SDC)

Dans `web/themes/custom/lcdle/css/components/utilities.css` (un seul fichier pour les trois) :

- `.lcdle-tag` — label small-caps `--ls-wider`, `color: var(--color-accent)`, `font-size: var(--fs-xs)`. Utilisé dans cards, articles, sidebars.
- `.lcdle-lettrine` — sélecteur `.lcdle-lettrine > p:first-of-type::first-letter`. Applique Playfair bold large, float left. Appliqué comme classe conteneur sur le body d'un article (en 2B2).
- `.lcdle-section-separator` — `<div class="lcdle-section-separator"><span class="lcdle-section-separator__label">Récents</span></div>` — ligne horizontale + label small-caps.

Ces utilitaires sont chargés via la library `global` (ajoutée à `libraries.yml`).

## 6. Layout templates (`page.html.twig` + `html.html.twig`)

### 6.1 `page.html.twig`

Override minimaliste. Structure :

```twig
<div class="lcdle-layout">
  {% include 'lcdle:header' with { site_name: 'La Culture de l\'Écran', site_sub: 'Musique · Cinéma · Art · Jeux · Voyages' } %}
  <main class="lcdle-layout__main">
    {{ page.content }}
  </main>
  {% include 'lcdle:footer' with { site_name: 'La Culture de l\'Écran' } %}
</div>
```

- Container `.lcdle-layout` = `max-width: 900px; margin: 0 auto; padding: 0 var(--space-5)`.
- Les autres regions Drupal par défaut (sidebars, breadcrumbs) ne sont **pas** rendues en 2B1 ; si des blocks sont placés automatiquement par l'install du thème, on les désactive.

### 6.2 `html.html.twig`

Ajoute un **snippet inline anti-FOUC** dans `<head>`, avant toute CSS :

```html
<script>
  (function () {
    var t = localStorage.getItem('lcdle-theme');
    if (t === 'dark') document.documentElement.classList.add('dark');
    else if (t === 'light') document.documentElement.classList.add('light');
  })();
</script>
```

~8 lignes, exécutées synchrone pour éviter le FOUC. Pas de dépendance, pas de bundle. Le reste de la logique (clic, write localStorage) vit dans `dark-mode-toggle.js`.

## 7. Pages système

Chaque page a un template Twig dédié. Utilise le même shell (`page.html.twig`) sauf la page maintenance.

### 7.1 `page--system--404.html.twig`

- Titre serif "Page introuvable".
- Corps : paragraphe explicatif, un lien "Retour à l'accueil".
- Bouton recherche optionnel (désactivé tant que la search page n'existe pas — reporté 2C).

### 7.2 `page--system--403.html.twig`

- Titre serif "Accès refusé".
- Corps : lien vers `/user/login` si l'utilisateur est anonyme, message alternatif sinon.

### 7.3 `maintenance-page.html.twig`

- Page **autonome** (Drupal ne peut pas forcément instancier le full theme en maintenance).
- Inline les tokens critiques + une version allégée du logo type-setting.
- Message simple "Le site est temporairement en maintenance."

## 8. Placeholder homepage (route `/`)

- Controller custom `HomepagePlaceholderController` ajouté dans le module `lcdle_theme_helpers` existant (OOP, cohérent avec l'emplacement du `FontPreloadHelper`). Pas de nouveau module nécessaire pour un placeholder temporaire.
- Retourne un render array simple : `<main>` vide + un message neutre "Site en construction — les pages arrivent en Phase 2B2."
- Supprimé en 2B2 quand la vraie homepage arrive.
- Le but : vérifier que header + footer + shell + dark mode + atomes utilisés depuis les blocks système fonctionnent.

## 9. Tests (stratégie minimaliste)

### 9.1 Exemples data dans `component.yml`

Chaque SDC a un bloc `examples:` qui est lu par la preview `/admin/structure/component` (Drupal core 11.x). Cela sert de :

- documentation visuelle
- terrain de test manuel rapide
- source d'input pour les kernel tests

### 9.2 Kernel test par SDC

Un test `*ComponentRenderTest` par SDC, basé sur `ComponentKernelTestBase` de Drupal core. Il :

1. Rend le SDC avec les example data.
2. Parse l'output HTML.
3. Asserte les **invariants structurels** : présence des classes BEM clés, présence des attributs a11y (`aria-label`, `role`), absence de contenu de debug.

Pas d'assertion visuelle (pixel-perfect). Pas de snapshot.

### 9.3 Tests à l'échelle page

Pas nécessaire en 2B1. Le `TokensLoadedTest` de 2A reste valide. En 2C on ajoute Pa11y + éventuellement un functional test intégré homepage.

## 10. Accessibilité (WCAG 2.1 AA)

- **Contraste** : tokens de palette déjà vérifiés dans les maquettes (ratio ≥ 4.5:1 pour texte, ≥ 3:1 pour UI). Re-checker light + dark après implémentation.
- **Focus visible** : chaque élément interactif a un outline visible (`:focus-visible { outline: 2px solid var(--color-accent); outline-offset: 2px; }`).
- **Navigation clavier** : toggle dark mode accessible via Tab + Enter/Space.
- **ARIA** : chaque SDC interactif a les attributs appropriés (détaillés dans leur `component.yml`).
- **Outil** : axe DevTools extension, exécuté manuellement sur chaque preview SDC. Pa11y CI en 2C.

## 11. Fichiers créés en 2B1

```text
web/themes/custom/lcdle/
├── components/
│   ├── header/
│   │   ├── header.component.yml
│   │   ├── header.twig
│   │   └── header.css
│   ├── footer/
│   │   ├── footer.component.yml
│   │   ├── footer.twig
│   │   └── footer.css
│   ├── article-card/
│   │   ├── article-card.component.yml
│   │   ├── article-card.twig
│   │   └── article-card.css
│   ├── wide-card/
│   │   ├── wide-card.component.yml
│   │   ├── wide-card.twig
│   │   └── wide-card.css
│   ├── author-chip/
│   │   ├── author-chip.component.yml
│   │   ├── author-chip.twig
│   │   └── author-chip.css
│   ├── pullquote/
│   │   ├── pullquote.component.yml
│   │   ├── pullquote.twig
│   │   └── pullquote.css
│   ├── meta-bar/
│   │   ├── meta-bar.component.yml
│   │   ├── meta-bar.twig
│   │   └── meta-bar.css
│   └── dark-mode-toggle/
│       ├── dark-mode-toggle.component.yml
│       ├── dark-mode-toggle.twig
│       ├── dark-mode-toggle.css
│       └── dark-mode-toggle.js
├── css/components/
│   ├── layout.css                     # .lcdle-layout + container
│   └── utilities.css                  # .lcdle-tag, .lcdle-lettrine, .lcdle-section-separator
├── templates/
│   ├── layout/
│   │   ├── html.html.twig             # override pour anti-FOUC
│   │   └── page.html.twig             # shell layout
│   └── system/
│       ├── page--system--404.html.twig
│       ├── page--system--403.html.twig
│       └── maintenance-page.html.twig

web/modules/custom/lcdle_theme_helpers/src/
└── Controller/
    └── HomepagePlaceholderController.php    # placeholder route '/'

web/modules/custom/lcdle_theme_helpers/
├── lcdle_theme_helpers.routing.yml      # ajout route front-page
└── tests/src/Kernel/
    ├── HeaderComponentRenderTest.php
    ├── FooterComponentRenderTest.php
    ├── ArticleCardComponentRenderTest.php
    ├── WideCardComponentRenderTest.php
    ├── AuthorChipComponentRenderTest.php
    ├── PullquoteComponentRenderTest.php
    ├── MetaBarComponentRenderTest.php
    └── DarkModeToggleComponentRenderTest.php
```

## 12. Modifications attendues

- `lcdle.libraries.yml` : nouvelle library `components` (ou intégrée à `global`) qui charge `layout.css` + `utilities.css`.
- `lcdle.info.yml` : **aucun** changement — SDCs sont auto-découverts depuis `components/`.
- `config/sync/` : nouvelles config pour les blocks désactivés (si on choisit de désactiver les placements automatiques) + la route homepage placeholder.

## 13. Fitness functions Phase 2B1

- [ ] `drush sdc:list` (ou l'UI `/admin/structure/component`) montre les 8 SDC attendus, tous avec example data.
- [ ] Chaque kernel test `*ComponentRenderTest` passe (8 tests).
- [ ] Toggle dark mode visuellement opérationnel en mode browser (OS en auto, puis toggle en dark, puis refresh → reste dark).
- [ ] FOUC absent : le premier paint applique la bonne classe (test manuel en throttling réseau).
- [ ] Les trois pages système 404/403/maintenance rendent avec le shell et typographie cohérents.
- [ ] La route `/` (placeholder) rend header + footer + message neutre.
- [ ] phpcs + phpstan 0 erreurs sur tout nouveau code.
- [ ] Le `TokensLoadedTest` de 2A reste vert.

## 14. Out of scope (2B1, rappel)

- Content type `page` (About/Contact/Legal) — Phase 2B2.
- Homepage réelle (Views agrégatrice) — Phase 2B2.
- Article page template + microformats h-entry — Phase 2B2.
- Profile page + h-card — Phase 2B2.
- Variants d'articles (media-centric vs texte) — Phase 3.
- Pa11y CI + Backstop visual regression — Phase 2C.
- Sitemap / metatag / canonical — Phase 2C.

## 15. Décisions à acter par ADR (si elles émergent en 2B1)

Probablement aucune nouvelle ADR en 2B1 — les décisions structurantes sont déjà dans ADR-004 (stack theming) et ADR-011 (fonts). À flaguer au plan seulement si un arbitrage inattendu émerge (ex. stratégie icons, structure du module de controller).
