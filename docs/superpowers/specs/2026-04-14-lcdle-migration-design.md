---
id: SPEC-001
name: lcdle-migration-design
title: Migration La Culture de l'Écran — WordPress vers Drupal 11
type: design-spec
status: draft
last-updated: 2026-04-14
authors: [jerome]
---

# Spec de design — Migration `laculturedelecran.com` (WordPress → Drupal 11)

## 1. Contexte et objectif

### 1.1 Situation actuelle

`laculturedelecran.com` est un site WordPress multi-contributeurs en activité réduite depuis plusieurs années. Structure actuelle :

- **Contributeurs** : une dizaine d'auteurs historiques, chacun associé à une ou plusieurs "chroniques" (rubrique récurrente).
- **URLs WordPress** :
  - Pages auteur : `/author/{username}/`
  - Chroniques : `/{theme}/{chronique}/` (ex : `/musique/song-of-the-week/`)
  - Articles : `/{slug}/` à la racine, slug unique

### 1.2 Objectif

Migrer vers une nouvelle plateforme **Drupal 11** qui repositionne le produit :

- Chaque contributeur dispose d'un **espace éditorial personnel** (à la Medium/Substack).
- Le concept de "chronique" devient un **outil optionnel** à disposition du contributeur pour organiser son propre espace, plutôt qu'une structure imposée.
- L'**axe principal est l'auteur**. Les thèmes (musique, cinéma…) deviennent transversaux.
- Le produit doit être **scalable** : démarrage à ~10 contributeurs, architecture prête pour beaucoup plus.
- Le **contenu existant est intégralement migré** (articles, auteurs, médias) avec préservation SEO (redirections 301).
- Une **newsletter** globale (puis per-author) accompagne la plateforme.
- Ouverture future à **IndieWeb** (Webmentions) et **Fediverse** (ActivityPub).

### 1.3 Non-objectifs (MVP)

- Pas de commentaires natifs — réactions légères uniquement ; commentaires éventuels via IndieWeb en phase 4.
- Pas de memberships / contenus payants.
- Pas de personnalisation visuelle par contributeur au MVP (thème global unifié).
- Pas de sous-domaines par contributeur au MVP (`domaine.fr/alice`, pas `alice.domaine.fr` ; option repoussée).
- Pas de multi-langue.
- Pas de migration des commentaires WP (export JSON d'archive pour réimport éventuel en phase 4).

## 2. Choix de plateforme

**Drupal 11 Core** (et non Drupal CMS / Starshot).

Justification :

- Modèle de contenu sur mesure (espace auteur + tags transversaux + chronique optionnelle + workflow hybride) que Drupal modélise sans contorsion.
- IndieWeb + ActivityPub disponibles via modules contrib matures.
- Alignement avec les compétences de l'équipe (dev Drupal).
- Scalabilité éprouvée.
- Drupal CMS est un preset opinionated pour cas standards ; Domain et certains modules multi-tenant sortent de son périmètre officiel.

Alternatives évaluées et écartées :

- **Ghost** : excellent UX d'écriture et ActivityPub natif, mais son modèle "multi-auteurs" n'est pas un modèle "multi-espaces". Extensibilité limitée pour les besoins à moyen terme (architecture éditoriale, newsletters per-author cohérentes avec le profil Drupal, onboarding évolutif).
- **Hugo / 11ty + Decap CMS** : écartés. Workflow éditorial évolué, validation a priori, IndieWeb interactif (Webmentions entrantes) nécessitent un backend dynamique.

## 3. Stack technique

| Couche | Choix |
|---|---|
| CMS | Drupal 11 (Core) |
| Langage | PHP 8.4 |
| Base de données | PostgreSQL 16 |
| Cache | Redis (module `redis` pour cache bins + sessions) |
| Serveur web | Nginx + PHP-FPM |
| CDN | Cloudflare (tier gratuit au MVP) |
| Recherche | Search API + backend DB (Solr/Elastic si le volume le justifie plus tard) |
| Medias (storage) | Filesystem local au MVP ; migration S3 via `s3fs` si volume croît |
| Dev local | DDEV |
| Hébergement prod | VPS OVH + Docker (docker-compose) |
| CI/CD | GitHub Actions |
| Gestion dépendances | Composer (`drupal/recommended-project` base) |
| Config | Config Management core (YAML versionné en git) |

### 3.1 Conventions de code custom

- **OOP moderne Drupal** : attributes PHP (`#[Hook]`, `#[FieldType]`, `#[Block]`…), dépendances via services + DI, pas de hook procédural sauf nécessité.
- **PHP 8.4 features** exploitées : property hooks, asymmetric visibility, readonly properties, typed properties, enums.
- **Machine names et identifiants techniques en anglais**. UI labels en français.
- **PHPStan** niveau 6+ en CI, **PHPCS** avec standard Drupal.

## 4. Modèle de contenu

### 4.1 Entités principales

#### User + ContributorProfile

`User` est l'entité d'authentification Drupal native. L'identité publique d'un contributeur est portée par une entité `ContributorProfile` dédiée, implémentée via le module contrib **Profile** (type de profil `contributor`, un profil par user). Choix retenu sur entité custom pour bénéficier des vues/formulaires natifs et de la cohérence avec l'écosystème Drupal.

Champs `ContributorProfile` :

| Machine name | Type | Rôle |
|---|---|---|
| `display_name` | string | Nom affiché publiquement |
| `slug` | string, unique, blacklist | Identifiant d'URL (`/{slug}`) |
| `bio` | text long | Bio publique |
| `avatar` | Media (image) | Avatar |
| `banner` | Media (image) | Bannière de l'espace |
| `social_links` | list (URL typé) | Mastodon, site perso, autres |
| `accent_color` | string (hex) | Préparé pour la perso légère phase 5+ ; ignoré au MVP |

**Slugs réservés (blacklist)** : `admin`, `user`, `users`, `node`, `taxonomy`, `api`, `jsonapi`, `theme`, `chronique`, `newsletter`, `contribute`, `about`, `rss`, `feed`, `sitemap`, `robots`, `login`, `search`, `media`, `files`, `system`, `contact` ; plus tout slug matchant `/^[a-z0-9]{1,2}$/`.

#### Node: Article

| Machine name | Type | Note |
|---|---|---|
| `title` | string | |
| `body` | text_long (CKEditor 5) | Support Media embed |
| `field_cover_image` | entity reference → Media (image) | |
| `field_excerpt` | text (formatted, plain) | Pour cards / newsletter / OpenGraph |
| `field_themes` | entity reference multiple → `Taxonomy: themes` | |
| `field_chronique` | entity reference single → `Taxonomy: chroniques` | Optionnel |
| `uid` | entity reference → User | Auteur, natif Drupal |
| `moderation_state` | Content Moderation (voir §5) | |

Pathauto : `article` → `[node:author:field_contributor_profile:slug]/[node:title]`.

#### Taxonomy: themes

Vocabulaire **Thèmes** (musique, cinéma, série, livres…). Hiérarchie plate ou à 1 niveau, gérée par les editors. Migré depuis les catégories WP principales.

#### Taxonomy: chroniques

Vocabulaire **Chroniques**. Hiérarchie plate. Termes créables par les contributeurs (validation éventuelle), attachés à un article via `field_chronique`. Une chronique est **associée à un auteur via ses articles** (pas un ownership explicite — un terme est transverse, mais en pratique les articles d'une chronique sont écrits par un contributeur).

#### Taxonomy: tags_legacy

Vocabulaire de quarantaine pour les tags WP importés. Sert au **triage post-migration** (fusion manuelle dans `themes`, conservation, ou suppression).

#### NewsletterSubscriber

Entité custom (distincte de User pour permettre les abonnements anonymes RGPD-propres).

| Champ | Type | Note |
|---|---|---|
| `email` | email | Unique |
| `status` | enum (pending / active / unsubscribed / bounced) | |
| `token` | string, unique | Confirmation double opt-in + désabonnement |
| `subscribed_at` | datetime | |
| `source` | string | Origine (form `/newsletter`, footer, page auteur…) |
| `locale` | string | fr par défaut |
| `author_scope` | entity reference → User, nullable | null = globale ; uid = newsletter d'un auteur (phase 4) |

### 4.2 Schéma relationnel

```
                 ┌─────────────────────────┐
                 │   ContributorProfile    │
                 │ slug, bio, avatar, …    │
                 └──────────┬──────────────┘
                            │ 1..1
                            ▼
                      ┌───────────┐
                      │   User    │───── Role: reader / contributor_new /
                      └─────┬─────┘        contributor_trusted / editor / admin
                            │ 1..*
                            ▼
                ┌─────────────────────────┐
                │       Article (node)    │
                │ moderation_state, body  │
                └────┬───────────────┬────┘
                     │ *  field_themes │ 0..1 field_chronique
                     ▼                 ▼
              ┌─────────────┐   ┌──────────────┐
              │ themes (tax)│   │ chroniques   │
              └─────────────┘   └──────────────┘

            ┌──────────────────────────┐
            │  NewsletterSubscriber    │
            │  (entité indépendante)   │
            └──────────────────────────┘
```

## 5. Workflow éditorial et rôles

### 5.1 Rôles

| Rôle | Permissions clés |
|---|---|
| `anonymous` | Lire articles publiés, s'abonner newsletter |
| `reader` | Anonymous + gérer ses abonnements newsletter |
| `contributor_new` | Créer/éditer ses propres articles → état `needs_review` uniquement |
| `contributor_trusted` | Créer/éditer/publier directement ses propres articles |
| `editor` | Modérer, promouvoir `new → trusted` (manuel), gérer vocabulaires `themes` / `chroniques` |
| `administrator` | Tout |

### 5.2 Workflow Content Moderation

États : `draft`, `needs_review`, `published`, `archived`.

Transitions autorisées par rôle :

| Transition | Rôles autorisés |
|---|---|
| Création → `draft` | contributor_new, contributor_trusted, editor |
| `draft → needs_review` | contributor_new (seule voie publique pour lui) |
| `draft → published` | contributor_trusted, editor |
| `needs_review → published` | editor |
| `needs_review → draft` | editor (rejet avec commentaire) |
| `published → archived` | editor, administrator |
| `published → draft` | editor (dépublication) |

### 5.3 Promotion `contributor_new → contributor_trusted`

Manuelle (editor décide via l'admin User) au MVP. **Non-objectif** : automatisation après N articles validés (à ouvrir en phase 5+ si le besoin émerge).

### 5.4 Onboarding (évolutif)

**Phase 2 (lancement) — invitation uniquement** :
- Page publique `/contribute` affiche un message "Pour rejoindre l'équipe, contactez-nous" + lien de contact.
- Les editors créent manuellement les comptes avec rôle `contributor_new`.
- **Contributeurs WP migrés** : importés directement en `contributor_trusted` (historique de confiance préservé).

**Phase 3 — candidature ouverte** :
- Formulaire Webform sur `/contribute` → notification editor → décision manuelle.

**Phase 5+ — inscription libre** :
- Inscription Drupal standard + premier article forcé en `needs_review`.
- Anti-spam : Captcha + Honeypot + Antibot + rate limiting.

### 5.5 Notifications

- Editor notifié à chaque `draft → needs_review` (module `message_notify` ou ECA).
- Contributeur notifié à chaque transition d'état sur son article.

## 6. URLs et migration WordPress

### 6.1 Schéma d'URL cible

| Ressource | URL |
|---|---|
| Accueil | `/` |
| Espace contributeur | `/{slug}` |
| Article | `/{slug}/{article-slug}` |
| Chronique d'un auteur | `/{slug}/chronique/{chronique-slug}` |
| Thème (transversal) | `/theme/{theme-slug}` |
| Flux RSS global | `/feed` |
| Flux RSS par auteur | `/{slug}/feed` |
| Flux RSS par thème | `/theme/{theme-slug}/feed` |
| Abonnement newsletter | `/newsletter` |
| Candidature contributeur (phase 3) | `/contribute` |
| Mentions légales / vie privée | `/legal/privacy` |

### 6.2 Migration — source et méthode

**Source** : backup SQL WordPress + dossier `wp-content` disponibles localement. La migration est exécutée dans un environnement dédié (aucun accès au WP en production).

**Outillage** : Drupal core Migrate API + `migrate_plus` + `migrate_tools`, source MySQL directe (`migrate_source_sql`) + filesystem pour les médias.

### 6.3 Mappings

| WordPress | Drupal |
|---|---|
| `wp_users` (rôle `author`+) | `User` + rôle `contributor_trusted` + `ContributorProfile` |
| User meta : bio, avatar, URL | Champs `ContributorProfile` |
| `wp_posts` (type=post, status=publish) | `Node: Article`, `moderation_state: published` |
| `wp_postmeta` featured image | `field_cover_image` (Media) |
| `wp_terms` (catégories WP principales) | `Taxonomy: themes` |
| `wp_terms` (sous-catégories qualifiées chroniques) | `Taxonomy: chroniques` (décision manuelle pré-import par terme) |
| Tags WP | `Taxonomy: tags_legacy` (vocabulaire de quarantaine) |
| `wp_uploads/*` | Entités Media (image), rsync `/wp-content/uploads/` → `/sites/default/files/migrated/`, URLs réécrites dans bodies |
| Commentaires | Non migrés ; export JSON d'archive stocké hors-Drupal |
| Shortcodes | Inventaire pré-migration, plugin migrate process custom par type ; fallback : conversion manuelle |

### 6.4 Réécriture des liens internes

Les bodies contiennent des liens internes vers `https://laculturedelecran.com/...`. Post-migration, un plugin migrate process custom (ou script one-shot) remplace ces URLs par les nouvelles (même domaine après bascule DNS, mais nouveaux chemins).

### 6.5 Redirects 301 (critique — objectif SEO prioritaire)

Module **Redirect** + migration dédiée qui génère une redirection 301 pour chaque URL indexée recensée.

Exemples :

| Ancien | Nouveau |
|---|---|
| `/author/rolandderudet/` | `/rolandderudet` (ou slug final choisi) |
| `/musique/song-of-the-week/` | `/rolandderudet/chronique/song-of-the-week` |
| `/musique/song-of-the-week/{article-slug}/` | `/rolandderudet/{article-slug}` |
| `/{article-slug}/` | `/{author-slug}/{article-slug}` |
| `/musique/` | `/theme/musique` |

**Audit exhaustif pré-bascule** :
- Extraction de la liste complète des URLs indexées (sitemap.xml + crawl).
- Génération automatique de la table de redirects.
- Test automatisé : pour chaque URL WP, vérifier que le redirect 301 répond correctement vers une URL Drupal existante (pas de 404 derrière).

### 6.6 Étapes de migration

1. **Audit contenu WP** : script qui compte users actifs, posts publiés, catégories, sous-catégories candidates chronique, tags, shortcodes présents.
2. **Curation manuelle** dans le WP source (dépublier ce qu'on ne veut pas migrer).
3. **Dump SQL + copie wp-content** vers environnement de migration.
4. **Pré-validation** : mapping auteurs → slugs Drupal (liste proposée, édition manuelle), mapping sous-catégories → chroniques.
5. **Dry-run local** : `drush migrate:import --feedback=50`, rollback facile.
6. **Vérifications automatisées** : volumes (articles, auteurs, médias), 0 broken reference, 100% URLs anciennes avec redirect, 100% médias présents.
7. **Vérification manuelle** : échantillon de 20 articles (rendu, images, liens internes).
8. **Bascule DNS** (uniquement après Phase 2 complète — voir §10).

## 7. Theming

### 7.1 Principes

- **Theme custom unique**, parent `stable9` ou vierge. Pas de base theme contrib (Bootstrap/Olivero) comme parent.
- **Single Directory Components (SDC)** — un composant = un dossier (template Twig + YAML + CSS + JS). Modulaire et testable.
- **Design tokens** alignés sur la spec W3C Design Tokens 2025 (couleurs, typographie, espacements, breakpoints). Implémentés en CSS custom properties.
- **Vanilla CSS moderne** (cascade layers, `@property`, container queries, nesting natif). Pas de Sass, pas de Tailwind au MVP.
- **BEM** comme convention de nommage CSS, cohérente avec la structure SDC (un composant = un block BEM).
- **Microformats h-entry / h-card** intégrés au rendu des articles et profils (prépare IndieWeb/Fediverse).
- **Accessibilité WCAG 2.1 AA** non-négociable.
- **Mobile-first**, responsive.
- **Pas de framework JS lourd** : JS vanilla ou Alpine.js via Drupal Behaviors pour interactions légères.

### 7.2 Responsive images

- Module core `responsive_image` + `image_style`.
- Image styles générant **WebP** via `convert` effect (core Drupal ≥ 10.1).
- Responsive image style mapping breakpoints → `srcset` + élément `<picture>` avec fallback JPEG/PNG.

### 7.3 Composants MVP

- Header (navigation principale, CTA newsletter, menu mobile)
- Footer (liens légaux, RSS, candidature contributeur)
- Card article (titre, extrait, auteur, thèmes, date, cover image)
- Page article (rendu plein texte + h-entry)
- Page profil contributeur (`/{slug}` : bio, avatar, bannière, liens, liste d'articles, liste de chroniques)
- Liste d'articles (page thème, page chronique)
- Page thème (`/theme/{slug}`)
- Pages système : 404, 403, maintenance
- Page newsletter (formulaire opt-in)

### 7.4 Ce qu'on n'utilise PAS au MVP

- React/Vue/Svelte côté front
- Storybook dédié (prévisualisation des SDC via module core `components` ou SDC viewer)
- Sass
- Framework CSS contrib

## 8. Newsletter

### 8.1 MVP (Phase 2 — collecte seulement)

- Formulaire public `/newsletter` (email + consentement RGPD explicite + **double opt-in**).
- Stockage : entité `NewsletterSubscriber`.
- **Pas d'envoi au lancement** — on collecte pour bâtir l'audience.

### 8.2 Phase 3 — envoi fonctionnel

Décision d'outillage prise au moment d'implémenter. Préférence exprimée : **solution Drupal-native** (newsletter comme contenu éditorial archivable/indexable dans Drupal).

Pistes :

- **Simplenews + SMTP transactionnel** (Postmark / Amazon SES / Brevo) — mature, tout dans Drupal.
- **Listmonk self-hosted + sync API** — UI moderne, séparation claire, ajoute un service à opérer.
- **SaaS type Buttondown / Brevo avec push d'abonnés via API** — écarté par défaut (contenu newsletter hors Drupal).

Décision à acter par ADR au moment de l'implémentation.

### 8.3 Phase 4 — newsletters per-author

Pré-câblage déjà présent : `NewsletterSubscriber.author_scope`. Formulaire sur `/{slug}` permet de s'abonner à un contributeur précis. Envoi déclenché à la publication (ou digest hebdo).

### 8.4 RGPD / légal

- **Double opt-in** obligatoire.
- **Token de désabonnement** à usage unique (non devinable) dans chaque email.
- **Page `/legal/privacy`** listant traitements, destinataires (service transactionnel), durée conservation.
- **Droit à l'effacement** pour les `NewsletterSubscriber` anonymes via token (au-delà du module User core qui gère les comptes).
- **SPF / DKIM / DMARC** configurés dès jour 1 sur le domaine expéditeur.

## 9. IndieWeb & Fediverse (phase 4)

### 9.1 Prérequis dès le MVP (gratuit à faire, coûteux à rétrofitter)

- **Microformats h-entry / h-card** dans le theme (cf. §7).
- **URLs canoniques stables** (redirects propres).
- **Feeds RSS** par auteur, par thème, global.
- **Page profil structurée** (avatar, bio, social links).

### 9.2 Phase 4 — Webmentions

- Module `indieweb` (contrib Drupal) : endpoint `/webmention`, header `<link rel>` sur les articles, affichage des mentions modérées, envoi de Webmentions depuis les liens sortants.
- **Bridgy** : pont Twitter/Mastodon → Webmentions (zéro code).

### 9.3 Phase 4 — ActivityPub

- Module `activitypub` (contrib) **ou** service Bridgy Fed — choix à arbitrer selon maturité en 2026.
- Chaque contributeur devient `@{slug}@domaine.fr` followable depuis Mastodon.
- Publications poussées dans le flux ActivityPub de l'auteur.

### 9.4 Réactions MVP (section commentaires)

Pas de commentaires natifs. Réactions légères (like/share) côté front, sans backend (liens de partage vers réseaux sociaux, bouton copier-lien). Conversation renvoyée vers les réseaux externes.

## 10. Hébergement et déploiement

### 10.1 Production

- **VPS OVH** + **Docker** (docker-compose).
- Services : Nginx, PHP-FPM (PHP 8.4), PostgreSQL 16, Redis.
- Image PHP basée sur `php:8.4-fpm-alpine` custom (extensions : `pdo_pgsql`, `gd`, `intl`, `opcache`, `redis`, `zip`).
- Volumes persistants : `files`, `private`, `pgdata`, `redis-data`.

### 10.2 Environnements

| Env | Localisation | Rôle |
|---|---|---|
| `local` | Laptop + DDEV | Dev quotidien |
| `staging` | VPS ou DDEV distant | Validation pré-prod, migration test |
| `prod` | VPS OVH | Production |

Pas d'accès SSH direct pour modifs applicatives en prod — tout passe par git + CI/CD.

### 10.3 CI/CD (GitHub Actions)

Workflow standard à chaque push sur `main` et chaque PR :

- `phpcs` (standard Drupal)
- `phpstan` (level ≥ 6)
- Tests PHPUnit (unit + kernel + functional)
- Tests d'accessibilité automatisés (Pa11y ou axe-core) sur pages clés
- Build et push de l'image Docker applicative
- Déploiement prod sur `main` : pull image, `drush updb && drush cim && drush cr`, healthcheck, rollback automatique si healthcheck échoue

### 10.4 Config management

- `drush config:export` et `config:import` natifs, config YAML versionnée dans le repo.
- `config_split` pour différences environnements (ex : modules dev activés en local uniquement).

### 10.5 Secrets

- Jamais en git.
- Locally : `.env` DDEV.
- Prod : variables d'env injectées via docker-compose + secrets système (non lisibles depuis le repo).

### 10.6 Backups

- Dump PostgreSQL quotidien chiffré, rotation 30 jours, stockage hors-VPS (S3 / Backblaze B2).
- Snapshot `files/` hebdomadaire.
- Test de restauration trimestriel.

## 11. Tests

### 11.1 Niveau attendu

- **PHPUnit unit tests** pour services métier dans les modules custom.
- **Kernel tests** pour hooks, plugins, event subscribers.
- **Functional tests (BrowserTestBase)** sur les **parcours critiques** :
  - Création article par `contributor_new` → `needs_review`
  - Validation article par editor → `published`
  - Création article par `contributor_trusted` → publication directe
  - Changement de slug dans `ContributorProfile` (unicité, blacklist, cascade des URLs)
  - Inscription newsletter avec double opt-in
  - Désabonnement newsletter via token
- **Pa11y / axe-core** en CI sur pages clés (article, profil, liste, formulaire newsletter).

### 11.2 Negative space

Chaque parcours critique a ses tests négatifs :

- Un `contributor_new` ne peut pas publier directement (assert interdiction).
- Un slug dans la blacklist est refusé (assert rejet).
- Un abonnement sans double opt-in n'envoie jamais de newsletter (assert état `pending` filtré).
- Une URL WP non-listée dans les redirects ne provoque pas 500 (assert 404 propre).

## 12. Roadmap par phases

### Phase 0 — Fondations (préparatoire)

- Repo git, Composer, DDEV local.
- GitHub Actions : lint, PHPCS, PHPStan.
- Content model complet (types, fields, vocabs, rôles, workflow, pathauto).
- Entités `ContributorProfile` et `NewsletterSubscriber`.
- Config en git.
- Premiers ADR (stack, URLs, migration source, theming).

**Livrable** : Drupal structurellement prêt, zéro contenu, zéro theming.

### Phase 1 — Migration de données (staging)

- Modules Migrate configurés (MySQL + filesystem wp-content).
- Mapping users / posts / terms / tags_legacy / médias / liens internes / shortcodes.
- Génération de la table de redirects 301 (audit URLs + automatisation).
- Audits post-migration (automatisés + échantillon manuel).

**Livrable** : staging avec tout le contenu migré, non-public.

### Phase 2 — Theming MVP + lancement

- Theme custom SDC + design tokens + BEM + responsive images WebP.
- Composants MVP (cf. §7.3).
- Microformats, feeds RSS (global, auteur, thème).
- Page `/newsletter` (collecte seule).
- Onboarding phase 2 (invitation manuelle).
- **Bascule DNS** — conditions en §12.1.

**Livrable** : site public en ligne, WP extinguible.

### Phase 3 — Newsletter + onboarding ouvert

- Choix outillage newsletter (ADR).
- Templates + automatisation.
- Envoi première newsletter.
- Formulaire `/contribute` + workflow.

**Livrable** : newsletter opérationnelle, candidatures ouvertes.

### Phase 4 — IndieWeb & Fediverse

- Module IndieWeb (Webmentions).
- Bridgy.
- ActivityPub (module ou Bridgy Fed, arbitrage).
- Réimport éventuel des commentaires WP d'archive.
- Newsletters per-author.

**Livrable** : plateforme interconnectée au Fediverse.

### Phase 5+ — Évolutions non-engagées (parking lot)

- Inscription libre (onboarding ouvert à tous, premier article forcé en `needs_review`).
- Perso visuelle légère par contributeur.
- Sous-domaines custom (Domain Access).
- Memberships / contenus payants.
- Commentaires natifs.
- Recherche Solr/Elastic.
- Storage médias S3.
- Multi-langue.
- Promotion automatique `contributor_new → contributor_trusted` après N articles validés.

### 12.1 Conditions de bascule DNS (gate Phase 2)

- [ ] 100% des URLs indexées ont un redirect 301 fonctionnel (testé automatiquement).
- [ ] Sitemap.xml nouveau généré, soumis GSC.
- [ ] Balises canoniques propres sur toutes les pages.
- [ ] `robots.txt` vérifié (pas de `Disallow /` accidentel).
- [ ] Core Web Vitals OK sur échantillon pages (seuils "Good" LCP/CLS/INP).
- [ ] SPF/DKIM/DMARC configurés sur le domaine.
- [ ] Backup automatique opérationnel, test de restauration validé.
- [ ] Monitoring basique (uptime + erreurs) en place.

## 13. Risques et mitigations

| Risque | Niveau | Mitigation |
|---|---|---|
| Perte de trafic SEO après bascule | Élevé | Redirects 301 exhaustifs + audit avant/après + surveillance GSC 30 jours |
| Shortcodes WP non convertis → contenu cassé | Moyen | Inventaire pré-migration, parse dédié par type, échantillon de validation manuelle |
| UX d'écriture Drupal perçue inférieure à WP | Moyen | Gin admin theme + CKEditor 5 soigné + Paragraphs bien pensés + onboarding rédactionnel écrit |
| Délivrabilité newsletter initiale faible (nouveau domaine expéditeur) | Moyen | Warmup progressif, SPF/DKIM/DMARC jour 1, service transactionnel sérieux |
| Modules IndieWeb / ActivityPub moins maintenus en 2026 qu'espéré | Faible | Découpler du lancement, ré-évaluer en phase 4 |
| Conflits de slugs lors de la migration (homonymes, auteurs changeant de nom) | Faible | Pré-validation humaine de la liste des slugs générés avant import |

## 14. Décisions à documenter en ADR

- **ADR-001** : Drupal 11 Core vs Drupal CMS vs Ghost (choix plateforme).
- **ADR-002** : Pas de Domain Access / pas de Group au MVP (multi-tenant logique simple).
- **ADR-003** : Source migration = backup SQL + wp-content local.
- **ADR-004** : Stack theming (SDC + Vanilla CSS moderne + BEM + design tokens).
- **ADR-005** : PostgreSQL + PHP 8.4 + VPS OVH Docker.
- **ADR-006** : Workflow Content Moderation hybride (new → review, trusted → direct).
- **ADR-007** : Entité `NewsletterSubscriber` distincte de User.
- **ADR-008** : Choix outillage newsletter (différé, tranché en Phase 3).
- **ADR-009** : Module IndieWeb vs custom (différé, tranché en Phase 4).
- **ADR-010** : Module ActivityPub vs Bridgy Fed (différé, tranché en Phase 4).

## 15. Glossaire initial (projet)

| Terme | Définition |
|---|---|
| **Contributeur** | Utilisateur identifié qui publie des articles (rôles `contributor_new` ou `contributor_trusted`). |
| **Espace contributeur** | Page `/{slug}` regroupant le profil et les articles d'un contributeur. |
| **Article** | Unité de contenu éditorial, attachée à un auteur (User), des thèmes et éventuellement une chronique. |
| **Thème** | Taxonomy term transversal qui catégorise un article (musique, cinéma, série…). |
| **Chronique** | Taxonomy term optionnel désignant une série récurrente (ex : "song-of-the-week"). Créable par les contributeurs. |
| **Editor** | Rôle qui modère les articles et gère les vocabulaires. |
| **Tag legacy** | Taxonomy term issu des tags WordPress importés, en vocabulaire de quarantaine pour triage. |
| **Slug** | Identifiant d'URL unique d'un contributeur, personnalisable dans son profil. |
| **Trusted** | Statut d'un contributeur autorisé à publier directement sans passer par `needs_review`. |
| **Newsletter globale** | Newsletter envoyée à tous les abonnés (`author_scope` null). |
| **Newsletter per-author** | Newsletter envoyée aux abonnés d'un contributeur précis (phase 4). |
