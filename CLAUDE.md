# CLAUDE.md — guide for working on this project

Green Office Digital Platform for the Faculty of Agriculture, Kasetsart University.
Plain PHP 8 (no framework) + MySQL/MariaDB + Tailwind CSS (Play CDN). UI text is Thai.
Deploy target is classic shared hosting (FTP upload + phpMyAdmin); local dev uses Laragon (Apache + PHP + MariaDB + phpMyAdmin).

## Run locally
```bash
cp config/config.sample.php config/config.php   # set DB host/user/pass (Laragon default: 127.0.0.1:3306, root, no password)
php database/install.php                        # create tables + starting data (add --no-sample for clean; target DB must already exist — create it first)
php -S localhost:8000 -t public                 # http://localhost:8000  (admin: /admin/  admin / green2569)
```
After changing seed data in `database/install.php`, regenerate `database/seed.sql`:
`php database/install.php --dump-seed > database/seed.sql`

## Layout
- `public/` is the web root. Each page is one PHP file: load `app/bootstrap.php`, query, then include
  `app/views/public_top.php` … `public_bottom.php` (or `admin_top.php` … `admin_bottom.php`).
- `app/db.php` — `q()`, `rows()`, `row()`, `val()`; always pass values as `?` params.
- `app/helpers.php` — `e()` escape, `url()`, `redirect()`, `csrf_field()/csrf_check()`, `flash()`, `setting()`,
  `save_upload()`/`delete_upload()`/`upload_url()` (files live in `public/uploads/<folder>/`), `clean_url()`.
- `app/session_db.php` — PHP sessions stored in the database (`app_sessions` table), not local disk — the shared host may put requests on
  different app instances behind a load balancer; registered in `app/bootstrap.php`.
- `app/auth.php` — `require_login(['role', ...])` at the top of every admin page (admin always allowed).
- `app/data.php` — shared queries: resources (GHG computed from fuel + electricity × factors in settings),
  7-category criteria (`category_table()`, `category_progress()`), complaints, intro game stats.
- `public/assets/js/app.js` — running numbers (`data-count`), growing bars (`.grow-bar` + `data-w`), mascot eyes (`.js-pupil`), parallax (`data-parallax`/`data-depth`), intro game (`#checkin`), confirm dialogs (`data-confirm`).
- Theme colours/fonts and component classes (`btn-primary`, `card`, `input`, `label`, `chip`…) live in `app/views/partials/head.php`.

## Rules to keep
- Escape every output with `e()`; never build SQL with string concatenation of user input.
- Every form that changes data: `<?= csrf_field() ?>` in the form and `csrf_check()` on POST.
- Admin pages must call `require_login()` first.
- Uploads only through `save_upload()` (whitelisted extensions, MIME checked, saved under `public/uploads/`).
- Never show complaint `name`/`contact` on public pages; public pages use `public_title`/`public_detail` only.
- Resource order on the site is fixed: fuel → GHG → electricity → water → paper (`resource_meta()`).
- Keep pages mobile-friendly (flex-wrap / auto-fit grids, tables inside `overflow-x-auto`) and touch targets ≥ 44px.

## Database (see database/schema.sql)
users, settings, app_sessions (PHP session storage), resource_monthly, categories, criteria_issues, criteria_indicators, criteria_subitems,
criteria_reports (one per indicator per year; checks_json = finished sub-items), criteria_evidence (links),
news, documents (about tabs + complaint plan; pdf/image/link), units (หน่วยงาน/สาขาวิชา options for joining a mission),
missions (target_count + mission_participants drive the progress bar — never set progress by hand), mission_participants
(one row per person who clicked เข้าร่วม — see `missions_with_progress()`/`unit_leaderboard()` in app/data.php and
admin › แดชบอร์ดภารกิจ), activities, audit_findings, complaints, complaint_plan_steps, game_responses.

Schema changes for an already-running database go in `database/migrations/<date>-<name>.sql` (run once via phpMyAdmin or the mysql CLI);
keep `schema.sql` as the full current structure for fresh installs.

News body is plain text with `[ข้อความ](url)` links and `![](url)` images, rendered by `render_body()` (escapes first);
categories and training links (`NEWS_CATS`, `NEWS_LINKS`) live in `app/helpers.php`.
