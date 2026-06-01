# Nang Delivery Brisbane — WordPress

Custom WordPress build for **Nang Delivery Brisbane** (cream chargers / N₂O delivery,
Brisbane AU). The front end is a bespoke theme; the store is **WooCommerce** and the
editable page content uses **ACF**, so the client manages everything from `wp-admin`.

## What's in this repo

| Path | Purpose |
|------|---------|
| `wp-content/themes/nangdelivery/` | The custom theme — templates, the localised CSS/JS/fonts/images, and the per-page `page-parts/` fragments. |
| `wp-content/themes/nangdelivery/inc/` | PHP modules: `render.php`, `woo.php` (WooCommerce + the bare-slug product redirect), `forms.php`, `post-types.php`, `page-fields.php` (ACF), `enqueue.php`, `site-settings.php` (in-admin client guide). |
| `wp-content/uploads/` | Media library (product images). |
| `deploy/db/nangdelivery_wp.sql` | Point-in-time database snapshot (table prefix `ndb_`). |
| `deploy/templates/wp-config.live.php` | Production `wp-config.php` template. |
| `deploy/DEPLOY.md` | **Step-by-step cPanel deployment runbook.** |

## Not in this repo (by design)

WordPress **core**, the **WooCommerce/ACF** plugins, the real `wp-config.php`
(secrets), and the `_build/` scaffolding are git-ignored. Core + plugins are
installed on the server; see `deploy/DEPLOY.md`.

## Deploy

See **[`deploy/DEPLOY.md`](deploy/DEPLOY.md)** — covers GitHub push, cPanel database
creation, file upload, `wp-config`, DB import, the dev→live URL search-replace, SSL,
and a smoke-test checklist.

## Local dev notes

- Table prefix: `ndb_`  ·  WooCommerce **10.8.1**, ACF **6.8.2**
- Product URLs: `/product-page/<slug>/`. Bare-slug links (`/​<slug>/`, used by the
  scraped "Related Products" markup) 301-redirect to the canonical URL via
  `inc/woo.php`.
- Stored content uses **root-relative** asset URLs for domain portability.
