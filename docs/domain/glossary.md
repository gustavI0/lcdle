---
id: GLOSSARY
type: glossary
status: living
last-updated: 2026-04-14
---

# Glossaire — La Culture de l'Écran

Vocabulaire du domaine projet. Source unique pour les termes utilisés dans le code (machine names), les tests, la documentation et les conversations. Un terme ambigu est un bug de communication.

## Acteurs

| Terme | Machine name | Définition |
|---|---|---|
| **Contributeur** | `contributor` | Utilisateur identifié qui publie des articles. Rôles : `contributor_new`, `contributor_trusted`. |
| **Editor** | `editor` | Utilisateur qui modère les articles et gère les vocabulaires `themes` / `chroniques`. |
| **Lecteur** | `reader` | Utilisateur non-contributeur qui consulte le site et/ou s'abonne à la newsletter. |
| **Administrator** | `administrator` | Super-utilisateur technique. |

## Contenu

| Terme | Machine name | Définition |
|---|---|---|
| **Article** | `article` | Unité de contenu éditorial. Un contenu = un auteur (`uid`), 0..n thèmes, 0..1 chronique. |
| **Thème** | `themes` (vocab) | Taxonomy term transversal qui catégorise un article (musique, cinéma, série, livres…). Gérés par les editors. |
| **Chronique** | `chroniques` (vocab) | Taxonomy term optionnel désignant une série récurrente dans l'espace d'un contributeur (ex: "song-of-the-week"). Créable par les contributeurs. |
| **Tag legacy** | `tags_legacy` (vocab) | Taxonomy term issu des tags WordPress importés, en vocabulaire de quarantaine pour triage post-migration. |

## Espace contributeur

| Terme | Machine name | Définition |
|---|---|---|
| **Espace contributeur** | — | Page publique `/{slug}` qui regroupe profil et articles d'un contributeur. |
| **Profil contributeur** | `contributor_profile` (profile type) | Entité Profile attachée à un User, portant bio, avatar, bannière, liens sociaux. |
| **Slug** | `slug` (field) | Identifiant d'URL unique d'un contributeur. Personnalisable, soumis à blacklist. |

## Workflow éditorial

| Terme | Machine name | Définition |
|---|---|---|
| **Workflow** | `article_workflow` | Machine à états Content Moderation qui régit les transitions d'un article. |
| **Draft** | `draft` | Brouillon, non publié. |
| **Needs review** | `needs_review` | Soumis à la validation d'un editor (obligatoire pour `contributor_new`). |
| **Published** | `published` | Publié et visible publiquement. |
| **Archived** | `archived` | Retiré de la visibilité, conservé en base. |
| **Trusted** | — | Statut d'un contributeur autorisé à publier directement sans passer par `needs_review`. |

## Newsletter

| Terme | Machine name | Définition |
|---|---|---|
| **Abonné newsletter** | `newsletter_subscriber` (entity) | Entité custom stockant email + token + état d'un abonnement. Distincte de User pour permettre l'abonnement anonyme. |
| **Double opt-in** | — | Obligation RGPD : un abonnement n'est actif qu'après confirmation via lien email à token unique. |
| **Newsletter globale** | `author_scope: null` | Newsletter adressée à tous les abonnés. |
| **Newsletter per-author** | `author_scope: <uid>` | Newsletter adressée aux abonnés d'un contributeur précis (phase 4). |

## Migration

| Terme | Définition |
|---|---|
| **Redirect 301** | Redirection HTTP permanente servie par le module `redirect` pour préserver le SEO des URLs WordPress. |
| **Shortcode WP** | Marqueur `[nom attr=val]` présent dans les bodies WordPress, à convertir ou parser lors de la migration. |

## Références externes

| Terme | Définition |
|---|---|
| **IndieWeb** | Mouvement pour la propriété de son contenu : microformats, Webmentions, Micropub. Voir `indieweb.org`. |
| **Webmention** | Notification HTTP envoyée quand un site référence un autre. Standard W3C. |
| **ActivityPub** | Protocole fédéré (Fediverse / Mastodon). Standard W3C. |
| **h-entry / h-card** | Microformats pour articles (h-entry) et profils (h-card). Intégrés au theme dès le MVP. |
