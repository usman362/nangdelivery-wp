# Deploying Nang Delivery Brisbane to a live cPanel server

This site is a **custom WordPress theme** (`nangdelivery`) driven by **WooCommerce**
(products/cart) and **ACF** (editable page fields). The design and all media live
in the theme; the products, 72 blog posts, 62 delivery-area pages and site pages
live in the **database**. So a deploy always moves **3 things**:

1. **Code** — the theme (this git repo) + WordPress core + plugins
2. **Database** — `deploy/db/nangdelivery_wp.sql` (5.6 MB snapshot, table prefix `ndb_`)
3. **Media** — `wp-content/uploads/` (product images in the media library)

…then we **rewrite the dev URL** (`http://localhost:8080`) to the live domain.

> **Live domain assumed:** `https://nangdeliverybrisbane.au`
> If the real domain/www differs, swap it everywhere it appears below.

> **Plugin versions to install on the live site (match dev exactly):**
> WooCommerce **10.8.1**, Advanced Custom Fields **6.8.2**.

---

## Step 1 — Push the code to GitHub (one-time)

`gh` isn't installed locally, so create the repo in the browser:

1. Go to <https://github.com/new> → **private** repo, name e.g. `nangdelivery-wp` →
   **don't** add a README/.gitignore (we already have them).
2. In a terminal at `~/Sites/nangdelivery-wp`:

   ```bash
   git remote add origin https://github.com/<your-username>/nangdelivery-wp.git
   git branch -M main
   git push -u origin main
   ```

   (If push asks for a password, use a **Personal Access Token**, not your GitHub
   password: GitHub → Settings → Developer settings → Tokens. Or set up an SSH key
   and use the `git@github.com:…` URL.)

---

## Step 2 — Create the database in cPanel

cPanel → **MySQL® Databases**:

1. **Create New Database** → e.g. `nangdb` (cPanel prefixes it → `cpaneluser_nangdb`).
2. **Add New User** → e.g. `nang` + a strong password (save it).
3. **Add User To Database** → grant **ALL PRIVILEGES**.
4. Write down: **DB name**, **DB user**, **DB password**. Host stays `localhost`.

> ⚠️ I can't enter these credentials for you — create them yourself and keep them private.

---

## Step 3 — Put WordPress + the theme into `public_html`

You need WordPress **core**, the **theme**, and **uploads** on the server. Two ways:

### Option A — Manual upload (simplest for a first deploy on a blank host)
1. Download WordPress from <https://wordpress.org/download/> (zip) → upload to
   `public_html` via cPanel **File Manager** → **Extract** (move files out of any
   `wordpress/` subfolder so `wp-load.php` sits directly in `public_html`).
2. Upload the theme: zip your local `wp-content/themes/nangdelivery` folder, upload
   into `public_html/wp-content/themes/`, extract.
3. Upload media: zip your local `wp-content/uploads`, upload into
   `public_html/wp-content/`, extract.

### Option B — cPanel Git™ Version Control (keeps code in sync via git)
1. cPanel → **Git Version Control** → **Create** → paste the GitHub repo URL
   (use an SSH deploy key or a token-embedded HTTPS URL for a private repo).
2. Clone it to e.g. `~/repositories/nangdelivery-wp`.
3. Copy `wp-content/themes/nangdelivery` and `wp-content/uploads` from the clone
   into `public_html/wp-content/…` (the repo has no WP core, so still download core
   per Option A step 1). Later code updates = `git pull` + re-copy, or a `.cpanel.yml`
   deploy task.

---

## Step 4 — Install the plugins

After core is in place, finish the famous 5-minute install in the browser
(`https://nangdeliverybrisbane.au/wp-admin/`), **or** just place plugin folders under
`wp-content/plugins/`. Install/activate:

- **WooCommerce 10.8.1**
- **Advanced Custom Fields 6.8.2**

(Their data comes from the database import in Step 6 — installing the plugin only
provides the code.)

---

## Step 5 — Create `wp-config.php`

Copy `deploy/templates/wp-config.live.php` to `public_html/wp-config.php` and fill in:

- `DB_NAME / DB_USER / DB_PASSWORD` from Step 2 (`DB_HOST` = `localhost`)
- Fresh salts from <https://api.wordpress.org/secret-key/1.1/salt/>
- Leave `$table_prefix = 'ndb_';` **unchanged** (the dump uses `ndb_`)

---

## Step 6 — Import the database

**phpMyAdmin (no SSH):** cPanel → phpMyAdmin → select your DB → **Import** →
upload `deploy/db/nangdelivery_wp.sql` → Go.
*(If the file is over the upload limit, gzip it first or use the WP-CLI path.)*

**WP-CLI (if SSH/Terminal is available):**
```bash
cd ~/public_html
wp db import /path/to/deploy/db/nangdelivery_wp.sql
```

---

## Step 7 — Rewrite the dev URL → live domain (IMPORTANT)

The dump still points at `http://localhost:8080` (only 9 spots, but some are inside
serialized data, so **never** do a plain find-replace on the .sql — it corrupts
serialized lengths). Use a serialization-safe tool:

**WP-CLI (preferred):**
```bash
wp search-replace 'http://localhost:8080' 'https://nangdeliverybrisbane.au' --all-tables --precise --skip-columns=guid
wp cache flush
wp rewrite flush --hard
```

**No SSH?** Install the **Better Search Replace** plugin → Search:
`http://localhost:8080` → Replace: `https://nangdeliverybrisbane.au` → select all
tables → untick "dry run" → run. Then Settings → Permalinks → **Save** (flushes rules).

> After this, the theme's chrome assets (CSS/fonts/logo) auto-resolve to the live
> domain because they are derived from `siteurl` at render time.

---

## Step 8 — Final settings & SSL

- cPanel → **SSL/TLS Status** → run **AutoSSL** (free Let's Encrypt) so HTTPS works.
- WP Admin → **Settings → Permalinks** → confirm "Post name" and **Save** once
  (this regenerates the rewrite rules, incl. the `/product-page/<slug>/` product URLs).
- WP Admin → **WooCommerce → Settings** → confirm the store address/currency (AUD).

---

## Step 9 — Smoke test the live site

- [ ] Home page renders pixel-identical to dev
- [ ] **Shop** → click a product → product page loads
- [ ] On a product page, click a **Related Product** → it loads (the bare-slug →
      `/product-page/…` redirect works — see `inc/woo.php` `ndb_redirect_bare_product_slug`)
- [ ] Add to Cart → cart count updates → Cart/Checkout pages work
- [ ] A blog post + the blog archive load with correct images and dates
- [ ] **Delivery Areas** page + the "Filter by Suburb" dropdown navigate correctly
- [ ] Contact / enquiry **forms** send (configure SMTP for reliable email — see the
      in-admin "Site Guide" page)
- [ ] `wp-admin` login works (user `admin`); change the admin password on live

---

## Ongoing updates

- **Code changes** (theme/templates): commit + push to GitHub, then on the server
  `git pull` (Option B) or re-upload the theme folder (Option A).
- **Content changes** (products, posts, text, images): the client edits them in
  **wp-admin** directly — these live in the database, not in git.
- Keep WordPress core, WooCommerce and ACF updated from **wp-admin → Updates**.
