# Doczur — Product Documentation Platform (WordPress Plugin)
### Prefix: `itsdz` | দুই রিসার্চ পাসের সংশ্লেষণ + সম্পূর্ণ বিল্ড রোডম্যাপ

---

## ০. দুই রিসার্চ থেকে কী নেওয়া হচ্ছে

আপনার পাঠানো এনালাইসিসটা (GS-এর করা) এবং আমার আগের রিসার্চ — দুটোই বেশিরভাগ জায়গায় একমত, কিন্তু কিছু জায়গায় ভিন্ন এমফ্যাসিস দিয়েছে। কোনটা নিচ্ছি, কেন নিচ্ছি:

| বিষয় | GS-এর এনালাইসিস | আমার রিসার্চ | Doczur-এ যা নেওয়া হচ্ছে |
|---|---|---|---|
| Competitor install সংখ্যা | BetterDocs 30,000+ | BetterDocs 40,000+ | দুটোই "bucketed" রেঞ্জ, নির্দিষ্ট নাম্বার নিয়ে না ভেবে **অর্ডার অফ ম্যাগনিটিউড** হিসেবে ধরা হবে — BetterDocs ক্লিয়ার লিডার |
| ফ্ল্যাগশিপ Pro ডিফারেনশিয়েটর | **Product versioning** (v1.x/v2.x switcher) | **WooCommerce প্রোডাক্ট-ডক লিংকিং** | **দুটোই রাখা হচ্ছে।** এই দুইটা মার্কেটে আলাদা আলাদা প্রতিযোগীর কাছে আছে (versioning কারও কাছেই ভালোভাবে নেই, WooCommerce link শুধু MinervaKB-এ আছে) — একসাথে দিলে Doczur-এর কোনো একক প্রতিযোগীর নেই এমন কম্বিনেশন তৈরি হয় |
| Free tier-এ কয়টা KB | ১টা (multi-KB Pro-gated) | আনলিমিটেড (BasePress-এর মতো ইনস্টল-জেতানো স্ট্র্যাটেজি) | **GS-এর পক্ষে যাচ্ছি:** single-KB free + multi-KB Pro রাখা হবে, কারণ আপনার পজিশনিং "product documentation platform" — একটা প্রোডাক্টের জন্য একটাই docs portal free-তে যথেষ্ট, আর multi-product হওয়া মানেই সিরিয়াস ইউজার যে টাকা দিতে রাজি |
| Positioning statement | "Documentation plugin with easiest admin, versions, fast search, zero theme conflict" | "সহজ ব্যবহার + AI + WooCommerce" | মার্জ করে: **"৫ মিনিটে product documentation, যা আপনার সাইটের স্পিড কমায় না বরং লোড-ফিল হালকা রাখে"** |
| Technical stack | React + TypeScript admin, CPT-based content | REST API + Gutenberg blocks | GS-এর React/TS স্ট্যাক গ্রহণ করা হচ্ছে (Vue-এর চেয়ে ভালো long-term WP compatibility) |
| Security/Performance checklist | খুবই ডিটেইলড, ব্যবহারযোগ্য | সাধারণ নির্দেশনা | GS-এর checklist-কে বেসলাইন ধরে **itsdz-স্পেসিফিক** করে নিচে বিস্তারিত করা হয়েছে |

**নতুন সিদ্ধান্ত (GS-এর এনালাইসিসে ছিল না):** competitor-দের ভাঙা জায়গা (site slow করে দেওয়া, JS bloat, DB query bloat) থেকে সুযোগ নিয়ে Doczur-এর core marketing claim হবে — *"the docs plugin that makes your site feel faster, not slower."* এটা অর্জন করতে নিচে একটা নির্দিষ্ট পারফরম্যান্স বাজেট বেঁধে দেওয়া হয়েছে (সেকশন ৬)।

---

## ১. ব্র্যান্ডিং ও নেমিং কনভেনশন

- **প্রোডাক্ট নাম:** Doczur
- **ট্যাগলাইন:** Doczur — Product Documentation, Knowledge Base & Help Center for WordPress
- **প্রিফিক্স:** `itsdz_` (functions, hooks, options), `itsdz-` (CSS classes, slugs, asset handles), namespace `ItsDZ\Doczur`
- **Text domain:** `doczur`
- **Main plugin file:** `doczur.php` → বুটস্ট্র্যাপ করবে `ItsDZ\Doczur\Core\Plugin`

নামকরণ প্যাটার্ন সবজায়গায় consistent রাখুন — এটা পরে কোড রিভিউয়ের সময় সবচেয়ে বেশি চোখে পড়া ইনকনসিস্টেন্সি হয় (কিছু জায়গায় `itsdz_`, কিছু জায়গায় `doczur_` — যেকোনো একটা বেছে সব জায়গায় ব্যবহার করুন, আমি সাজেস্ট করছি সব জায়গায় `itsdz`)।

---

## ২. পজিশনিং ও ফাইনাল ফিচার ডিফারেনশিয়েটর

**Core positioning:** সহজতম প্রোডাক্ট ডকুমেন্টেশন অভিজ্ঞতা — ৫ মিনিটে প্রথম পাবলিশ, জিরো থিম কনফ্লিক্ট, এবং সাইটের স্পিডে কোনো নেগেটিভ ইমপ্যাক্ট না ফেলে বরং হালকা-লাগা ফিল দেওয়া।

**টার্গেট ইউজার:** WordPress plugin/theme developer, SaaS founder, software company, WooCommerce digital/physical product seller, এজেন্সি, ইন্টারনাল কোম্পানি ডকুমেন্টেশন।

**যা কোনো একক প্রতিযোগীর কাছে নেই, কিন্তু Doczur-এ থাকবে:**
1. Product versioning (v1.x/v2.x switcher + version-specific URL)
2. WooCommerce প্রোডাক্ট-টু-আর্টিকেল নেটিভ লিংকিং (প্রোডাক্ট পেজে অটো ট্যাব)
3. "Documentation health score" + no-result search রিপোর্ট (content gap finder)
4. Setup wizard যা ৫ মিনিটে প্রথম portal পাবলিশ করে দেয়
5. Theme-independent, ultra-light frontend (< 30KB JS gzipped বাজেট)

---

## ৩. ডাটাবেজ ডিজাইন

### ৩.১ Content model (WP Core টেবিল ব্যবহার করে — Custom Post Type + Taxonomy)

কনটেন্ট পুরোপুরি custom টেবিলে না রেখে **CPT-ভিত্তিক** রাখা হচ্ছে (GS-এর সাজেশন মেনে) — এতে বিনামূল্যে পাওয়া যাবে: revisions, REST API base, SEO plugin compatibility, native export/import, Gutenberg।

| Object | Type | Slug |
|---|---|---|
| Documentation Project (একটা product/KB) | Custom Post Type | `itsdz_kb` |
| Article | Custom Post Type | `itsdz_doc` |
| Section (hierarchical, ৩ লেভেল পর্যন্ত) | Taxonomy (hierarchical) | `itsdz_section` |
| Tag | Taxonomy (non-hierarchical) | `itsdz_tag` |
| Version (Pro) | Taxonomy (non-hierarchical) | `itsdz_version` |

**`itsdz_kb` (Project/KB) — post meta:**
```
_itsdz_kb_logo          (attachment ID)
_itsdz_kb_brand_color   (hex)
_itsdz_kb_theme_mode    (light|dark|system)
_itsdz_kb_template      (clean|modern|compact)
_itsdz_kb_doc_type      (software|wp-plugin|wp-theme|physical-product|internal|blank)
_itsdz_kb_slug_base     (custom URL base, e.g. /docs/product-name/)
_itsdz_kb_active_version (Pro — current default version term ID)
```

**`itsdz_doc` (Article) — post meta:**
```
_itsdz_kb_id            (parent itsdz_kb post ID — required, indexed via meta query or better: also store as a taxonomy relation for query speed)
_itsdz_order            (integer, drag-drop এর জন্য menu_order ব্যবহার করাই ভালো — সরাসরি WP core কলাম, নতুন meta লাগবে না)
_itsdz_reading_time     (auto-calculated, cached)
_itsdz_last_reviewed    (date — stale-content reminder-এর জন্য, Pro)
_itsdz_owner            (user ID, Pro — article owner/contributor workflow)
```
> নোট: `menu_order` (WP core কলাম) drag-drop ordering-এর জন্য ব্যবহার করুন, নতুন meta বানাবেন না — এটা পারফরম্যান্সে সরাসরি সুবিধা দেয় কারণ `wp_posts` টেবিলে ইনডেক্সড কলাম, আলাদা meta JOIN লাগে না।

### ৩.২ Custom টেবিল (শুধু high-volume / non-relational ডেটার জন্য)

Custom টেবিল **শুধু তখনই** — যখন WP core টেবিল দিয়ে efficient ভাবে করা যায় না। এই ৪টা দরকার:

```sql
-- ১. Search index (pre-computed, LIKE %keyword% এড়ানোর জন্য)
CREATE TABLE {$prefix}itsdz_search_index (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  article_id BIGINT UNSIGNED NOT NULL,
  kb_id BIGINT UNSIGNED NOT NULL,
  title TEXT NOT NULL,
  content_plain LONGTEXT NOT NULL,
  keywords TEXT NULL,
  weight FLOAT DEFAULT 1.0,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY article_id (article_id),
  KEY kb_id (kb_id),
  FULLTEXT KEY search_ft (title, content_plain, keywords)
) ENGINE=InnoDB;

-- ২. Search query log (analytics + no-result report, Pro)
CREATE TABLE {$prefix}itsdz_search_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kb_id BIGINT UNSIGNED NOT NULL,
  query VARCHAR(255) NOT NULL,
  results_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  clicked_article_id BIGINT UNSIGNED NULL,
  ip_hash CHAR(64) NULL,
  created_at DATETIME NOT NULL,
  KEY kb_id_created (kb_id, created_at),
  KEY results_count (results_count)
) ENGINE=InnoDB;

-- ৩. Feedback (Was this helpful?)
CREATE TABLE {$prefix}itsdz_feedback (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  article_id BIGINT UNSIGNED NOT NULL,
  kb_id BIGINT UNSIGNED NOT NULL,
  helpful TINYINT(1) NOT NULL,
  comment TEXT NULL,
  ip_hash CHAR(64) NULL,
  created_at DATETIME NOT NULL,
  KEY article_id (article_id)
) ENGINE=InnoDB;

-- ৪. View counter (daily rollup — প্রতি pageview-এ row insert না করে UPSERT)
CREATE TABLE {$prefix}itsdz_views (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  article_id BIGINT UNSIGNED NOT NULL,
  kb_id BIGINT UNSIGNED NOT NULL,
  view_date DATE NOT NULL,
  view_count INT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY article_date (article_id, view_date),
  KEY kb_id (kb_id)
) ENGINE=InnoDB;
```

**Pro-only (Phase 3+):**
```sql
-- WooCommerce প্রোডাক্ট-টু-আর্টিকেল লিংক (ফ্ল্যাগশিপ ফিচার)
CREATE TABLE {$prefix}itsdz_product_links (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  article_id BIGINT UNSIGNED NOT NULL,
  wc_product_id BIGINT UNSIGNED NOT NULL,
  display_order SMALLINT UNSIGNED DEFAULT 0,
  UNIQUE KEY article_product (article_id, wc_product_id),
  KEY wc_product_id (wc_product_id)
) ENGINE=InnoDB;
```

### ৩.৩ কেন এই স্প্লিট (CPT বনাম Custom Table)

- **CPT-তে রাখা:** যেকোনো কিছু যা edit/revision/permission দরকার (KB, Article) → relational integrity ও WP core ফিচার ফ্রি।
- **Custom Table-এ রাখা:** যেকোনো কিছু যা **high-write বা read-heavy, aggregatable, বা full-text search দরকার** (views, search log, feedback, search index) → `wp_posts`/`wp_postmeta` কে ভারী করলে পুরো সাইটের query performance-এ প্রভাব পড়ে, তাই আলাদা রাখা।

### ৩.৪ Migration/versioning স্ট্র্যাটেজি

- `wp_options`-এ একটা key: `itsdz_db_version`
- Activation hook-এ চেক করবে current constant vs stored version; শুধু mismatch হলে `dbDelta()` চালাবে
- **প্রতি request-এ migration চেক করা যাবে না** (GS-এর ফ্ল্যাগ করা পারফরম্যান্স ইস্যু) — শুধু `admin_init` hook-এ, আর সেখানেও একটা transient দিয়ে throttle করা (দিনে একবার চেক)

---

## ৪. ব্যাকএন্ড আর্কিটেকচার (PHP)

### ৪.১ ফোল্ডার স্ট্রাকচার

```
doczur/
├── doczur.php                      # Bootstrap, header comment, version const
├── uninstall.php                   # Opt-in data cleanup
├── composer.json                   # PSR-4 autoload: ItsDZ\Doczur\ => includes/
├── package.json                    # React admin app + frontend TS build
├── build/                          # Compiled JS/CSS (gitignored, built by CI)
├── includes/
│   ├── Core/
│   │   ├── Plugin.php              # Singleton bootstrap, service registry
│   │   ├── Activator.php
│   │   ├── Deactivator.php
│   │   └── Migrations/
│   │       ├── Migration_1_0_0.php
│   │       └── Migrator.php
│   ├── PostTypes/
│   │   ├── KB_Post_Type.php
│   │   └── Article_Post_Type.php
│   ├── Taxonomies/
│   │   ├── Section_Taxonomy.php
│   │   ├── Tag_Taxonomy.php
│   │   └── Version_Taxonomy.php    # Pro
│   ├── REST/
│   │   ├── REST_Controller.php     # abstract base w/ shared permission logic
│   │   ├── KB_Controller.php
│   │   ├── Article_Controller.php
│   │   ├── Search_Controller.php
│   │   ├── Feedback_Controller.php
│   │   └── Analytics_Controller.php # Pro
│   ├── Search/
│   │   ├── Indexer.php             # save_post hook → rebuild index row
│   │   └── Search_Service.php      # FULLTEXT query wrapper + caching
│   ├── Analytics/
│   │   ├── View_Tracker.php        # buffered write, flush via cron
│   │   └── Health_Score.php        # Pro
│   ├── Admin/
│   │   ├── Admin_Menu.php
│   │   ├── Setup_Wizard.php
│   │   └── Assets.php              # conditional enqueue
│   ├── Frontend/
│   │   ├── Template_Loader.php     # template_include filter
│   │   ├── Assets.php              # conditional enqueue, only on doc pages
│   │   └── Blocks/                 # Gutenberg block registration (PHP side)
│   ├── Integrations/
│   │   └── WooCommerce/
│   │       ├── Product_Tab.php     # Pro
│   │       └── Product_Linker.php  # Pro
│   ├── Security/
│   │   ├── Capabilities.php
│   │   └── Rate_Limiter.php
│   └── Utils/
│       ├── Cache.php               # object cache wrapper w/ transient fallback
│       └── Sanitize.php
├── assets/
│   ├── admin-src/                  # React + TypeScript source
│   │   ├── app/
│   │   ├── components/
│   │   ├── store/                  # @wordpress/data
│   │   └── index.tsx
│   └── frontend-src/               # Vanilla TS, no framework
│       ├── search.ts
│       ├── toc.ts
│       ├── feedback.ts
│       └── style.css               # CSS custom properties, scoped
└── languages/
```

### ৪.২ কোর প্রিন্সিপল

- **PSR-4 + Composer autoload** — vibe-coding tool (Cursor/Claude Code ইত্যাদি) দিয়ে কাজ করলেও ফাইল-প্রতি এক ক্লাস নিয়ম মানলে রিভিউ করা অনেক সহজ হবে।
- **Service registry প্যাটার্ন** — `Plugin.php`-এ প্রতিটা service class instantiate ও hook-এ register হবে একটা জায়গা থেকে, ছড়িয়ে-ছিটিয়ে `add_action()` কল বিভিন্ন ফাইলে না রেখে।
- **Capability:** কাস্টম ক্যাপাবিলিটি `itsdz_manage_docs` রেজিস্টার করুন, ডিফল্ট `manage_options`-এ ম্যাপ করুন, কিন্তু সেটিংসে অ্যাডমিন চাইলে অন্য রোলকে দিতে পারবে এমন UI রাখুন (agency ইউজ-কেসের জন্য গুরুত্বপূর্ণ)।
- **Background jobs:** Action Scheduler (bundled library, WooCommerce-ও এটা ব্যবহার করে, compatibility ভালো) — view counter flush, search index rebuild, stale-content reminder email-এর জন্য।
- **REST namespace:** `itsdz/v1`

### ৪.৩ REST API এন্ডপয়েন্ট লিস্ট

| Method | Route | Access | নোট |
|---|---|---|---|
| GET/POST | `/itsdz/v1/kb` | Admin | KB list/create |
| GET/PUT/DELETE | `/itsdz/v1/kb/{id}` | Admin | |
| GET/POST | `/itsdz/v1/articles` | Admin | |
| GET/PUT/DELETE | `/itsdz/v1/articles/{id}` | Admin | |
| POST | `/itsdz/v1/articles/reorder` | Admin | bulk menu_order + section update |
| GET | `/itsdz/v1/search` | Public | rate-limited, cached response, `Cache-Control` header |
| POST | `/itsdz/v1/feedback` | Public | rate-limited, nonce না লাগলেও IP+time throttle লাগবে |
| GET | `/itsdz/v1/analytics/summary` | Admin, Pro | |
| POST | `/itsdz/v1/import` | Admin | BetterDocs/Echo/weDocs/EazyDocs importer |
| GET | `/itsdz/v1/export` | Admin | JSON export |
| GET/POST | `/itsdz/v1/product-links` | Admin, Pro | WooCommerce article↔product mapping |

**প্রতিটা controller-এ বাধ্যতামূলক:** `permission_callback` (কখনো `__return_true` admin route-এ না), capability check, input sanitize + validate, output escape context অনুযায়ী।

---

## ৫. Admin/Backend UX আর্কিটেকচার (React app)

- **Stack:** React + TypeScript, `@wordpress/components`, `@wordpress/data` (custom store `itsdz-store`), `@wordpress/api-fetch`
- **একটাই SPA screen** (`wp-admin/admin.php?page=doczur`) যেখানে ভেতরে client-side routing (react-router বা `@wordpress/url` দিয়ে hash routing) — Setup Wizard, Documentation Tree Builder, Settings, Analytics সব এর ভেতরে।

**Setup Wizard (৫ ধাপ, GS-এর ডিজাইন অনুসরণ করে):**
1. Documentation type নির্বাচন (software/wp-plugin/wp-theme/physical-product/internal/blank) → auto-generate starter section structure (Getting Started, Installation, Configuration, FAQ, Changelog ইত্যাদি)
2. Basic info (নাম, URL slug, লোগো, ব্র্যান্ড কালার, থিম মোড)
3. Starter structure প্রিভিউ + এডিট
4. Template নির্বাচন (Clean/Modern/Compact)
5. Publish button

**Documentation Tree Builder:**
- বাঁ পাশে drag-drop tree (react-dnd বা `@dnd-kit`), ডান পাশে inline editor preview
- Auto-save (debounced, ২-৩ সেকেন্ড পরে), unsaved-change ব্রাউজার warning
- Keyboard shortcuts (Cmd/Ctrl+S সেভ, Cmd/Ctrl+N নতুন আর্টিকেল)
- Bulk move, duplicate, draft/published toggle সরাসরি ট্রি থেকে

**গুরুত্বপূর্ণ:** Admin React app-এর bundle শুধু Doczur-এর নিজের অ্যাডমিন পেজে লোড হবে — কোনো অবস্থাতেই ওয়ার্ডপ্রেসের বাকি অ্যাডমিন পেজে (Posts, Dashboard ইত্যাদি) enqueue হবে না। এটা conditional check `get_current_screen()` দিয়ে নিশ্চিত করুন।

---

## ৬. ফ্রন্টএন্ড ডিজাইন ও "সাইট স্লো না করা" পারফরম্যান্স স্ট্র্যাটেজি

এটাই আপনার সবচেয়ে বড় ব্র্যান্ড প্রমিজ, তাই একে নির্দিষ্ট, মাপযোগ্য নিয়মে ভাঙা দরকার — শুধু "আমরা fast রাখব" বললে vibe-coding সেশনে এটা হারিয়ে যাবে।

### ৬.১ Template সিস্টেম (থিম-ইন্ডিপেন্ডেন্ট)

- নিজস্ব template `template_include` filter দিয়ে ইনজেক্ট হবে, থিমের page template-এর উপর নির্ভর করবে না
- দুইটা মোড অফার করুন: (ক) থিমের `header()`/`footer()` সহ (সাইটের সাথে মিশে থাকা লুক), (খ) সম্পূর্ণ ক্যানভাস মোড (Elementor-এর "Full Width, No Header/Footer"-এর মতো, ডেডিকেটেড docs সাইটের জন্য)
- সব CSS একটা root ক্লাসের ভেতরে স্কোপড: `.itsdz-docs { ... }` — কখনো global element selector (যেমন খালি `h2 { }`) ব্যবহার করবেন না, সবসময় `.itsdz-docs h2 { }` — এটাই থিম-কনফ্লিক্ট এড়ানোর সবচেয়ে বড় নিয়ম (BetterDocs-এর সবচেয়ে বড় অভিযোগ ছিল এটাই)

### ৬.২ পারফরম্যান্স বাজেট (মাপযোগ্য টার্গেট)

| মেট্রিক | টার্গেট |
|---|---|
| Frontend JS (gzipped, initial load) | ≤ 30 KB |
| Frontend CSS (gzipped) | ≤ 20 KB |
| jQuery dependency | কোনোভাবেই না |
| Search response time (cached) | ≤ 100 ms |
| Assets লোড হবে | শুধু doc-related পেজে (conditional enqueue, `is_singular('itsdz_doc')` ইত্যাদি) |
| Admin React bundle | শুধু Doczur admin স্ক্রিনে |
| Database query per pageview (frontend) | মূল আর্টিকেল পেজ ≤ ৩-৪টা অতিরিক্ত কোয়েরি (WP core query বাদে) |

### ৬.৩ কৌশল

- **Search-modal lazy load:** সার্চ মডালের JS শুধু ইউজার Cmd/Ctrl+K চাপলে বা সার্চ আইকনে ক্লিক করলে dynamic `import()` হবে, পেজ লোডে না
- **View counter:** প্রতি pageview-এ সরাসরি DB write **না** — object cache/transient-এ কাউন্ট বাফার করে cron/Action Scheduler দিয়ে প্রতি কয়েক মিনিটে batch flush (UPSERT with `view_date`)
- **Search:** কখনো `WP_Query` + `LIKE %keyword%` না — pre-built `itsdz_search_index` টেবিলের উপর MySQL FULLTEXT ব্যবহার, রেজাল্ট object cache-এ ক্যাশ করা
- **Images:** আর্টিকেল কনটেন্টের সব ইমেজে অটো `loading="lazy"` inject (content filter দিয়ে)
- **Fonts:** ডিফল্ট system font stack (`-apple-system, Segoe UI, Roboto...`), কোনো external font blocking render না করবে; কাস্টম ফন্ট চাইলে Pro-তে `font-display: swap` সহ
- **Object cache wrapper:** Redis/Memcached থাকলে ব্যবহার করবে, না থাকলে transient-এ fallback করবে — একটাই `Cache.php` utility ক্লাস দিয়ে
- **REST public responses:** যেখানে সম্ভব `Cache-Control` header সেট করে ব্রাউজার/CDN ক্যাশিং সক্রিয় রাখুন
- **Full-page cache compatibility:** ফিডব্যাক ("Was this helpful?") ফর্ম PHP session-নির্ভর না রেখে JS + REST দিয়ে হবে, যাতে WP Rocket/LiteSpeed-এর মতো পেজ ক্যাশ প্লাগিনের সাথে কনফ্লিক্ট না হয়
- **Autoloaded options:** বড় সেটিংস ব্লব `autoload => false` করে রাখুন — ছোট, ঘন ঘন-দরকারি সেটিংস আলাদাভাবে autoload রাখুন। এই ভুলটাই (সব কিছু autoload=yes) সবচেয়ে বেশি সাইট স্লো করে এমন root cause

### ৬.৪ Frontend UX উপাদান (GS-এর লিস্ট, পুরোপুরি গ্রহণযোগ্য)

বাম নেভিগেশন (collapsible) + মাঝে আর্টিকেল + ডানে auto-TOC, উপরে instant search (Cmd/Ctrl+K), Previous/Next, breadcrumb, copy-link, print view, dark mode, mobile slide-out nav, "Was this helpful?", related articles, last-updated date, version badge (Pro), "Still need help?" CTA। এই পুরো লিস্টটা রাখুন — কোনো পরিবর্তনের দরকার নেই।

---

## ৭. Security Checklist (itsdz-স্পেসিফিক)

GS-এর লিস্টই বেসলাইন, নিচে সরাসরি Doczur-এ প্রয়োগযোগ্য করে দেওয়া হলো:

- [ ] প্রতিটা `register_rest_route()`-এ explicit `permission_callback` — কখনো `__return_true` অ্যাডমিন রুটে না
- [ ] Public রুট (search, feedback) → capability লাগবে না কিন্তু **rate limiting আবশ্যক** (`Rate_Limiter.php` — IP hash + transient, প্রতি মিনিটে সর্বোচ্চ N রিকোয়েস্ট)
- [ ] সব `$wpdb` কোয়েরিতে `$wpdb->prepare()` — raw SQL string concatenation একদম না
- [ ] Draft/private আর্টিকেল কখনো search index-এ ঢুকবে না, public REST response-এ leak হবে না (`Indexer.php`-এ post_status চেক)
- [ ] Attachment (ফাইল আপলোড) permission ভ্যালিডেশন, SVG আপলোড সরাসরি enable না
- [ ] AI ফিচার (ভবিষ্যতে) — কনটেন্ট বাইরের API-তে পাঠানোর আগে explicit per-KB consent টগল
- [ ] Uninstall-এ ডেটা ডিলিট শুধু ইউজার সেটিংসে opt-in করলে (ডিফল্টে ডেটা থেকে যাবে)
- [ ] Migration শুধু version mismatch হলে চলবে, প্রতি রিকোয়েস্টে না (৩.৪ দেখুন)
- [ ] Nonce ব্যবহার হবে কিন্তু **শুধু authorization হিসেবে না** — nonce শুধু CSRF protection, capability check আলাদাভাবে থাকতেই হবে

**সবচেয়ে গুরুত্বপূর্ণ automated test:** "একটা private/draft আর্টিকেল কখনো public search result-এ আসবে না" — এটার জন্য PHPUnit টেস্ট বাধ্যতামূলক লিখুন, ম্যানুয়াল টেস্টে ভরসা করবেন না।

---

## ৮. Free vs Pro ফিচার স্প্লিট (Doczur ফাইনাল)

### Free (WordPress.org)
- একটা Documentation Project (একটা `itsdz_kb`)
- Unlimited section (৩ লেভেল), unlimited article
- Drag-drop ordering, Gutenberg editor, draft/published
- Instant search (FULLTEXT-ভিত্তিক), auto-TOC, breadcrumb, prev/next, related articles
- Light/dark mode, print-friendly, reading time
- "Was this helpful?" ফিডব্যাক
- Gutenberg blocks + shortcode, theme-independent template
- SEO plugin compatibility, translation-ready, RTL
- Import/export JSON
- বেসিক ভিউ কাউন্ট (কোনো advanced analytics ছাড়া)

### Pro
- **Multiple Documentation Projects (multi-KB)** + product switcher
- **Product versioning** (v1.x/v2.x, version switcher, "older version" warning, version-specific URL)
- **WooCommerce প্রোডাক্ট-আর্টিকেল লিংকিং** (ফ্ল্যাগশিপ) — প্রোডাক্ট পেজে অটো Documentation ট্যাব, My Account সাপোর্ট এরিয়া, buyer-gated docs
- Advanced search (typo-tolerant, synonyms, no-result report)
- Analytics dashboard (health score, most viewed/searched, exit rate, weekly email)
- Access control (role-based, password-protected, per-user/group)
- Additional templates, visual customizer, white-label, Elementor widgets
- Workflow (contributor roles, review/approval, scheduled publishing, revision compare)
- Importer (BetterDocs/Echo/weDocs/EazyDocs/Markdown/CSV)
- AI (পরে, আলাদা ফেজে — সেকশন ৯ দেখুন)

**প্রাইসিং (দুই রিসার্চের মাঝামাঝি, GS-এর aggressive পজিশনের দিকে ঝুঁকিয়ে):**

| Plan | মূল্য | Sites |
|---|---|---|
| Free | $0 | Unlimited |
| Personal | $59/year | 1 |
| Business | $129/year | 5 |
| Agency | $249/year | Unlimited |
| Lifetime (launch deal, সীমিত সময়) | $299 | Unlimited |

---

## ৯. AI ফিচার — কবে আনবেন

MVP-তে AI বাধ্যতামূলক না। GS-এর সাজেশন সঠিক: প্রথমে core experience শক্ত করুন। AI আসবে Phase 6+ এ (Roadmap দেখুন), এবং তখনও:
- Draft-only — AI কখনো সরাসরি publish করবে না, ইউজার রিভিউ করে publish করবে
- BYO API key বা Doczur managed credit (আলাদা রেভিনিউ স্ট্রিম) — দুটোই অপশন
- আউটলাইন জেনারেটর, existing content থেকে draft, grammar fix, summarization, FAQ generation, no-result search থেকে missing-article suggestion, stale content detection

---

## ১০. ধাপে ধাপে ডেভেলপমেন্ট রোডম্যাপ (একা ভাইব-কোডিং, ~১৪-১৮ সপ্তাহ)

প্রতিটা ফেজের শেষে **"রিভিউ চেকপয়েন্ট"** দেওয়া আছে — এই পয়েন্টে আমাকে কোড দেখান, আমি রিভিউ করে দেব (আমি নিজে কোড লিখব না)।

### Phase 0 — Setup (৩-৫ দিন)
- Composer + PSR-4 autoload সেটআপ, plugin boilerplate, `Plugin.php` service registry
- Git repo, `.gitignore` (build/, node_modules/, vendor/)
- WPCS (WordPress Coding Standards) + PHP_CodeSniffer কনফিগার করুন — vibe-coding tool-কে বলুন এই স্ট্যান্ডার্ড মেনে কোড লিখতে
- **রিভিউ চেকপয়েন্ট ০:** ফোল্ডার স্ট্রাকচার, autoload, boilerplate ঠিক আছে কিনা

### Phase 1 — Foundation / Content Model (২ সপ্তাহ)
- `itsdz_kb`, `itsdz_doc` CPT রেজিস্ট্রেশন
- `itsdz_section`, `itsdz_tag`, `itsdz_version` taxonomy
- Capabilities রেজিস্ট্রেশন
- Activator/Deactivator, migration system + সব custom টেবিল তৈরি (dbDelta)
- বেসিক PHPUnit সেটআপ + প্রথম টেস্ট (post type registration test)
- **রিভিউ চেকপয়েন্ট ১:** DB schema বাস্তবে তৈরি হচ্ছে কিনা, migration idempotent কিনা (দুইবার চালালে এরর না দেয়), capability mapping ঠিক আছে কিনা

### Phase 2 — REST API + Backend Logic (২ সপ্তাহ)
- সব REST controller (সেকশন ৪.৩-এর লিস্ট অনুযায়ী)
- Permission callbacks, sanitization, rate limiter
- Search indexer (`save_post` hook → index row আপডেট)
- Search service (FULLTEXT কোয়েরি + cache)
- **রিভিউ চেকপয়েন্ট ২:** এইখানেই সবচেয়ে গুরুত্বপূর্ণ সিকিউরিটি রিভিউ — প্রতিটা রুটের permission_callback, prepared statement, draft-leak টেস্ট

### Phase 3 — Admin React App (৩ সপ্তাহ)
- Setup wizard (৫ ধাপ)
- Documentation tree builder (drag-drop, auto-save)
- Settings screen
- Import/export UI
- **রিভিউ চেকপয়েন্ট ৩:** React app শুধু নিজের স্ক্রিনে লোড হচ্ছে কিনা (bundle leak চেক), UX flow, accessibility (keyboard nav, ARIA)

### Phase 4 — Frontend Templates (৩ সপ্তাহ)
- Template loader, landing page, single article template
- TOC, breadcrumb, prev/next, related articles, reading time
- Dark mode, print layout, mobile nav
- Instant search UI (frontend)
- **রিভিউ চেকপয়েন্ট ৪:** পারফরম্যান্স বাজেট (সেকশন ৬.২) মেনে চলছে কিনা — এখানেই JS/CSS সাইজ, conditional enqueue, query count মাপুন (Query Monitor প্লাগিন দিয়ে)

### Phase 5 — Feedback, Analytics, WooCommerce Integration (২-৩ সপ্তাহ)
- Feedback system (frontend + REST)
- View tracker (buffered, Action Scheduler flush)
- Analytics dashboard (Pro)
- WooCommerce product-link mapping + product-page tab (Pro flagship)
- Product versioning UI + version switcher (Pro)
- **রিভিউ চেকপয়েন্ট ৫:** WooCommerce hook ঠিকমতো bail out করছে কিনা যখন WooCommerce active না, HPOS compatibility

### Phase 6 — Polish, i18n, QA, WordPress.org Prep (২ সপ্তাহ)
- Gutenberg blocks + shortcode
- জনপ্রিয় থিমে (Astra, GeneratePress, OceanWP, Twenty Twenty-Four) টেস্ট
- WPML/Polylang, RTL টেস্ট
- Accessibility অডিট (axe DevTools)
- readme.txt, screenshots, WordPress.org সাবমিশন প্রস্তুতি
- **রিভিউ চেকপয়েন্ট ৬ (ফাইনাল):** পুরো প্লাগিনের এন্ড-টু-এন্ড রিভিউ, security checklist (সেকশন ৭) সম্পূর্ণ পাস

### Phase 7+ (পোস্ট-লঞ্চ) — AI + Advanced Pro
- AI outline generator, draft assistant, summarization
- Advanced search (synonym, semantic)
- Migration importer (BetterDocs/Echo/weDocs/EazyDocs)

---

## ১১. কোড রিভিউয়ের জন্য আমাকে যা দেবেন

প্রতিটা checkpoint-এ এভাবে শেয়ার করুন যাতে আমি দ্রুত এবং accurate রিভিউ দিতে পারি:
1. সংশ্লিষ্ট ফোল্ডার/ফাইলগুলো (পুরো repo না হলেও চলবে, সেই ফেজের রিলিভেন্ট ফাইল)
2. কী কাজ করছে, কী করছে না (যদি কিছু incomplete থাকে)
3. কোনো নির্দিষ্ট জায়গায় সন্দেহ থাকলে সেটা বলুন (যেমন, "এই REST route-টা secure কিনা নিশ্চিত না")

**আমার রিভিউ চেকলিস্ট (প্রতি checkpoint-এ যা যাচাই করব):**
- [ ] WordPress Coding Standards মানা হয়েছে কিনা
- [ ] REST route-এ permission_callback + capability check
- [ ] SQL injection risk (raw query থাকলে prepare() ব্যবহার হয়েছে কিনা)
- [ ] XSS risk (output escaping — `esc_html()`, `esc_attr()`, `esc_url()` ঠিকঠাক জায়গায়)
- [ ] N+1 query বা অপ্রয়োজনীয় heavy query আছে কিনা
- [ ] Autoloaded option bloat আছে কিনা
- [ ] Conditional asset loading ঠিকমতো হচ্ছে কিনা (frontend/admin bundle leak)
- [ ] i18n (`__()`, `_e()` text domain সহ) ব্যবহার হয়েছে কিনা
- [ ] Draft/private কনটেন্ট leak হওয়ার কোনো পথ আছে কিনা

---

## ১২. পরের পদক্ষেপ

আপনি এখন Phase 0 শুরু করতে পারেন। যখন boilerplate + প্রাথমিক ফোল্ডার স্ট্রাকচার রেডি হবে, সেটা আমাকে দেখান — রিভিউ চেকপয়েন্ট ০ দিয়ে শুরু করি। ইনশাআল্লাহ ধাপে ধাপে এগোলে ১৪-১৮ সপ্তাহে একটা solid MVP + Pro-এর প্রথম ভার্সন দাঁড় করানো সম্ভব।
