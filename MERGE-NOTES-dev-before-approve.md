# `dev-before-approve` → `dev-doczur` merge notes

Date: 2026-08-21  
Source branch: `dev-before-approve` (`c8d5f1a Working`, `e46caf9 added features`)  
Target branch: `dev-doczur` (already on the approved Nirdeshio / `itsmanzur-docs` identity)  
Merge-base: `a6d9fef Submitted- wp`

`dev-before-approve` still used the old public name **Doczur** (`doczur.php`, text domain `doczur`). That work is now on `dev-doczur` with the current public identity:

- Plugin Name: **Nirdeshio**
- Main file / WP.org slug / text domain: `itsmanzur-docs`
- Internal code left as-is: namespace `ItsDZ\Doczur`, prefix `itsdz_`, REST `itsdz/v1`, cache group `doczur`

This notes file is repo-only (excluded from the WordPress.org zip via `.distignore`).

## Name adaptation during merge

- Incoming i18n `'doczur'` → `'itsmanzur-docs'`
- Dashboard widget title/copy **Doczur** → **Nirdeshio**
- Compact template description kept from the approved branch: “Dense sidebar layout for large docs”
- Custom CSS enqueue from `dev-before-approve` kept, with `wp_set_script_translations( …, 'itsmanzur-docs', … )`

## What landed

### Public URL / rewrite (`c8d5f1a`)

- Documentation slug base is stored in option `itsdz_kb_slug_base` and applied on `init` (not only post meta)
- Section archives follow that slug: `{slug}/section/…` (rewrite schema `1.3.0`)
- Article REST list paginates (`per_page` + `X-WP-TotalPages`) so large projects load fully in admin
- Uninstall now also deletes `itsdz_kb_slug_base`, `itsdz_rewrite_version`, `itsdz_rewrite_flush`
- Tests: `tests/integration/SlugAndArticleListTest.php`, extra stubs in `tests/bootstrap.php`, unit coverage in `ContentModelTest.php`

### Admin: sections manager

- New screen `assets/admin-src/components/Sections.tsx` (create / rename / parent / Dashicon / delete)
- Nav item **Sections** in `App.tsx`
- REST helpers on `api.ts`: `listSections`, `createSection`, `updateSection`, `deleteSection`

### Admin: Markdown import

- `assets/admin-src/markdownImport.ts` + Import / Export UI to import a `.md` file as a draft article

### Admin: project settings chrome

- Documentation logo upload
- Landing intro
- Left nav skins: Accordion / Rail / Tree
- Toggles: table of contents, feedback, related articles, print
- Header links (label + URL, max 4)
- Custom CSS (sanitized, max 8000 chars)
- Docs Manager edit/preview article actions

### WP Admin dashboard

- New `includes/Admin/Dashboard_Widget.php`: drafts / need review / unsectioned counts, link to Nirdeshio

### Public frontend

- Nested tree sidebar (`templates/partials/nav-nodes.php`) when nav style is `tree`
- Section archive template `templates/section.php`
- Header links partial `templates/partials/header-links.php`
- Optional print button and optional TOC
- Empty-search popular articles
- Frontend CSS/JS for the new nav, section pages, print, popular search, header links

### Content model / REST

New KB meta (registered + returned by `KB_Controller`):

- `_itsdz_kb_nav_style`
- `_itsdz_kb_show_toc`
- `_itsdz_kb_show_feedback`
- `_itsdz_kb_show_related`
- `_itsdz_kb_show_print`
- `_itsdz_kb_custom_css`
- `_itsdz_kb_header_links`

Saving `_itsdz_kb_slug_base` now also calls `KB_Post_Type::persist_rewrite_slug()`.

## Files (36)

New: `Sections.tsx`, `markdownImport.ts`, `Dashboard_Widget.php`, `header-links.php`, `nav-nodes.php`, `section.php`, `SlugAndArticleListTest.php`

Updated: admin API/App/DocsManager/ImportExport/Settings/types/CSS, frontend index/search/CSS, Plugin, Assets, Documentation, Rewrite_Manager, Template_Loader, Article/KB post types, Meta_Fields, Article/KB REST, Section_Taxonomy, article/kb/navigation/shell templates, unit bootstrap + ContentModelTest, uninstall.

## Conflicts resolved (5)

| File | Resolution |
| --- | --- |
| `assets/admin-src/components/Settings.tsx` | Incoming nav/chrome settings; approved Compact copy + `itsmanzur-docs` |
| `includes/Frontend/Assets.php` | Incoming custom CSS enqueue + current text domain |
| `templates/article.php` | Incoming print + optional TOC + current text domain |
| `templates/partials/navigation.php` | Incoming tree nav + current text domain |
| `templates/partials/shell-end.php` | Incoming popular-search payload + current text domain |
