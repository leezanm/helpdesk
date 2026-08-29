# 01 — Current State Analysis

## 1. Stack summary (as-is)

| Layer | Current | Source |
|-------|---------|--------|
| Framework | **Laravel 8.83.27** | `composer.lock` |
| PHP constraint | `^7.3 \| ^8.0` (dev machine runs PHP 8.5) | `composer.json`, `php -v` |
| Auth | **Laravel Fortify 1.x** (custom `username` login, 2FA columns present) | `FortifyServiceProvider.php` |
| Authorization | **spatie/laravel-permission 4.x** (roles + permissions, `can:` gates) | `composer.json`, `web.php` |
| LDAP / AD | **adldap2/adldap2-laravel 6.x** — *currently commented out but wired in* | `FortifyServiceProvider.php` |
| Backup | **spatie/laravel-backup 6.x** | `BackupContoller.php` |
| Frontend build | **Laravel Mix 6 (webpack)** | `webpack.mix.js`, `package.json` |
| CSS/UI theme | Bootstrap 5 admin theme ("Themesbrand" / Skote-style) | `layouts/app.blade.php` |
| JS interactivity | **jQuery + AJAX + DataTables + ApexCharts/ECharts/Chart.js** | `public/assets/libs`, layout scripts |
| Database | **MySQL** (default `mysql` connection, port 3306) | `.env.example`, `config/database.php` |
| i18n | Bahasa Malaysia UI, `laravel-lang/lang` | views, `composer.json` |

- Composer packages installed: **333**; **5 are flagged `abandoned`** in the lockfile (most important: `adldap2/adldap2`).

## 2. Application domain

This is a **complaint / report management helpdesk** ("Sistem Aduan") for KKDW
(Kementerian Kemajuan Desa dan Wilayah). Core concepts:

- **Report / Aduan** (`Report`) — the central entity: has a category, status, attachments, and a trail of actions.
- **ReportTrail** — audit/action history per report, with its own status ref table.
- **ReportCategory**, **ReportStatus**, **ReportTrailStatus**, **TaskStatus** — reference/lookup tables (`app/Models/Ref`).
- **ReportAttachment**, **ReportFeedback** — file uploads and reporter feedback/ratings ("soal selidik").
- **User** — with `HasRoles`, assets (`UserAsset`), profile picture, LDAP-friendly `username`.
- **Department / Division / Officer / Type** — organizational structure.
- **AuditTrail** — global audit logging via `audit-trail` middleware.
- **Notifications** — database notifications (bell dropdown in the top bar), 5 notification classes.

### Domain map (models)

```
Report ──< ReportAttachment
       ──< ReportTrail >── ReportTrailStatus (ref)
       ──> ReportCategory (ref)
       ──> ReportStatus (ref)
       ──> User (reportBy / bpUser)
       ──  ReportFeedback

User ──< UserAsset
     ──  HasRoles (spatie: roles, permissions)
     ──  Department / Division / Officer / Type
```

19 migrations, 15 models (incl. 3 ref models), 23 web controllers + 4 API controllers.

## 3. Current UI / interaction pattern

This is the single most important finding for the Blade→Livewire migration.

The app is **server-rendered Blade with heavy jQuery/AJAX layered on top**:

- **86 Blade files**, organized by feature (`aduan`, `category`, `division`, `status`, `role`, `user`, `superadmin`, `laporan`).
- Lots of **modal partials** (`_modal/create`, `_modal/edit`, `_modal/show`, `partials/modal/...`).
- Controllers return **`response()->json([...])`** and even build **HTML strings inside PHP**
  (e.g. `ReportCategoryController::show` returns `<span class="badge">` markup in the JSON).
- Custom **AJAX form submit + validation-error injection** is hand-written per page
  (see the `updatePassword` script at the bottom of `layouts/app.blade.php`).
- **DataTables** power all listing/search/pagination.
- Charts via **ApexCharts / ECharts / Chart.js**, fed by API endpoints in `routes/api.php`.

This pattern (JSON controllers + jQuery DOM manipulation + Bootstrap modals) is *exactly* the
kind of code Livewire is designed to replace, which makes this a good migration candidate — but
it also means a lot of glue code has to be rebuilt as components.

## 4. Notable technical debt / risks (as-is)

1. **Abandoned LDAP package** — `adldap2/adldap2` is marked abandoned; it will not work cleanly on Laravel 10+. The AD auth is currently commented out but the dependency still blocks the upgrade.
2. **`minimum-stability: dev`** in `composer.json` — risky; should be `stable` after upgrade.
3. **HTML built in controllers** — status badges, etc. returned as strings in JSON. Not portable to Livewire and hard to test.
4. **No automated tests exercised** — `tests/` scaffolding exists but the domain logic is untested; risky for a large upgrade.
5. **Fat controllers** — business logic (file storage, status transitions, notifications) lives directly in controllers.
6. **Typo in class name** — `BackupContoller` (missing "r"). Cosmetic, but worth fixing during refactor.
7. **`Str` imported as global** (`use Str;`) instead of `Illuminate\Support\Str` — works via alias but fragile.
8. **Two parallel "laporan" view trees** — `resources/views/laporan/*` and `resources/views/layouts/laporan/*` appear duplicated.
9. **Mixed languages in code** (BM route names, EN model names) — fine, but document conventions before onboarding new devs.

## 5. What is *good* and should be preserved

- Clear feature-based folder organization.
- Reference tables are already normalized into `Models/Ref`.
- Role/permission model via spatie is modern and carries forward unchanged.
- Database notifications already in place.
- Route naming is consistent (`aduan.`, `user.`, `laporan.` prefixes).
