---
id: PLAN-2B1
type: implementation-plan
status: draft
last-updated: 2026-04-20
spec: SPEC-003
phase: "2B1 — Atoms + Shell"
---

# Phase 2B1 — Atoms + Shell Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the shell of the `lcdle` theme — 8 SDC components (header, footer, article-card, wide-card, author-chip, pullquote, meta-bar, dark-mode-toggle), 3 CSS utilities (tag, lettrine, section-separator), the layout templates (`html.html.twig` + `page.html.twig`), the three system pages (404/403/maintenance), and a placeholder homepage route so the shell can actually be rendered for a visitor. No content pages — those come in 2B2.

**Architecture:** Each SDC lives in `web/themes/custom/lcdle/components/<name>/` with a `component.yml` (JSON-Schema props + slots + example data), a Twig template, a scoped CSS file written inside `@layer components`, and — for the dark mode toggle only — a small vanilla JS file. Each SDC has one kernel test based on Drupal's `ComponentKernelTestBase`. Utility classes live in `css/components/utilities.css` inside `@layer utilities`. The placeholder homepage is a controller under `lcdle_theme_helpers` (same module as `FontPreloadHelper`); it renders only the shell and a holding message.

**Tech Stack:** Drupal 11, PHP 8.4, vanilla CSS, SDC (core), DDEV locally. No build step, no npm, no SCSS, no Tailwind.

---

## Scope

In: 8 SDC, 3 CSS utilities, layout templates, system pages, dark mode toggle + anti-FOUC, placeholder homepage, 8 kernel tests.

Out (deferred to later sub-phases): content type `page`, real homepage Views, article/profile/theme/chronique templates, microformats h-entry/h-card, Pa11y, Backstop visual regression.

---

## File Structure

### Created

```text
web/themes/custom/lcdle/
├── components/
│   ├── author-chip/
│   │   ├── author-chip.component.yml
│   │   ├── author-chip.twig
│   │   └── author-chip.css
│   ├── meta-bar/
│   │   ├── meta-bar.component.yml
│   │   ├── meta-bar.twig
│   │   └── meta-bar.css
│   ├── pullquote/
│   │   ├── pullquote.component.yml
│   │   ├── pullquote.twig
│   │   └── pullquote.css
│   ├── dark-mode-toggle/
│   │   ├── dark-mode-toggle.component.yml
│   │   ├── dark-mode-toggle.twig
│   │   ├── dark-mode-toggle.css
│   │   └── dark-mode-toggle.js
│   ├── article-card/
│   │   ├── article-card.component.yml
│   │   ├── article-card.twig
│   │   └── article-card.css
│   ├── wide-card/
│   │   ├── wide-card.component.yml
│   │   ├── wide-card.twig
│   │   └── wide-card.css
│   ├── header/
│   │   ├── header.component.yml
│   │   ├── header.twig
│   │   └── header.css
│   └── footer/
│       ├── footer.component.yml
│       ├── footer.twig
│       └── footer.css
├── css/components/
│   ├── layout.css                        # .lcdle-layout, container, grid helpers
│   └── utilities.css                     # .lcdle-tag, .lcdle-lettrine, .lcdle-section-separator
└── templates/
    ├── layout/
    │   ├── html.html.twig                # anti-FOUC inline snippet
    │   └── page.html.twig                # shell with header + main + footer
    └── system/
        ├── page--system--404.html.twig
        ├── page--system--403.html.twig
        └── maintenance-page.html.twig

web/modules/custom/lcdle_theme_helpers/
├── lcdle_theme_helpers.routing.yml       # NEW — route lcdle.homepage_placeholder
├── src/Controller/
│   └── HomepagePlaceholderController.php
└── tests/src/Kernel/
    ├── AuthorChipComponentRenderTest.php
    ├── MetaBarComponentRenderTest.php
    ├── PullquoteComponentRenderTest.php
    ├── DarkModeToggleComponentRenderTest.php
    ├── ArticleCardComponentRenderTest.php
    ├── WideCardComponentRenderTest.php
    ├── HeaderComponentRenderTest.php
    └── FooterComponentRenderTest.php
```

### Modified

- `web/themes/custom/lcdle/lcdle.libraries.yml` — add `components` library (layout + utilities CSS).
- `config/sync/` — reflect the new route, any block placement cleanup, and the config export after the theme re-install.

---

## Conventions

- **SDC machine names**: kebab-case, namespaced as `lcdle:<name>` in Twig includes and `#component` render arrays.
- **BEM**: each SDC root element carries a class matching the machine name (e.g. `.article-card`, `.author-chip--lg`). Modifiers use `--`, elements use `__`.
- **CSS layers**: every SDC CSS file wraps its rules in `@layer components { ... }`. Utilities wrap in `@layer utilities { ... }`.
- **Accessibility**: every interactive element has an explicit `aria-label` (in French — this is UI text). Every SDC declares its a11y contract in its `component.yml` description.
- **TDD**: one kernel test per SDC. Test renders the component with its `examples.default` props + slots, asserts key BEM classes and a11y attributes are present. Do NOT snapshot HTML.
- **Code in English**, UI labels in French.
- **Commits**: one per task, message explains *why*, structure vs behavior in separate commits when refactoring.
- **phpcs + phpstan 0 errors 0 warnings**: run after each code task before committing.

---

## Task 1 — Layout foundation (`layout.css`, `html.html.twig`, `page.html.twig`)

**Files:**
- Create: `web/themes/custom/lcdle/css/components/layout.css`
- Create: `web/themes/custom/lcdle/templates/layout/html.html.twig`
- Create: `web/themes/custom/lcdle/templates/layout/page.html.twig`
- Modify: `web/themes/custom/lcdle/lcdle.libraries.yml`

- [ ] **Step 1: Write `layout.css`**

`web/themes/custom/lcdle/css/components/layout.css`:

```css
/**
 * @file
 * Shell layout rules.
 *
 * .lcdle-layout is the top-level container wrapping header + main + footer
 * on every page. The 900px cap matches the validated mockups; content
 * pages (article, profile) add their own inner grid on top of this.
 */

@layer components {
  .lcdle-layout {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 var(--space-5) var(--space-7);
  }

  .lcdle-layout__main {
    padding-block: var(--space-6);
    min-height: 60vh;
  }
}
```

- [ ] **Step 2: Write `html.html.twig` (anti-FOUC)**

`web/themes/custom/lcdle/templates/layout/html.html.twig`:

```twig
{#
/**
 * @file
 * HTML document override for the lcdle theme.
 *
 * The inline script right after <head> reads localStorage synchronously
 * and applies .dark or .light on <html> before any style is painted.
 * This prevents a flash of unstyled-color (FOUC) when the user has
 * picked a theme explicitly.
 */
#}
<!DOCTYPE html>
<html{{ html_attributes }}>
  <head>
    <script>
      (function () {
        try {
          var t = localStorage.getItem('lcdle-theme');
          if (t === 'dark') document.documentElement.classList.add('dark');
          else if (t === 'light') document.documentElement.classList.add('light');
        } catch (e) { /* localStorage may be blocked; fall back to prefers-color-scheme */ }
      })();
    </script>
    <head-placeholder token="{{ placeholder_token }}">
    <title>{{ head_title|safe_join(' | ') }}</title>
    <css-placeholder token="{{ placeholder_token }}">
    <js-placeholder token="{{ placeholder_token }}">
  </head>
  <body{{ attributes }}>
    <a href="#main-content" class="visually-hidden focusable skip-link">
      {{ 'Skip to main content'|t }}
    </a>
    {{ page_top }}
    {{ page }}
    {{ page_bottom }}
    <js-bottom-placeholder token="{{ placeholder_token }}">
  </body>
</html>
```

- [ ] **Step 3: Write `page.html.twig` (shell)**

`web/themes/custom/lcdle/templates/layout/page.html.twig`:

```twig
{#
/**
 * @file
 * Page shell for the lcdle theme.
 *
 * Renders the header SDC, the main content area, and the footer SDC.
 * Other Drupal regions (sidebar_first, sidebar_second, breadcrumb,
 * highlighted) are intentionally NOT rendered — the shell stays focused
 * on header/main/footer until 2B2 introduces real content pages.
 */
#}
<div class="lcdle-layout">
  {% include 'lcdle:header' with {
    site_name: 'La Culture de l\'Écran',
    site_sub: 'Musique · Cinéma · Art · Jeux · Voyages'
  } only %}

  <main id="main-content" class="lcdle-layout__main" role="main">
    {{ page.content }}
  </main>

  {% include 'lcdle:footer' with {
    site_name: 'La Culture de l\'Écran'
  } only %}
</div>
```

Note: `lcdle:header` and `lcdle:footer` will not exist until Tasks 9 and 10. This file will break cache rebuild until then. That's accepted: we commit the shell now, the theme won't render correctly until the atoms are in place, and we fix progressively.

- [ ] **Step 4: Add `components` library entry**

Read the current file:

```bash
cat web/themes/custom/lcdle/lcdle.libraries.yml
```

Replace its content with:

```yaml
# Global library — always loaded via the 'libraries' key in lcdle.info.yml.
# Order matters: layers → tokens → fonts → reset → base.
global:
  version: 1.x
  css:
    base:
      css/layers.css: {}
      css/base/tokens.css: {}
      css/base/tokens-dark.css: {}
      css/base/fonts.css: {}
      css/base/reset.css: {}
      css/base/base.css: {}

# Non-SDC CSS: shell layout + standalone utility classes.
# Every SDC ships its own CSS via sdc plugin manager; this library only
# carries layout.css + utilities.css.
components:
  version: 1.x
  css:
    component:
      css/components/layout.css: {}
      css/components/utilities.css: {}
```

Then add the library to the theme's `libraries` key in `lcdle.info.yml`:

```yaml
libraries:
  - lcdle/global
  - lcdle/components
```

- [ ] **Step 5: Clear cache, ensure no fatal errors**

```bash
ddev drush cr
```

Expected: success. If cache rebuild fails with "Component lcdle:header not found", the include in page.html.twig is expected to break until Task 9 — accept and move on (the theme won't be rendered yet; tests in later tasks will pass after the components exist).

If you want to avoid the breakage until Task 9, temporarily swap the `{% include %}` calls for `<!-- header placeholder -->` and `<!-- footer placeholder -->`. Restore in Task 9 and Task 10 when those components land.

- [ ] **Step 6: Commit**

```bash
git add web/themes/custom/lcdle/css/components/layout.css web/themes/custom/lcdle/templates/layout/ web/themes/custom/lcdle/lcdle.libraries.yml web/themes/custom/lcdle/lcdle.info.yml
git -c commit.gpgsign=false commit -m "Add layout shell (container + page + anti-FOUC html template)

- layout.css: .lcdle-layout = 900px capped container + main region.
- html.html.twig: inline <script> in <head> reads localStorage and
  applies .dark / .light BEFORE the first paint. Prevents the color
  flash when a user has explicitly toggled dark mode. Try/catch guards
  against browsers that block storage access.
- page.html.twig: renders lcdle:header + main + lcdle:footer. The SDCs
  don't exist yet — Tasks 9/10 add them. Intentional: commit the shell
  once so the remaining tasks just slot pieces in.
- components library wired next to global."
```

---

## Task 2 — CSS utilities (tag, lettrine, section-separator)

**Files:**
- Create: `web/themes/custom/lcdle/css/components/utilities.css`

- [ ] **Step 1: Write `utilities.css`**

`web/themes/custom/lcdle/css/components/utilities.css`:

```css
/**
 * @file
 * Small CSS utility patterns that don't deserve full SDCs.
 *
 * .lcdle-tag             — small-caps label with accent color, used in
 *                          cards, article pages, sidebars.
 * .lcdle-lettrine        — drop-cap on the first paragraph of a container.
 *                          Applied as a class on the wrapper (e.g. the
 *                          article body container).
 * .lcdle-section-separator — horizontal divider + label, used on the
 *                          homepage to separate sections.
 */

@layer utilities {
  .lcdle-tag {
    display: inline-block;
    font-family: var(--font-family-sans);
    font-size: var(--fs-xs);
    font-weight: var(--fw-medium);
    letter-spacing: var(--ls-wider);
    text-transform: uppercase;
    color: var(--color-accent);
  }

  .lcdle-lettrine > p:first-of-type::first-letter {
    font-family: var(--font-family-serif);
    font-size: 3.25em;
    font-weight: var(--fw-regular);
    float: left;
    line-height: 0.85;
    margin-inline-end: var(--space-2);
    color: var(--color-text);
  }

  .lcdle-section-separator {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    margin-block: var(--space-7) var(--space-5);
  }

  .lcdle-section-separator__label {
    font-size: var(--fs-xs);
    font-weight: var(--fw-medium);
    letter-spacing: var(--ls-wider);
    text-transform: uppercase;
    color: var(--color-text-subtle);
    white-space: nowrap;
  }

  .lcdle-section-separator::after {
    content: '';
    flex: 1;
    height: var(--bw-hairline);
    background-color: var(--color-border);
  }
}
```

- [ ] **Step 2: Commit**

```bash
git add web/themes/custom/lcdle/css/components/utilities.css
git -c commit.gpgsign=false commit -m "Add CSS utilities: tag, lettrine, section-separator

Three BEM utility patterns that don't clear the bar for a full SDC
(no props, no slots, no JS, no sub-components). They all consume
tokens from Phase 2A and live in @layer utilities so element styles
don't fight them. Reused across cards, article pages, and sidebars
when those arrive in 2B2."
```

---

## Task 3 — `author-chip` SDC

**Files:**
- Create: `web/themes/custom/lcdle/components/author-chip/author-chip.component.yml`
- Create: `web/themes/custom/lcdle/components/author-chip/author-chip.twig`
- Create: `web/themes/custom/lcdle/components/author-chip/author-chip.css`
- Create: `web/modules/custom/lcdle_theme_helpers/tests/src/Kernel/AuthorChipComponentRenderTest.php`

- [ ] **Step 1: Write the failing kernel test**

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_theme_helpers\Kernel;

use Drupal\KernelTests\Components\ComponentKernelTestBase;

/**
 * Renders the author-chip SDC and asserts structural invariants.
 *
 * @group lcdle_theme_helpers
 */
final class AuthorChipComponentRenderTest extends ComponentKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $themes = ['lcdle'];

  public function testRendersNameAndUrl(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:author-chip',
      '#props' => [
        'name' => 'Léa Martineau',
        'url' => '/leamartineau',
        'avatar_initials' => 'LM',
      ],
    ]);

    self::assertCount(1, $crawler->filter('.author-chip'));
    self::assertSame('Léa Martineau', trim($crawler->filter('.author-chip__name')->text()));
    self::assertSame('/leamartineau', $crawler->filter('a.author-chip')->attr('href'));
  }

  public function testAvatarFallbackRendersInitials(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:author-chip',
      '#props' => [
        'name' => 'Hugo B.',
        'url' => '/hugob',
        'avatar_initials' => 'HB',
      ],
    ]);

    self::assertSame('HB', trim($crawler->filter('.author-chip__avatar')->text()));
  }

  public function testSizeModifierApplied(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:author-chip',
      '#props' => [
        'name' => 'Test',
        'url' => '/test',
        'avatar_initials' => 'T',
        'size' => 'lg',
      ],
    ]);

    self::assertCount(1, $crawler->filter('.author-chip--lg'));
  }

}
```

- [ ] **Step 2: Run — expect FAIL**

```bash
ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_theme_helpers/tests/src/Kernel/AuthorChipComponentRenderTest.php"
```

Expected: failures because the `lcdle:author-chip` component doesn't exist yet.

- [ ] **Step 3: Write `author-chip.component.yml`**

`web/themes/custom/lcdle/components/author-chip/author-chip.component.yml`:

```yaml
$schema: https://git.drupalcode.org/project/drupal/-/raw/HEAD/core/assets/schemas/v1/metadata.schema.json
name: Author chip
status: experimental
description: >
  Compact author block: avatar (initials or image) + name, optionally
  with a role label and a link to the author's profile. Three size
  variants (sm / md / lg) for meta bars, sidebars, and contributor
  strips.
props:
  type: object
  required: [name, url, avatar_initials]
  properties:
    name:
      type: string
      title: Display name
    url:
      type: string
      title: Profile URL
    avatar_initials:
      type: string
      title: Avatar initials
      description: Two letters shown when avatar_src is absent.
    avatar_src:
      type: string
      title: Avatar image URL
      description: Takes precedence over avatar_initials when set.
    size:
      type: string
      title: Size variant
      enum: [sm, md, lg]
      default: md
    role:
      type: string
      title: Role label
      description: Optional subtitle under the name (e.g. "Musique classique").
examples:
  default:
    props:
      name: 'Léa Martineau'
      url: '/leamartineau'
      avatar_initials: 'LM'
      size: md
      role: 'Musique classique & ambient'
  large:
    props:
      name: 'Hugo B.'
      url: '/hugob'
      avatar_initials: 'HB'
      size: lg
      role: "Cinéma d'auteur"
```

- [ ] **Step 4: Write `author-chip.twig`**

`web/themes/custom/lcdle/components/author-chip/author-chip.twig`:

```twig
{#
/**
 * @file
 * Author chip template.
 *
 * Props: name, url, avatar_initials, avatar_src, size, role.
 */
#}
<a class="author-chip author-chip--{{ size|default('md') }}" href="{{ url }}" rel="author">
  {% if avatar_src %}
    <img class="author-chip__avatar author-chip__avatar--image" src="{{ avatar_src }}" alt="" />
  {% else %}
    <span class="author-chip__avatar" aria-hidden="true">{{ avatar_initials }}</span>
  {% endif %}
  <span class="author-chip__info">
    <span class="author-chip__name">{{ name }}</span>
    {% if role %}
      <span class="author-chip__role">{{ role }}</span>
    {% endif %}
  </span>
</a>
```

- [ ] **Step 5: Write `author-chip.css`**

`web/themes/custom/lcdle/components/author-chip/author-chip.css`:

```css
@layer components {
  .author-chip {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    color: var(--color-text);
    transition: color var(--duration-fast) ease;
  }

  .author-chip:hover .author-chip__name {
    color: var(--color-accent);
  }

  .author-chip__avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background-color: var(--color-surface);
    border: var(--bw-hairline) solid var(--color-border);
    color: var(--color-text-muted);
    font-family: var(--font-family-sans);
    font-weight: var(--fw-medium);
    flex-shrink: 0;
  }

  .author-chip__avatar--image {
    object-fit: cover;
    background-color: transparent;
  }

  .author-chip__info {
    display: flex;
    flex-direction: column;
    min-width: 0;
  }

  .author-chip__name {
    font-size: var(--fs-sm);
    font-weight: var(--fw-medium);
    color: var(--color-text);
    transition: color var(--duration-fast) ease;
  }

  .author-chip__role {
    font-size: var(--fs-xs);
    color: var(--color-text-subtle);
  }

  /* Size variants. */
  .author-chip--sm .author-chip__avatar {
    width: 20px;
    height: 20px;
    font-size: 9px;
  }

  .author-chip--md .author-chip__avatar {
    width: 26px;
    height: 26px;
    font-size: var(--fs-xs);
  }

  .author-chip--lg .author-chip__avatar {
    width: 38px;
    height: 38px;
    font-size: var(--fs-sm);
  }
}
```

- [ ] **Step 6: Run tests — expect PASS**

```bash
ddev drush cr
ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_theme_helpers/tests/src/Kernel/AuthorChipComponentRenderTest.php"
```

Expected: `OK (3 tests, N assertions)`.

- [ ] **Step 7: phpcs + phpstan sweep**

```bash
ddev exec "vendor/bin/phpcs web/modules/custom/lcdle_theme_helpers web/themes/custom/lcdle --standard=phpcs.xml.dist"
ddev exec "vendor/bin/phpstan analyse web/modules/custom/lcdle_theme_helpers --memory-limit=1G"
```

Expected: 0 errors 0 warnings.

- [ ] **Step 8: Commit**

```bash
git add web/themes/custom/lcdle/components/author-chip/ web/modules/custom/lcdle_theme_helpers/tests/src/Kernel/AuthorChipComponentRenderTest.php
git -c commit.gpgsign=false commit -m "Add author-chip SDC (avatar + name, three sizes)

Reusable block for author meta: shows initials in a circle (or an image
if avatar_src is provided), name, optional role. Three size variants
(sm/md/lg) cover inline meta bars, cards, and sidebars without
duplicating the markup. Renders as <a rel=\"author\"> which will play
well with microformats h-card when added in 2B2. Kernel test guards
the BEM structure and size modifier."
```

---

## Task 4 — `meta-bar` SDC

**Files:**
- Create: `web/themes/custom/lcdle/components/meta-bar/meta-bar.component.yml`
- Create: `web/themes/custom/lcdle/components/meta-bar/meta-bar.twig`
- Create: `web/themes/custom/lcdle/components/meta-bar/meta-bar.css`
- Create: `web/modules/custom/lcdle_theme_helpers/tests/src/Kernel/MetaBarComponentRenderTest.php`

- [ ] **Step 1: Write the failing kernel test**

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_theme_helpers\Kernel;

use Drupal\KernelTests\Components\ComponentKernelTestBase;

/**
 * @group lcdle_theme_helpers
 */
final class MetaBarComponentRenderTest extends ComponentKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $themes = ['lcdle'];

  public function testRendersItemsSlot(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:meta-bar',
      '#slots' => [
        'items' => '<span>12 avril 2025</span><span>8 min</span>',
      ],
    ]);

    self::assertCount(1, $crawler->filter('.meta-bar'));
    self::assertCount(2, $crawler->filter('.meta-bar > span'));
    self::assertStringContainsString('12 avril', $crawler->filter('.meta-bar')->text());
  }

}
```

- [ ] **Step 2: Run — expect FAIL**

- [ ] **Step 3: Write `meta-bar.component.yml`**

`web/themes/custom/lcdle/components/meta-bar/meta-bar.component.yml`:

```yaml
$schema: https://git.drupalcode.org/project/drupal/-/raw/HEAD/core/assets/schemas/v1/metadata.schema.json
name: Meta bar
status: experimental
description: >
  Horizontal list of metadata items (date, reading time, author label,
  etc.), separated by small dots inserted via CSS. Consumers put any
  markup they want in the `items` slot; the separator is presentational.
slots:
  items:
    title: Items
    required: true
    description: The meta items, typically a series of <span> elements.
examples:
  default:
    slots:
      items: |
        <span>Léa Martineau</span>
        <span>12 avril 2025</span>
        <span>8 min</span>
```

- [ ] **Step 4: Write `meta-bar.twig`**

`web/themes/custom/lcdle/components/meta-bar/meta-bar.twig`:

```twig
<div class="meta-bar">
  {% block items %}{% endblock %}
</div>
```

- [ ] **Step 5: Write `meta-bar.css`**

`web/themes/custom/lcdle/components/meta-bar/meta-bar.css`:

```css
@layer components {
  .meta-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-3);
    font-family: var(--font-family-sans);
    font-size: var(--fs-sm);
    color: var(--color-text-subtle);
  }

  /* Dot separator between consecutive children. */
  .meta-bar > :not(:first-child)::before {
    content: '';
    display: inline-block;
    width: 3px;
    height: 3px;
    border-radius: 50%;
    background-color: currentColor;
    margin-inline-end: var(--space-3);
    vertical-align: middle;
  }
}
```

- [ ] **Step 6: Run tests — expect PASS**

```bash
ddev drush cr
ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_theme_helpers/tests/src/Kernel/MetaBarComponentRenderTest.php"
```

- [ ] **Step 7: phpcs + phpstan sweep, 0/0.**

- [ ] **Step 8: Commit**

```bash
git add web/themes/custom/lcdle/components/meta-bar/ web/modules/custom/lcdle_theme_helpers/tests/src/Kernel/MetaBarComponentRenderTest.php
git -c commit.gpgsign=false commit -m "Add meta-bar SDC (dots-separated item list)

Dead-simple slot-only component: wraps a series of span children with
the editorial dot separators seen in the mockups. Pure CSS separators
(no extra markup, no <li>) keep the output clean for microformats and
screen readers."
```

---

## Task 5 — `pullquote` SDC

**Files:**
- Create: `web/themes/custom/lcdle/components/pullquote/pullquote.component.yml`
- Create: `web/themes/custom/lcdle/components/pullquote/pullquote.twig`
- Create: `web/themes/custom/lcdle/components/pullquote/pullquote.css`
- Create: `web/modules/custom/lcdle_theme_helpers/tests/src/Kernel/PullquoteComponentRenderTest.php`

- [ ] **Step 1: Write the failing kernel test**

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_theme_helpers\Kernel;

use Drupal\KernelTests\Components\ComponentKernelTestBase;

/**
 * @group lcdle_theme_helpers
 */
final class PullquoteComponentRenderTest extends ComponentKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $themes = ['lcdle'];

  public function testRendersQuoteAndCite(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:pullquote',
      '#slots' => [
        'quote' => '<p>Je travaille avec peu d\'éléments.</p>',
        'cite' => '— Arvo Pärt, 1978',
      ],
    ]);

    self::assertCount(1, $crawler->filter('blockquote.pullquote'));
    self::assertStringContainsString("Je travaille avec peu d'éléments.", $crawler->filter('.pullquote__quote')->html());
    self::assertStringContainsString('Arvo Pärt', trim($crawler->filter('.pullquote__cite')->text()));
  }

  public function testRendersWithoutCite(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:pullquote',
      '#slots' => [
        'quote' => '<p>Anonymous quote.</p>',
      ],
    ]);

    self::assertCount(1, $crawler->filter('.pullquote__quote'));
    self::assertCount(0, $crawler->filter('.pullquote__cite'));
  }

}
```

- [ ] **Step 2: Run — expect FAIL**

- [ ] **Step 3: Write `pullquote.component.yml`**

```yaml
$schema: https://git.drupalcode.org/project/drupal/-/raw/HEAD/core/assets/schemas/v1/metadata.schema.json
name: Pullquote
status: experimental
description: >
  Editorial pullquote with an accent-colored left rail, serif italic
  quote, and a small attribution line. `cite` slot is optional.
slots:
  quote:
    title: Quote
    required: true
    description: The quoted text, wrapped in <p>.
  cite:
    title: Attribution
    description: Optional attribution (author, year). Rendered plain text.
examples:
  default:
    slots:
      quote: |
        <p>Je travaille avec peu d'éléments — une voix, deux notes.</p>
      cite: '— Arvo Pärt, entretien de 1978'
```

- [ ] **Step 4: Write `pullquote.twig`**

`web/themes/custom/lcdle/components/pullquote/pullquote.twig`:

```twig
<blockquote class="pullquote">
  <div class="pullquote__quote">{% block quote %}{% endblock %}</div>
  {% if block('cite') is not empty %}
    <cite class="pullquote__cite">{% block cite %}{% endblock %}</cite>
  {% endif %}
</blockquote>
```

- [ ] **Step 5: Write `pullquote.css`**

```css
@layer components {
  .pullquote {
    margin-block: var(--space-6);
    margin-inline: 0;
    padding-block: var(--space-2);
    padding-inline-start: var(--space-5);
    border-inline-start: var(--bw-emphasis) solid var(--color-accent);
  }

  .pullquote__quote {
    font-family: var(--font-family-serif);
    font-size: var(--fs-xl);
    font-style: italic;
    line-height: var(--lh-normal);
    color: var(--color-text);
  }

  .pullquote__quote > p {
    margin: 0;
  }

  .pullquote__cite {
    display: block;
    margin-block-start: var(--space-2);
    font-family: var(--font-family-sans);
    font-size: var(--fs-xs);
    font-style: normal;
    letter-spacing: var(--ls-wide);
    color: var(--color-text-subtle);
  }
}
```

- [ ] **Step 6: Run tests — expect PASS. phpcs + phpstan sweep 0/0.**

- [ ] **Step 7: Commit**

```bash
git add web/themes/custom/lcdle/components/pullquote/ web/modules/custom/lcdle_theme_helpers/tests/src/Kernel/PullquoteComponentRenderTest.php
git -c commit.gpgsign=false commit -m "Add pullquote SDC (quote + optional cite)

<blockquote> with accent-colored left rail, serif italic body, sans
small-caps attribution. The cite slot is optional — rendered only when
non-empty, guarded in the Twig template so screen readers don't hear
an empty <cite> when there's no attribution."
```

---

## Task 6 — `dark-mode-toggle` SDC (with JS)

**Files:**
- Create: `web/themes/custom/lcdle/components/dark-mode-toggle/dark-mode-toggle.component.yml`
- Create: `web/themes/custom/lcdle/components/dark-mode-toggle/dark-mode-toggle.twig`
- Create: `web/themes/custom/lcdle/components/dark-mode-toggle/dark-mode-toggle.css`
- Create: `web/themes/custom/lcdle/components/dark-mode-toggle/dark-mode-toggle.js`
- Create: `web/modules/custom/lcdle_theme_helpers/tests/src/Kernel/DarkModeToggleComponentRenderTest.php`

- [ ] **Step 1: Write the failing kernel test**

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_theme_helpers\Kernel;

use Drupal\KernelTests\Components\ComponentKernelTestBase;

/**
 * @group lcdle_theme_helpers
 */
final class DarkModeToggleComponentRenderTest extends ComponentKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $themes = ['lcdle'];

  public function testRendersAccessibleButton(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:dark-mode-toggle',
    ]);

    $button = $crawler->filter('button.dark-mode-toggle');
    self::assertCount(1, $button);
    self::assertNotEmpty($button->attr('aria-label'));
    self::assertSame('false', $button->attr('aria-pressed'));
    self::assertSame('button', $button->attr('type'));
  }

  public function testContainsBothSvgIcons(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:dark-mode-toggle',
    ]);

    // Both icons are present in the DOM; CSS hides one depending on state.
    self::assertCount(2, $crawler->filter('button.dark-mode-toggle svg'));
  }

}
```

- [ ] **Step 2: Run — expect FAIL**

- [ ] **Step 3: Write `dark-mode-toggle.component.yml`**

```yaml
$schema: https://git.drupalcode.org/project/drupal/-/raw/HEAD/core/assets/schemas/v1/metadata.schema.json
name: Dark mode toggle
status: experimental
description: >
  Button that flips between light and dark themes. Source of truth is
  localStorage['lcdle-theme']. When absent, the theme follows OS
  preference via prefers-color-scheme. Both sun and moon SVGs are in
  the DOM; CSS shows the right one based on the active mode. Behavior
  attached via Drupal.behaviors.lcdleDarkMode.
libraryOverrides:
  js:
    dark-mode-toggle.js: {}
  dependencies:
    - core/drupal
    - core/once
examples:
  default: {}
```

- [ ] **Step 4: Write `dark-mode-toggle.twig`**

`web/themes/custom/lcdle/components/dark-mode-toggle/dark-mode-toggle.twig`:

```twig
<button type="button"
        class="dark-mode-toggle"
        aria-label="{{ 'Basculer en mode sombre'|t }}"
        aria-pressed="false">
  <svg class="dark-mode-toggle__icon dark-mode-toggle__icon--moon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
  </svg>
  <svg class="dark-mode-toggle__icon dark-mode-toggle__icon--sun" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <circle cx="12" cy="12" r="4"></circle>
    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path>
  </svg>
</button>
```

- [ ] **Step 5: Write `dark-mode-toggle.css`**

```css
@layer components {
  .dark-mode-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: var(--bw-hairline) solid var(--color-border);
    background-color: var(--color-surface);
    color: var(--color-text-muted);
    cursor: pointer;
    transition:
      color var(--duration-fast) ease,
      background-color var(--duration-fast) ease,
      border-color var(--duration-fast) ease;
  }

  .dark-mode-toggle:hover {
    color: var(--color-text);
    border-color: var(--color-text-muted);
  }

  .dark-mode-toggle:focus-visible {
    outline: 2px solid var(--color-accent);
    outline-offset: 2px;
  }

  .dark-mode-toggle__icon {
    display: block;
  }

  /* Default (light mode): show moon (click to go dark), hide sun. */
  .dark-mode-toggle__icon--sun { display: none; }

  /* Dark mode: swap icons. */
  :root.dark .dark-mode-toggle__icon--moon { display: none; }
  :root.dark .dark-mode-toggle__icon--sun { display: block; }

  /* Auto dark (no user preference, OS is dark): same as .dark. */
  @media (prefers-color-scheme: dark) {
    :root:not(.light) .dark-mode-toggle__icon--moon { display: none; }
    :root:not(.light) .dark-mode-toggle__icon--sun { display: block; }
  }
}
```

- [ ] **Step 6: Write `dark-mode-toggle.js`**

`web/themes/custom/lcdle/components/dark-mode-toggle/dark-mode-toggle.js`:

```javascript
/**
 * @file
 * Dark mode toggle behavior.
 *
 * Source of truth: localStorage['lcdle-theme'] ∈ {'dark', 'light'} or absent.
 * Absent = follow prefers-color-scheme. The inline snippet in html.html.twig
 * applies the correct class pre-paint; this behavior keeps the button's
 * aria-pressed and label in sync with the current state and handles clicks.
 */
(function (Drupal, once) {
  'use strict';

  var STORAGE_KEY = 'lcdle-theme';

  function currentMode() {
    var stored = null;
    try { stored = localStorage.getItem(STORAGE_KEY); } catch (e) {}
    if (stored === 'dark' || stored === 'light') { return stored; }
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      return 'dark';
    }
    return 'light';
  }

  function apply(mode, button) {
    var root = document.documentElement;
    root.classList.remove('dark', 'light');
    root.classList.add(mode);
    try { localStorage.setItem(STORAGE_KEY, mode); } catch (e) {}
    var labelToDark = Drupal.t('Basculer en mode sombre');
    var labelToLight = Drupal.t('Basculer en mode clair');
    button.setAttribute('aria-pressed', mode === 'dark' ? 'true' : 'false');
    button.setAttribute('aria-label', mode === 'dark' ? labelToLight : labelToDark);
  }

  Drupal.behaviors.lcdleDarkMode = {
    attach: function (context) {
      once('lcdle-dark-mode', '.dark-mode-toggle', context).forEach(function (button) {
        // Sync button state with actual current mode.
        apply(currentMode(), button);
        button.addEventListener('click', function () {
          var next = currentMode() === 'dark' ? 'light' : 'dark';
          apply(next, button);
        });
      });
    }
  };
})(Drupal, once);
```

- [ ] **Step 7: Run tests — expect PASS. phpcs + phpstan sweep 0/0.**

- [ ] **Step 8: Commit**

```bash
git add web/themes/custom/lcdle/components/dark-mode-toggle/ web/modules/custom/lcdle_theme_helpers/tests/src/Kernel/DarkModeToggleComponentRenderTest.php
git -c commit.gpgsign=false commit -m "Add dark-mode-toggle SDC (button + JS behavior)

Round button in the header that flips between light and dark themes.
Both sun and moon SVGs ship in the DOM; CSS shows the right one based
on :root.dark / :root.light / prefers-color-scheme, so there's no
icon flash at mode-switch time. JS reads and writes
localStorage['lcdle-theme'] as the explicit user choice; the absence
of that key means 'follow the OS'. aria-pressed + aria-label stay in
sync with the effective mode. Attached via Drupal.behaviors +
core/once so repeated AJAX renders don't double-bind."
```

---

## Task 7 — `article-card` SDC

**Files:**
- Create: `web/themes/custom/lcdle/components/article-card/article-card.component.yml`
- Create: `web/themes/custom/lcdle/components/article-card/article-card.twig`
- Create: `web/themes/custom/lcdle/components/article-card/article-card.css`
- Create: `web/modules/custom/lcdle_theme_helpers/tests/src/Kernel/ArticleCardComponentRenderTest.php`

- [ ] **Step 1: Write the failing kernel test**

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_theme_helpers\Kernel;

use Drupal\KernelTests\Components\ComponentKernelTestBase;

/**
 * @group lcdle_theme_helpers
 */
final class ArticleCardComponentRenderTest extends ComponentKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $themes = ['lcdle'];

  public function testRendersAllFields(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:article-card',
      '#props' => [
        'title' => 'Wim Wenders à Tokyo',
        'url' => '/hugob/wim-wenders-tokyo',
        'tag' => 'Cinéma',
        'author_label' => 'Hugo B.',
        'reading_time' => '8 min',
      ],
    ]);

    self::assertCount(1, $crawler->filter('article.article-card'));
    self::assertStringContainsString('Wim Wenders à Tokyo', $crawler->filter('.article-card__title')->text());
    self::assertStringContainsString('Cinéma', $crawler->filter('.article-card__tag')->text());
    self::assertStringContainsString('Hugo B.', $crawler->filter('.article-card__meta')->text());
    self::assertSame('/hugob/wim-wenders-tokyo', $crawler->filter('.article-card__title a')->attr('href'));
  }

  public function testCoverPlaceholderWhenNoImage(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:article-card',
      '#props' => [
        'title' => 'Test',
        'url' => '/test',
        'tag' => 'Test',
        'author_label' => 'Anon',
        'reading_time' => '1 min',
      ],
    ]);

    self::assertCount(1, $crawler->filter('.article-card__cover--placeholder'));
  }

}
```

- [ ] **Step 2: Run — expect FAIL**

- [ ] **Step 3: Write `article-card.component.yml`**

```yaml
$schema: https://git.drupalcode.org/project/drupal/-/raw/HEAD/core/assets/schemas/v1/metadata.schema.json
name: Article card
status: experimental
description: >
  Teaser card for an article: cover image (or 4:3 surface placeholder),
  tag label, serif title, small meta line with author + reading time.
  Used on homepage, theme pages, chronique pages.
props:
  type: object
  required: [title, url, tag, author_label, reading_time]
  properties:
    title:
      type: string
      title: Article title
    url:
      type: string
      title: Article URL
    tag:
      type: string
      title: Primary tag label
    author_label:
      type: string
      title: Author display name
    reading_time:
      type: string
      title: Reading time (e.g. "8 min")
slots:
  cover:
    title: Cover image
    description: >
      Responsive image render array. When empty, a 4:3 surface placeholder
      renders in its place.
examples:
  default:
    props:
      title: "Wim Wenders à Tokyo — l'ordinaire comme révélation"
      url: '/hugob/wim-wenders-a-tokyo'
      tag: 'Cinéma'
      author_label: 'Hugo B.'
      reading_time: '8 min'
```

- [ ] **Step 4: Write `article-card.twig`**

```twig
<article class="article-card">
  <a class="article-card__cover-link" href="{{ url }}" tabindex="-1" aria-hidden="true">
    {% if block('cover') is not empty %}
      <div class="article-card__cover">{% block cover %}{% endblock %}</div>
    {% else %}
      <div class="article-card__cover article-card__cover--placeholder" aria-hidden="true">4:3</div>
    {% endif %}
  </a>
  <span class="article-card__tag lcdle-tag">{{ tag }}</span>
  <h3 class="article-card__title">
    <a href="{{ url }}">{{ title }}</a>
  </h3>
  <div class="article-card__meta">
    <span class="article-card__author">{{ author_label }}</span>
    <span class="article-card__dot" aria-hidden="true">·</span>
    <span class="article-card__time">{{ reading_time }}</span>
  </div>
</article>
```

- [ ] **Step 5: Write `article-card.css`**

```css
@layer components {
  .article-card {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
  }

  .article-card__cover-link {
    display: block;
  }

  .article-card__cover {
    aspect-ratio: 4 / 3;
    background-color: var(--color-surface);
    border: var(--bw-hairline) solid var(--color-border);
    border-radius: var(--radius-md);
    overflow: hidden;
    margin-block-end: var(--space-3);
  }

  .article-card__cover--placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: var(--font-family-sans);
    font-size: var(--fs-xs);
    font-style: italic;
    color: var(--color-text-subtle);
  }

  .article-card__tag {
    margin-block-end: var(--space-1);
  }

  .article-card__title {
    font-family: var(--font-family-serif);
    font-size: var(--fs-lg);
    font-weight: var(--fw-regular);
    line-height: var(--lh-tight);
    letter-spacing: var(--ls-tight);
    margin: 0;
  }

  .article-card__title a {
    color: var(--color-text);
    transition: color var(--duration-fast) ease;
  }

  .article-card__title a:hover {
    color: var(--color-accent);
  }

  .article-card__meta {
    font-family: var(--font-family-sans);
    font-size: var(--fs-xs);
    color: var(--color-text-subtle);
  }

  .article-card__dot {
    margin-inline: var(--space-1);
  }
}
```

- [ ] **Step 6: Run tests — expect PASS. phpcs + phpstan sweep 0/0.**

- [ ] **Step 7: Commit**

```bash
git add web/themes/custom/lcdle/components/article-card/ web/modules/custom/lcdle_theme_helpers/tests/src/Kernel/ArticleCardComponentRenderTest.php
git -c commit.gpgsign=false commit -m "Add article-card SDC (cover + tag + serif title + meta)

Teaser card for the reading grid on homepage + theme/chronique pages.
Cover slot takes a responsive-image render array; when absent, a 4:3
surface-color placeholder renders in its place so the layout doesn't
collapse. The cover link is duplicated (aria-hidden on the wrapper +
live link on the title) to keep click area large while giving screen
readers a single focusable anchor."
```

---

## Task 8 — `wide-card` SDC

**Files:**
- Create: `web/themes/custom/lcdle/components/wide-card/wide-card.component.yml`
- Create: `web/themes/custom/lcdle/components/wide-card/wide-card.twig`
- Create: `web/themes/custom/lcdle/components/wide-card/wide-card.css`
- Create: `web/modules/custom/lcdle_theme_helpers/tests/src/Kernel/WideCardComponentRenderTest.php`

- [ ] **Step 1: Write the failing kernel test**

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_theme_helpers\Kernel;

use Drupal\KernelTests\Components\ComponentKernelTestBase;

/**
 * @group lcdle_theme_helpers
 */
final class WideCardComponentRenderTest extends ComponentKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $themes = ['lcdle'];

  public function testRendersAllFields(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:wide-card',
      '#props' => [
        'title' => 'FIP la nuit — une radio pour ne pas dormir',
        'url' => '/gust/fip-la-nuit',
        'tag' => 'Électronique',
        'author_label' => 'Gus T.',
        'published_date' => '10 avr.',
        'reading_time' => '5 min',
      ],
    ]);

    self::assertCount(1, $crawler->filter('article.wide-card'));
    self::assertStringContainsString('FIP la nuit', $crawler->filter('.wide-card__title')->text());
    self::assertStringContainsString('Électronique', $crawler->filter('.wide-card__tag')->text());
    self::assertStringContainsString('Gus T.', $crawler->filter('.wide-card__meta')->text());
    self::assertStringContainsString('5 min', $crawler->filter('.wide-card__meta')->text());
  }

  public function testNoCoverImageEverRendered(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:wide-card',
      '#props' => [
        'title' => 'T', 'url' => '/t', 'tag' => 'X',
        'author_label' => 'A', 'published_date' => '1 janv.', 'reading_time' => '1 min',
      ],
    ]);

    self::assertCount(0, $crawler->filter('img'));
    self::assertCount(0, $crawler->filter('.wide-card__cover'));
  }

}
```

- [ ] **Step 2: Run — expect FAIL**

- [ ] **Step 3: Write `wide-card.component.yml`**

```yaml
$schema: https://git.drupalcode.org/project/drupal/-/raw/HEAD/core/assets/schemas/v1/metadata.schema.json
name: Wide card
status: experimental
description: >
  Image-less horizontal card used on the homepage's "Ailleurs" section.
  Tag + serif title + meta on one row. Designed to list articles where
  the cover isn't the draw.
props:
  type: object
  required: [title, url, tag, author_label, published_date, reading_time]
  properties:
    title: { type: string, title: 'Article title' }
    url: { type: string, title: 'Article URL' }
    tag: { type: string, title: 'Primary tag label' }
    author_label: { type: string, title: 'Author display name' }
    published_date: { type: string, title: 'Published date (short, e.g. "10 avr.")' }
    reading_time: { type: string, title: 'Reading time (e.g. "5 min")' }
examples:
  default:
    props:
      title: 'FIP la nuit — une radio pour ne pas dormir'
      url: '/gust/fip-la-nuit'
      tag: 'Électronique'
      author_label: 'Gus T.'
      published_date: '10 avr.'
      reading_time: '5 min'
```

- [ ] **Step 4: Write `wide-card.twig`**

```twig
<article class="wide-card">
  <span class="wide-card__tag lcdle-tag">{{ tag }}</span>
  <h3 class="wide-card__title">
    <a href="{{ url }}">{{ title }}</a>
  </h3>
  <div class="wide-card__meta">
    <span>{{ author_label }}</span>
    <span class="wide-card__dot" aria-hidden="true">·</span>
    <span>{{ published_date }}</span>
    <span class="wide-card__dot" aria-hidden="true">·</span>
    <span>{{ reading_time }}</span>
  </div>
</article>
```

- [ ] **Step 5: Write `wide-card.css`**

```css
@layer components {
  .wide-card {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    padding-block-start: var(--space-4);
    border-block-start: var(--bw-hairline) solid var(--color-border);
  }

  .wide-card__tag {
    /* Inherits .lcdle-tag from utilities. */
  }

  .wide-card__title {
    font-family: var(--font-family-serif);
    font-size: var(--fs-xl);
    font-weight: var(--fw-regular);
    line-height: var(--lh-tight);
    letter-spacing: var(--ls-tight);
    margin: 0;
  }

  .wide-card__title a {
    color: var(--color-text);
    transition: color var(--duration-fast) ease;
  }

  .wide-card__title a:hover {
    color: var(--color-accent);
  }

  .wide-card__meta {
    font-family: var(--font-family-sans);
    font-size: var(--fs-xs);
    color: var(--color-text-subtle);
  }

  .wide-card__dot {
    margin-inline: var(--space-1);
  }
}
```

- [ ] **Step 6: Run tests — expect PASS. phpcs + phpstan sweep 0/0.**

- [ ] **Step 7: Commit**

```bash
git add web/themes/custom/lcdle/components/wide-card/ web/modules/custom/lcdle_theme_helpers/tests/src/Kernel/WideCardComponentRenderTest.php
git -c commit.gpgsign=false commit -m "Add wide-card SDC (tag + serif title + meta, no cover)

Companion to article-card for the 'Ailleurs' section of the homepage
where we list articles that don't warrant a cover. Visually lighter
(hairline top border instead of image), structurally different
(no cover prop, no slot). Separate SDC rather than a variant per the
pragmatic granularity rule from SPEC-003 §3."
```

---

## Task 9 — `header` SDC

**Files:**
- Create: `web/themes/custom/lcdle/components/header/header.component.yml`
- Create: `web/themes/custom/lcdle/components/header/header.twig`
- Create: `web/themes/custom/lcdle/components/header/header.css`
- Create: `web/modules/custom/lcdle_theme_helpers/tests/src/Kernel/HeaderComponentRenderTest.php`

- [ ] **Step 1: Write the failing kernel test**

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_theme_helpers\Kernel;

use Drupal\KernelTests\Components\ComponentKernelTestBase;

/**
 * @group lcdle_theme_helpers
 */
final class HeaderComponentRenderTest extends ComponentKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $themes = ['lcdle'];

  public function testRendersSiteNameAndTagline(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:header',
      '#props' => [
        'site_name' => "La Culture de l'Écran",
        'site_sub' => 'Musique · Cinéma · Art',
      ],
    ]);

    self::assertCount(1, $crawler->filter('header.header'));
    self::assertStringContainsString("La Culture de l'Écran", $crawler->filter('.header__site-name')->text());
    self::assertStringContainsString('Musique', $crawler->filter('.header__site-sub')->text());
  }

  public function testEmbedsDarkModeToggle(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:header',
      '#props' => ['site_name' => 'X', 'site_sub' => 'Y'],
    ]);

    self::assertCount(1, $crawler->filter('header.header button.dark-mode-toggle'));
  }

  public function testNavSlotRendered(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:header',
      '#props' => ['site_name' => 'X', 'site_sub' => 'Y'],
      '#slots' => [
        'nav' => '<a href="/articles">Articles</a><a href="/contribs">Contributeurs</a>',
      ],
    ]);

    self::assertCount(2, $crawler->filter('.header__nav a'));
  }

}
```

- [ ] **Step 2: Run — expect FAIL**

- [ ] **Step 3: Write `header.component.yml`**

```yaml
$schema: https://git.drupalcode.org/project/drupal/-/raw/HEAD/core/assets/schemas/v1/metadata.schema.json
name: Header
status: experimental
description: >
  Top bar: type-set site name (Playfair Display black italic with a green
  text-shadow that recreates the original logo), tagline, navigation
  links slot, embedded dark-mode-toggle. The SDC always includes the
  toggle — the host page shouldn't have to know about it.
props:
  type: object
  required: [site_name]
  properties:
    site_name:
      type: string
      title: Site name
    site_sub:
      type: string
      title: Tagline
      description: Short line shown under the site name.
slots:
  nav:
    title: Navigation
    description: >
      Navigation links, rendered as raw markup. Typically a set of <a> elements.
examples:
  default:
    props:
      site_name: "La Culture de l'Écran"
      site_sub: 'Musique · Cinéma · Art · Jeux · Voyages'
    slots:
      nav: |
        <a href="/articles">Articles</a>
        <a href="/contributeurs">Contributeurs</a>
        <a href="/a-propos">À propos</a>
```

- [ ] **Step 4: Write `header.twig`**

```twig
<header class="header">
  <div class="header__brand">
    <a class="header__site-name-link" href="/" rel="home">
      <span class="header__site-name">{{ site_name }}</span>
    </a>
    {% if site_sub %}
      <span class="header__site-sub">{{ site_sub }}</span>
    {% endif %}
  </div>
  <div class="header__actions">
    <nav class="header__nav" aria-label="{{ 'Navigation principale'|t }}">
      {% block nav %}{% endblock %}
    </nav>
    {% include 'lcdle:dark-mode-toggle' %}
  </div>
</header>
```

- [ ] **Step 5: Write `header.css`**

```css
@layer components {
  .header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-5);
    padding-block: var(--space-5);
    border-block-end: var(--bw-hairline) solid var(--color-border);
    margin-block-end: var(--space-6);
  }

  .header__brand {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
    min-width: 0;
  }

  .header__site-name-link {
    color: inherit;
    text-decoration: none;
  }

  .header__site-name {
    font-family: var(--font-family-serif);
    font-weight: var(--fw-black);
    font-style: italic;
    font-size: var(--fs-2xl);
    letter-spacing: var(--ls-tight);
    color: var(--color-accent);
    text-shadow: 3px 3px 0 var(--color-accent-shadow);
  }

  .header__site-sub {
    font-family: var(--font-family-sans);
    font-size: var(--fs-xs);
    letter-spacing: var(--ls-wider);
    text-transform: uppercase;
    color: var(--color-text-subtle);
  }

  .header__actions {
    display: flex;
    align-items: center;
    gap: var(--space-5);
  }

  .header__nav {
    display: flex;
    align-items: center;
    gap: var(--space-5);
  }

  .header__nav a {
    font-family: var(--font-family-sans);
    font-size: var(--fs-sm);
    color: var(--color-text-muted);
    transition: color var(--duration-fast) ease;
  }

  .header__nav a:hover,
  .header__nav a:focus-visible {
    color: var(--color-text);
  }

  /* Collapse on small screens. */
  @media (max-width: 480px) {
    .header {
      flex-direction: column;
      align-items: flex-start;
    }
    .header__actions {
      width: 100%;
      justify-content: space-between;
    }
  }
}
```

- [ ] **Step 6: Run tests — expect PASS. phpcs + phpstan sweep 0/0.**

- [ ] **Step 7: Commit**

```bash
git add web/themes/custom/lcdle/components/header/ web/modules/custom/lcdle_theme_helpers/tests/src/Kernel/HeaderComponentRenderTest.php
git -c commit.gpgsign=false commit -m "Add header SDC (type-set logo + nav + dark mode toggle)

Type-set logo uses Playfair Display 900 italic + a 3px green
text-shadow to recreate the original brand without shipping a raster
image. The dark-mode-toggle SDC is ALWAYS included — header consumers
don't have to know it exists. Nav slot accepts any markup, typically
a handful of <a> elements. Collapses to a stacked layout under 480px."
```

---

## Task 10 — `footer` SDC

**Files:**
- Create: `web/themes/custom/lcdle/components/footer/footer.component.yml`
- Create: `web/themes/custom/lcdle/components/footer/footer.twig`
- Create: `web/themes/custom/lcdle/components/footer/footer.css`
- Create: `web/modules/custom/lcdle_theme_helpers/tests/src/Kernel/FooterComponentRenderTest.php`

- [ ] **Step 1: Write the failing kernel test**

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_theme_helpers\Kernel;

use Drupal\KernelTests\Components\ComponentKernelTestBase;

/**
 * @group lcdle_theme_helpers
 */
final class FooterComponentRenderTest extends ComponentKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $themes = ['lcdle'];

  public function testRendersSiteNameAndLinksSlot(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:footer',
      '#props' => ['site_name' => "La Culture de l'Écran"],
      '#slots' => [
        'links' => '<a href="/a-propos">À propos</a><a href="/rss">RSS</a>',
      ],
    ]);

    self::assertCount(1, $crawler->filter('footer.footer'));
    self::assertStringContainsString("La Culture de l'Écran", $crawler->filter('.footer__site-name')->text());
    self::assertCount(2, $crawler->filter('.footer__links a'));
  }

}
```

- [ ] **Step 2: Run — expect FAIL**

- [ ] **Step 3: Write `footer.component.yml`**

```yaml
$schema: https://git.drupalcode.org/project/drupal/-/raw/HEAD/core/assets/schemas/v1/metadata.schema.json
name: Footer
status: experimental
description: >
  Simple site footer: site name on the left, a set of links on the right.
  No images, no social icons, no newsletter CTA at MVP (those arrive in 2B2 or later).
props:
  type: object
  required: [site_name]
  properties:
    site_name:
      type: string
      title: Site name
slots:
  links:
    title: Footer links
    description: Raw markup (typically <a> elements).
examples:
  default:
    props:
      site_name: "La Culture de l'Écran"
    slots:
      links: |
        <a href="/a-propos">À propos</a>
        <a href="/contact">Contact</a>
        <a href="/rss">RSS</a>
```

- [ ] **Step 4: Write `footer.twig`**

```twig
<footer class="footer">
  <span class="footer__site-name">{{ site_name }}</span>
  <nav class="footer__links" aria-label="{{ 'Liens de pied de page'|t }}">
    {% block links %}{% endblock %}
  </nav>
</footer>
```

- [ ] **Step 5: Write `footer.css`**

```css
@layer components {
  .footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-5);
    padding-block: var(--space-5);
    border-block-start: var(--bw-hairline) solid var(--color-border);
    margin-block-start: var(--space-8);
  }

  .footer__site-name {
    font-family: var(--font-family-serif);
    font-size: var(--fs-base);
    color: var(--color-text-muted);
  }

  .footer__links {
    display: flex;
    gap: var(--space-5);
  }

  .footer__links a {
    font-family: var(--font-family-sans);
    font-size: var(--fs-xs);
    color: var(--color-text-subtle);
    transition: color var(--duration-fast) ease;
  }

  .footer__links a:hover,
  .footer__links a:focus-visible {
    color: var(--color-text);
  }
}
```

- [ ] **Step 6: Run tests — expect PASS. phpcs + phpstan sweep 0/0.**

- [ ] **Step 7: Commit**

```bash
git add web/themes/custom/lcdle/components/footer/ web/modules/custom/lcdle_theme_helpers/tests/src/Kernel/FooterComponentRenderTest.php
git -c commit.gpgsign=false commit -m "Add footer SDC (site name + links row)

Intentionally minimal: site name on the left, link group on the right,
no social icons, no newsletter CTA. The newsletter block arrives in
2B2 or later as a separate region above the footer — not inside it."
```

---

## Task 11 — System pages 404 + 403

**Files:**
- Create: `web/themes/custom/lcdle/templates/system/page--system--404.html.twig`
- Create: `web/themes/custom/lcdle/templates/system/page--system--403.html.twig`

- [ ] **Step 1: Write the 404 template**

`web/themes/custom/lcdle/templates/system/page--system--404.html.twig`:

```twig
{#
/**
 * @file
 * 404 page override. Inherits the shell layout from page.html.twig.
 * Content replaces the default Drupal 404 message with a localized,
 * typographic error screen.
 */
#}
{% extends "@lcdle/layout/page.html.twig" %}

{% block content %}
  <div class="lcdle-system-page">
    <p class="lcdle-tag">{{ 'Erreur 404'|t }}</p>
    <h1 class="lcdle-system-page__title">{{ 'Page introuvable'|t }}</h1>
    <p class="lcdle-system-page__body">{{ "La page que vous cherchez n'existe pas ou a été déplacée."|t }}</p>
    <a class="lcdle-system-page__action" href="/">{{ "Retour à l'accueil"|t }} →</a>
  </div>
{% endblock %}
```

Note: the `{% extends %}` + `{% block content %}` pattern depends on `page.html.twig` defining a `content` block. Since our `page.html.twig` currently puts `{{ page.content }}` directly, adjust `page.html.twig` to wrap it:

Change `page.html.twig` `<main>`:

```twig
<main id="main-content" class="lcdle-layout__main" role="main">
  {% block content %}{{ page.content }}{% endblock %}
</main>
```

This lets system pages override just the content region.

- [ ] **Step 2: Write the 403 template**

`web/themes/custom/lcdle/templates/system/page--system--403.html.twig`:

```twig
{% extends "@lcdle/layout/page.html.twig" %}

{% block content %}
  <div class="lcdle-system-page">
    <p class="lcdle-tag">{{ 'Erreur 403'|t }}</p>
    <h1 class="lcdle-system-page__title">{{ 'Accès refusé'|t }}</h1>
    <p class="lcdle-system-page__body">{{ "Vous n'êtes pas autorisé à accéder à cette page."|t }}</p>
    {% if logged_in %}
      <a class="lcdle-system-page__action" href="/">{{ "Retour à l'accueil"|t }} →</a>
    {% else %}
      <a class="lcdle-system-page__action" href="/user/login">{{ 'Se connecter'|t }} →</a>
    {% endif %}
  </div>
{% endblock %}
```

- [ ] **Step 3: Add minimal styles for system pages in utilities.css**

Append to `web/themes/custom/lcdle/css/components/utilities.css` (inside `@layer utilities`):

```css
  .lcdle-system-page {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    padding-block: var(--space-8);
    max-width: 640px;
  }

  .lcdle-system-page__title {
    font-family: var(--font-family-serif);
    font-size: var(--fs-4xl);
    font-weight: var(--fw-regular);
    line-height: var(--lh-tight);
    letter-spacing: var(--ls-tight);
    color: var(--color-text);
    margin: 0;
  }

  .lcdle-system-page__body {
    font-size: var(--fs-base);
    line-height: var(--lh-normal);
    color: var(--color-text-muted);
    margin: 0;
  }

  .lcdle-system-page__action {
    font-family: var(--font-family-sans);
    font-size: var(--fs-sm);
    color: var(--color-accent);
    letter-spacing: var(--ls-wide);
    align-self: flex-start;
  }
```

- [ ] **Step 4: Clear cache, manual check via curl**

```bash
ddev drush cr
curl -sk https://lcdle.ddev.site/some-non-existent-page | grep -E "(Page introuvable|Erreur 404)" | head -3
```

Expected: the phrases appear in the 404 HTML.

- [ ] **Step 5: phpcs + phpstan sweep 0/0.**

- [ ] **Step 6: Commit**

```bash
git add web/themes/custom/lcdle/templates/system/page--system--404.html.twig web/themes/custom/lcdle/templates/system/page--system--403.html.twig web/themes/custom/lcdle/templates/layout/page.html.twig web/themes/custom/lcdle/css/components/utilities.css
git -c commit.gpgsign=false commit -m "Add 404 + 403 system pages with a minimal editorial layout

Both extend the shell page.html.twig via a new content block so the
header + footer stay consistent. Strings are French (user-facing)
through Drupal's t() so future translations can layer on top.
Added .lcdle-system-page utility class (serif title, muted body,
accent CTA link) — reused by the maintenance page next."
```

---

## Task 12 — `maintenance-page.html.twig`

**Files:**
- Create: `web/themes/custom/lcdle/templates/system/maintenance-page.html.twig`

The maintenance page runs in a stripped-down Drupal context (modules often can't be invoked). We can't rely on the full shell. Inline the minimum.

- [ ] **Step 1: Write the maintenance template**

`web/themes/custom/lcdle/templates/system/maintenance-page.html.twig`:

```twig
{#
/**
 * @file
 * Standalone maintenance page.
 *
 * Drupal serves this when the site is in maintenance mode, often while
 * plugins and themes are partially available. We ship a self-contained
 * page with tokens and typography inlined so the visitor still gets the
 * right brand language.
 */
#}
<!DOCTYPE html>
<html lang="{{ language.getId() ?: 'fr' }}">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ head_title|safe_join(' | ') }}</title>
    <style>
      :root {
        --color-bg: #FAFAF8;
        --color-surface: #F2F0EB;
        --color-text: #1A1A18;
        --color-text-muted: #6B6B62;
        --color-accent: #C84B2A;
        --color-accent-shadow: #2D7A4A;
      }
      @media (prefers-color-scheme: dark) {
        :root {
          --color-bg: #141412;
          --color-surface: #1E1E1B;
          --color-text: #EDEDE8;
          --color-text-muted: #A8A89F;
          --color-accent: #E06040;
          --color-accent-shadow: #4DA371;
        }
      }
      body {
        margin: 0;
        font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
        background-color: var(--color-bg);
        color: var(--color-text);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
      }
      .maintenance { max-width: 640px; text-align: center; display: flex; flex-direction: column; gap: 1rem; }
      .maintenance__brand {
        font-family: 'Playfair Display', Georgia, serif;
        font-weight: 900;
        font-style: italic;
        font-size: 2rem;
        color: var(--color-accent);
        text-shadow: 3px 3px 0 var(--color-accent-shadow);
        margin: 0;
      }
      .maintenance__title {
        font-family: 'Playfair Display', Georgia, serif;
        font-weight: 400;
        font-size: 1.75rem;
        margin: 0;
      }
      .maintenance__body {
        color: var(--color-text-muted);
        line-height: 1.55;
        margin: 0;
      }
    </style>
  </head>
  <body>
    <div class="maintenance" role="main">
      <h1 class="maintenance__brand">{{ 'La Culture de l\'Écran'|t }}</h1>
      <h2 class="maintenance__title">{{ 'Site en maintenance'|t }}</h2>
      <p class="maintenance__body">{{ "Le site est temporairement indisponible pour maintenance. Merci de revenir dans un instant."|t }}</p>
    </div>
  </body>
</html>
```

- [ ] **Step 2: Clear cache**

```bash
ddev drush cr
```

- [ ] **Step 3: phpcs + phpstan sweep 0/0.**

- [ ] **Step 4: Commit**

```bash
git add web/themes/custom/lcdle/templates/system/maintenance-page.html.twig
git -c commit.gpgsign=false commit -m "Add a self-contained maintenance page

Drupal serves the maintenance template when modules are only partially
loaded — we can't assume our libraries are attached. Inlines the
minimum tokens + font fallbacks so visitors still see the brand
typography and colors, including a working dark-mode hint via
prefers-color-scheme. No JS, no external assets."
```

---

## Task 13 — Placeholder homepage controller + route

**Files:**
- Create: `web/modules/custom/lcdle_theme_helpers/lcdle_theme_helpers.routing.yml`
- Create: `web/modules/custom/lcdle_theme_helpers/src/Controller/HomepagePlaceholderController.php`

- [ ] **Step 1: Write the routing file**

`web/modules/custom/lcdle_theme_helpers/lcdle_theme_helpers.routing.yml`:

```yaml
lcdle_theme_helpers.homepage_placeholder:
  path: '/'
  defaults:
    _controller: 'Drupal\lcdle_theme_helpers\Controller\HomepagePlaceholderController::build'
    _title: "La Culture de l'Écran"
  requirements:
    _access: 'TRUE'
```

- [ ] **Step 2: Write the controller**

`web/modules/custom/lcdle_theme_helpers/src/Controller/HomepagePlaceholderController.php`:

```php
<?php

declare(strict_types=1);

namespace Drupal\lcdle_theme_helpers\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Serves a placeholder homepage for Phase 2B1.
 *
 * The real homepage (aggregating latest articles, featured, contributor
 * strip) arrives in Phase 2B2. This placeholder exists only so anonymous
 * visitors can actually see the shell (header + footer + dark mode
 * toggle) during 2B1 development.
 */
final class HomepagePlaceholderController extends ControllerBase {

  /**
   * Builds the placeholder render array.
   *
   * @return array<string, mixed>
   */
  public function build(): array {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['lcdle-system-page']],
      'tag' => [
        '#markup' => '<p class="lcdle-tag">' . $this->t('En construction') . '</p>',
      ],
      'title' => [
        '#markup' => '<h1 class="lcdle-system-page__title">' . $this->t("Bientôt") . '</h1>',
      ],
      'body' => [
        '#markup' => '<p class="lcdle-system-page__body">'
          . $this->t("Les pages arrivent en Phase 2B2. En attendant, ce shell valide que l'en-tête, le pied et le bascule mode sombre fonctionnent.")
          . '</p>',
      ],
    ];
  }

}
```

- [ ] **Step 3: Clear cache**

```bash
ddev drush cr
```

- [ ] **Step 4: Check the placeholder renders**

```bash
curl -sk https://lcdle.ddev.site/ | grep -E "(Bientôt|En construction|header|footer)" | head -5
```

Expected: strings appear in the HTML output, confirming the shell plus the placeholder content render together.

- [ ] **Step 5: phpcs + phpstan sweep 0/0.**

- [ ] **Step 6: Commit**

```bash
git add web/modules/custom/lcdle_theme_helpers/lcdle_theme_helpers.routing.yml web/modules/custom/lcdle_theme_helpers/src/Controller/HomepagePlaceholderController.php
git -c commit.gpgsign=false commit -m "Serve a placeholder homepage at / while 2B2 is in-flight

Until the real Views-backed homepage ships in Phase 2B2, the front
page renders a lightweight message that exists only to prove the
shell works end-to-end: header branding, nav, dark-mode toggle +
footer are all there. The controller is OOP (ControllerBase subclass)
and trivially removable in 2B2 — we just replace the route target."
```

---

## Task 14 — Disable auto-placed system blocks

When `lcdle` theme was installed in Phase 2A, Drupal auto-placed a handful of blocks (branding, breadcrumbs, main content, etc.) under the default regions (`header`, `content`, etc.). Our shell renders `{{ page.content }}` only, so blocks in other regions are orphaned but still get generated into config/sync. Clean these up.

- [ ] **Step 1: Inspect placed blocks**

```bash
ls config/sync/ | grep '^block\.block\.lcdle_' | sort
```

Expected: ~7 files named `block.block.lcdle_branding.yml`, `block.block.lcdle_breadcrumbs.yml`, `block.block.lcdle_content.yml`, `block.block.lcdle_local_actions.yml`, `block.block.lcdle_local_tasks.yml`, `block.block.lcdle_messages.yml`, `block.block.lcdle_page_title.yml`.

- [ ] **Step 2: Decide which to keep**

Keep:
- `lcdle_content` — required (it IS `{{ page.content }}`).
- `lcdle_messages` — useful for Drupal's status messages (form errors, etc.) even in shell-only rendering.
- `lcdle_page_title` — needed so 404/403/system pages and future content pages show their title.
- `lcdle_local_tasks` + `lcdle_local_actions` — admin-facing, no harm keeping them.

Disable (status: false) or delete outright (since we intentionally don't have regions for them):
- `lcdle_branding` — the `header` SDC includes the site name directly; the legacy block is redundant.
- `lcdle_breadcrumbs` — no breadcrumb region in the shell.

- [ ] **Step 3: Delete the redundant block configs from the database and sync**

```bash
ddev drush config:delete block.block.lcdle_branding
ddev drush config:delete block.block.lcdle_breadcrumbs
```

- [ ] **Step 4: Export config**

```bash
ddev drush config:export -y
```

Expected: `block.block.lcdle_branding.yml` and `block.block.lcdle_breadcrumbs.yml` removed from `config/sync/`. Check with:

```bash
ls config/sync/ | grep '^block\.block\.lcdle_'
```

- [ ] **Step 5: Clear cache, re-check placeholder homepage still renders**

```bash
ddev drush cr
curl -sk https://lcdle.ddev.site/ | grep -E "(Bientôt|header|footer)" | head -5
```

- [ ] **Step 6: Commit**

```bash
git add config/sync/
git -c commit.gpgsign=false commit -m "Drop redundant lcdle_branding + lcdle_breadcrumbs blocks

The header SDC renders the site name directly, and the shell has no
breadcrumb region at MVP, so these two auto-placed blocks were dead
config. Keeping lcdle_content, lcdle_messages, lcdle_page_title,
lcdle_local_tasks, lcdle_local_actions — those still play a role
inside the shell or on admin pages."
```

---

## Task 15 — Fitness function sweep + tag `phase-2b1-complete`

- [ ] **Step 1: Run every fitness function from SPEC-003 §13**

```bash
# FF-1: SDC discovery — 8 components expected.
ddev drush ev "print_r(array_keys(\Drupal::service('plugin.manager.sdc')->getDefinitions()));" | grep 'lcdle:' | wc -l
# Expected: 8

# FF-2: kernel tests all pass.
ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_theme_helpers/tests/src/Kernel/"
# Expected: 8 kernel test classes pass. Plus the pre-existing Unit + Functional tests of Phase 2A still green.

# FF-3: dark mode toggle renders on the placeholder homepage.
curl -sk https://lcdle.ddev.site/ | grep -E '<button[^>]+dark-mode-toggle' | head -1
# Expected: one match.

# FF-4: anti-FOUC script is in <head>.
curl -sk https://lcdle.ddev.site/ | head -60 | grep 'localStorage.getItem'
# Expected: match inside the <head> section (before any stylesheet).

# FF-5: system pages produce the right strings.
curl -sk https://lcdle.ddev.site/this-path-does-not-exist | grep -E '(Page introuvable|Erreur 404)'
# Expected: both strings present.

# FF-6: 2A TokensLoadedTest still passes.
ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_theme_helpers/tests/src/Functional/TokensLoadedTest.php"
# Expected: OK.

# FF-7: phpcs + phpstan clean, no warnings.
ddev exec "vendor/bin/phpcs --standard=phpcs.xml.dist"
ddev exec "vendor/bin/phpstan analyse --memory-limit=1G"
# Expected: no failures.
```

Fix any red check in a dedicated commit before tagging.

- [ ] **Step 2: Verify the full test suite still runs**

```bash
ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_theme_helpers/tests/"
```

Expected: all kernel + unit + functional tests pass together.

- [ ] **Step 3: Tag `phase-2b1-complete`**

```bash
git tag -a phase-2b1-complete -m "Phase 2B1 — Atoms + Shell complete

8 SDC components:
- author-chip (3 size variants)
- meta-bar (slot-only list with CSS dot separators)
- pullquote (quote + optional cite)
- dark-mode-toggle (button + vanilla JS + anti-FOUC cooperation)
- article-card (cover + tag + serif title + meta)
- wide-card (no-image sibling of article-card)
- header (type-set logo + nav + embedded toggle)
- footer (site name + links)

3 CSS utilities (.lcdle-tag, .lcdle-lettrine, .lcdle-section-separator)
+ .lcdle-system-page for 404/403.

Layout templates:
- html.html.twig with inline anti-FOUC script
- page.html.twig shell with 900px container
- system/page--system--404.html.twig
- system/page--system--403.html.twig
- system/maintenance-page.html.twig (self-contained)

Placeholder homepage at / (HomepagePlaceholderController in
lcdle_theme_helpers) so anonymous visitors can verify the shell.
Redundant auto-placed blocks (branding, breadcrumbs) removed.

Tests: 8 kernel component tests + the pre-existing 2A unit +
functional tests, all green. phpcs + phpstan 0 errors 0 warnings.

Real content pages (homepage aggregate, article, profile, theme,
chronique, basic 'page' content type) ship in Phase 2B2."
```

- [ ] **Step 4: Do NOT push. Ask the user.**

Leave the tag + commits local. The user will push after confirmation.

---

## Self-Review

**Spec coverage:**

| SPEC-003 requirement | Task(s) |
|---|---|
| §2 scope: atoms + shell only | All |
| §3.1 pragmatic SDC granularity rule | 2 (utilities) + 3–10 (SDCs) |
| §3.2 wide-card as separate SDC | 8 |
| §3.3 placeholder homepage | 13 |
| §3.4 minimalist testing (A) | 3–10 (one kernel test per SDC) |
| §3.5 dark mode 2-state + localStorage + anti-FOUC | 1 (html.html.twig) + 6 (toggle SDC) |
| §3.6 system pages as Twig overrides | 11 + 12 |
| §4.1 header SDC | 9 |
| §4.2 footer SDC | 10 |
| §4.3 article-card SDC | 7 |
| §4.4 wide-card SDC | 8 |
| §4.5 author-chip SDC | 3 |
| §4.6 pullquote SDC | 5 |
| §4.7 meta-bar SDC | 4 |
| §4.8 dark-mode-toggle SDC | 6 |
| §5 utilities (tag, lettrine, section-separator) | 2 |
| §6.1 page.html.twig shell | 1 |
| §6.2 html.html.twig anti-FOUC | 1 |
| §7.1 page--system--404 | 11 |
| §7.2 page--system--403 | 11 |
| §7.3 maintenance-page | 12 |
| §8 placeholder homepage controller | 13 |
| §9 testing strategy (examples + kernel tests) | 3–10 |
| §10 a11y (aria-labels, focus-visible, contrast) | 1, 6, 9, 11 |
| §13 fitness functions | 15 |

**Placeholder scan:** none. No TBD/TODO left anywhere.

**Type consistency:** SDC machine names (`lcdle:author-chip` etc.) and BEM class names (`.author-chip__name`, `.header__site-name`, etc.) are used identically across each task's kernel test, Twig template, CSS, and consumer. Controller class `HomepagePlaceholderController::build()` is the only method referenced from both the route and the spec.
