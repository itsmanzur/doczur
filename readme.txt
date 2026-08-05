=== Doczur — Product Documentation & Knowledge Base ===
Contributors: itsdz
Tags: documentation, knowledge base, docs, help center, product docs
Requires at least: 6.5
Tested up to: 6.7
Stable tag: 0.1.0
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Product documentation, knowledge base, and help center for WordPress — fast, theme-independent, and easy to set up in 5 minutes.

== Description ==

**Doczur** is a product documentation and knowledge base plugin built for WordPress. It helps software companies, plugin/theme developers, SaaS founders, WooCommerce sellers, and agencies publish beautiful, fast-loading documentation without slowing down their sites.

= Why Doczur? =

Most documentation plugins load heavy JavaScript, conflict with popular themes, or require dozens of settings before your first article appears. Doczur is different:

* **5-minute setup** — A built-in wizard creates your first documentation portal and publishes it before your coffee gets cold.
* **Zero theme conflict** — Every frontend element is scoped under `.itsdz-docs`, so Doczur never leaks styles into your theme.
* **Ultra-light frontend** — Less than 30 KB of JavaScript (gzipped). The search modal loads on-demand only when the user opens it.
* **No jQuery** — Built with modern Vanilla TypeScript and React for the admin.
* **Performance-first** — Views are buffered and written to the database in batches every 5 minutes, not on every page load.

= Free Features =

* **One documentation project** (one knowledge base)
* Unlimited sections (3 levels deep), unlimited articles
* Drag-and-drop article ordering
* Gutenberg block editor support + shortcodes
* **Instant full-text search** (MySQL FULLTEXT — no `LIKE %keyword%`)
* Auto-generated table of contents, breadcrumbs, Previous / Next navigation
* Related articles
* Light / dark mode (respects system preference, user-toggleable)
* Print-friendly layout
* Mobile slide-out navigation
* "Was this helpful?" feedback system
* Reading time estimate
* Basic view counter (buffered, performance-safe)
* Two layout modes: **canvas** (full-page, no theme header/footer) and **theme** (integrates with your active theme)
* Three template styles: **Clean**, **Modern**, **Compact**
* Custom brand color per knowledge base
* Import / export (JSON)
* Translation-ready, RTL support
* SEO plugin compatible (Yoast SEO, Rank Math, AIOSEO)

= Gutenberg Blocks =

* **Doczur Search** — Embed a search box anywhere on your site. Submits to your KB page and auto-opens the search modal with the entered query pre-filled.
* **Doczur Article List** — Display a linked list of articles from any KB. Ideal for sidebars, landing pages, or related-content widgets.

= Shortcodes =

* `[doczur_search kb_id="123"]` — search form for a specific KB
* `[doczur_docs_list kb_id="123" limit="5" show_section="true"]` — article list

= Pro Features (coming soon) =

* Multiple documentation projects (multi-KB) + product switcher
* Product versioning (v1.x / v2.x switcher with version-specific URLs)
* WooCommerce integration — automatic Documentation tab on product pages
* Advanced analytics dashboard (health score, no-result searches, exit rate)
* Access control (role-based, password-protected, buyer-gated docs)
* Additional templates + visual customizer
* Contributor workflow (review, approval, scheduled publishing)
* Importers for BetterDocs, EazyDocs, weDocs, Echo Knowledge Base, Markdown, CSV
* AI assistant (outline generator, draft from existing content, FAQ generator)

= Privacy =

Doczur does not send any data to external servers. The "Was this helpful?" feedback and view counts are stored locally in your WordPress database. IP addresses are stored as irreversible hashes (SHA-256) for rate-limiting purposes only.

When uninstalling, data is deleted only if you opt in to data removal in the plugin settings. By default, your content and settings are preserved.

== Installation ==

**Automatic installation (recommended)**

1. Log in to your WordPress admin panel and go to **Plugins → Add New**.
2. Search for **Doczur**.
3. Click **Install Now**, then **Activate**.

**Manual installation**

1. Download the plugin ZIP from the WordPress.org plugin directory.
2. Go to **Plugins → Add New → Upload Plugin**.
3. Choose the ZIP file and click **Install Now**, then **Activate**.

**After activation**

1. Go to **Doczur** in your admin sidebar.
2. The Setup Wizard launches automatically — follow the 5 steps to publish your first documentation portal.

== Frequently Asked Questions ==

= Will Doczur conflict with my theme? =

No. Doczur uses two layout modes. In **canvas mode** (the default), it renders a fully independent page that bypasses your theme entirely. In **theme mode**, it integrates with your theme's header and footer, and all CSS is scoped under `.itsdz-docs` so it cannot affect other parts of your site.

= Does Doczur slow down my site? =

No — in fact, we designed Doczur specifically to avoid the performance problems common in other documentation plugins:

* Assets load **only on documentation pages** (conditional enqueue).
* The search modal JavaScript is loaded **on-demand**, only when the user opens the search.
* Page views are written to the database in **5-minute batches**, never on every request.
* The frontend bundle is under **30 KB gzipped**.

= How is Doczur search different from the built-in WordPress search? =

Doczur uses a dedicated search index table with MySQL **FULLTEXT** indexing. It never runs `LIKE %keyword%` queries against `wp_posts`, which are slow and load your entire database. Searches are also cached in the object cache (Redis / Memcached if available, transients otherwise).

= Can I use Doczur with the Classic Editor? =

Yes. Both `[doczur_search]` and `[doczur_docs_list]` shortcodes work in any editor, widget, or page builder that supports shortcodes.

= Is Doczur compatible with page caching plugins? =

Yes. The feedback ("Was this helpful?") system uses JavaScript + REST API instead of PHP sessions, so it works correctly behind WP Rocket, LiteSpeed Cache, W3 Total Cache, and similar caching plugins.

= Can I translate Doczur? =

Yes. Doczur is fully internationalized (i18n). All strings use the `doczur` text domain. Translations can be contributed on translate.wordpress.org.

= Does Doczur work with RTL languages? =

Yes. The compiled CSS includes an automatically generated RTL stylesheet (`style-frontend-rtl.css`) loaded by WordPress when an RTL language is active.

= How many knowledge bases can I have in the free version? =

One. If you need multiple knowledge bases for different products, that is a Pro feature.

= Where is my data stored? =

All data is stored in your WordPress database. Doczur creates four custom tables: `{prefix}itsdz_search_index`, `{prefix}itsdz_search_log`, `{prefix}itsdz_feedback`, and `{prefix}itsdz_views`. Articles and knowledge bases are stored as standard WordPress custom post types and can be exported like any other post type.

== Screenshots ==

1. Knowledge base landing page — hero section with instant search trigger, section cards, and article list.
2. Single documentation article — sidebar navigation, auto-generated table of contents, breadcrumbs, feedback widget, and related articles.
3. Instant search modal — opens on Ctrl+K or by clicking the search button, results appear as you type.
4. Setup Wizard — 5-step guided wizard for publishing your first documentation portal in under 5 minutes.
5. Documentation Tree Builder — drag-and-drop article ordering, inline status toggles, and auto-save.
6. Dark mode — automatically respects the visitor's system preference with a manual override toggle.

== Changelog ==

= 0.1.0 =
* Initial public release.
* Knowledge base and article custom post types with section, tag, and version taxonomies.
* Setup Wizard (5 steps), Documentation Tree Builder, Settings screen, Import/Export UI.
* Gutenberg block editor admin interface (React + TypeScript).
* Instant full-text search with MySQL FULLTEXT index.
* Auto table of contents, breadcrumbs, Previous/Next navigation, related articles.
* "Was this helpful?" feedback system.
* Buffered view counter (5-minute WP-Cron flush, Action Scheduler compatible).
* Dark mode, print layout, mobile slide-out navigation.
* `itsdz/search` and `itsdz/docs-list` Gutenberg blocks.
* `[doczur_search]` and `[doczur_docs_list]` shortcodes.
* Canvas and theme layout modes.
* Clean, Modern, and Compact template styles.
* Translation-ready with RTL support.
* REST API (`itsdz/v1`) for admin and public endpoints.

== Upgrade Notice ==

= 0.1.0 =
First release — no upgrade steps required.
