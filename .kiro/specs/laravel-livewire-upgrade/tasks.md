# Implementation Plan — Laravel + Livewire Upgrade

Execute top to bottom. Each framework/DB step is gated by the Phase 0 test suite passing.
Do not combine the framework upgrade with the Livewire rewrite.

- [x] 1. Establish the regression safety net
  - [x] 1.1 Create branch `upgrade/laravel-12`; take a full DB backup and a `storage/` uploads snapshot
    - Branch `upgrade/laravel-12` created off `master` (uncommitted changes carried over per option B).
    - Backups at `~/helpdesk-backups/pre-upgrade-20260828/`: `helpdesk-db.sql.gz` (21 tables, gzip-verified) + `storage-app-public.tar.gz` (160 files), both outside the repo.
    - _Requirements: 1.3, 1.4_
  - [x] 1.2 Write feature tests on the current Laravel 8 codebase for the critical paths
    - Login with custom `username` auth; create aduan; upload attachment; change report status; view a laporan; run a backup
    - Added `tests/FeatureTestCase.php` (seeds permissions/roles/report statuses), restored test scaffolding, fixed `UserFactory` to match real schema, and set `phpunit.xml` to in-memory SQLite so tests never touch prod.
    - _Requirements: 1.1_
  - [x] 1.3 Run the suite on Laravel 8 and confirm green baseline; stand up staging on target PHP + MySQL
    - Baseline green: 20 tests, 53 assertions passing on Laravel 8 / PHP 8.5.
    - Staging is infra to provision; setup + MySQL-8 pre-check documented in `sys-req/06-staging-setup.md`.
    - _Requirements: 1.2, 1.3_

- [x] 2. Modernize runtime and build tooling
  - [x] 2.1 Set `composer.json` `minimum-stability` to `stable` and pin PHP to `^8.3`
    - Done. `composer validate` passes; lock will be resynced during the per-major upgrade steps (task 3+), not now.
    - _Requirements: 2.1, 2.4_
  - [x] 2.2 Provision MySQL 8.0 (or MariaDB 10.11+) in staging with `utf8mb4`/`utf8mb4_unicode_ci`; point the app at it
    - Done in task 1.3: staging runs MySQL 8.0 with a full data copy; app serves against it (/ ->302, /login ->200).
    - _Requirements: 2.2_
  - [x] 2.3 Audit and fix aggregate `laporan`/dashboard queries for `ONLY_FULL_GROUP_BY`; re-run tests against MySQL 8
    - Tested the flagged laporan aggregates against staging MySQL 8 (ONLY_FULL_GROUP_BY active): all compliant, no rewrites needed. See `sys-req/06`. Re-verify after framework upgrade.
    - _Requirements: 2.3_
  - [x] 2.4 Remove `laravel-mix`; add Vite + `laravel-vite-plugin`; create `vite.config.js`; move JS entry
    - Done. package.json now uses Vite; `vite.config.js` added; JS/CSS entries converted to ESM; `webpack.mix.js` deleted; `npm run build` succeeds; `/public/build` git-ignored.
    - NOTE: `@vite()` is NOT added to the layout yet — the `@vite` Blade directive does not exist in Laravel 8. It will be wired in after the framework reaches 9.19+ (see task 7.1). The current UI is unaffected because it serves pre-built assets from `public/assets/`.
    - _Requirements: 2.4_

- [x] 3. Upgrade Laravel 8 → 9
  - [x] 3.1 Replace `fruitcake/laravel-cors` (built-in CORS) and `fideloper/proxy` (built-in TrustProxies); swap `facade/ignition` → `spatie/laravel-ignition`; apply Symfony Mailer changes
    - Removed fruitcake/fideloper (Kernel now uses `Illuminate\Http\Middleware\HandleCors`; TrustProxies extends the built-in middleware). facade/ignition -> spatie/laravel-ignition. Bumped spatie/permission ^5.5, spatie/laravel-backup ^8.1, fortify ^1.16, collision ^6, laravel-lang ~9.
    - _Requirements: 3.2_
  - [x] 3.2 Bump `laravel/framework` to 9, resolve config/skeleton diffs, run the test suite green
    - Now on Laravel 9.52.22. Fixed `config/auth.php` users provider driver `ldap` -> `eloquent` (leftover from adldap2) and removed adldap2 service providers from `config/app.php`. Baseline 20/20 green; verified on staging (login `Auth::attempt` PASS against real data, app serves).
    - _Requirements: 3.1_
    - NOTE: also pulled forward the LDAP replacement (task 4.2) here, because abandoned adldap2 hard-blocks Laravel 9. See 4.2.

- [x] 4. Upgrade Laravel 9 → 10
  - [x] 4.1 Enforce PHP 8.1+; bump `spatie/laravel-permission` → 6 and `spatie/laravel-backup`; add required type hints
    - spatie/laravel-permission ^6 (installed 6.25, existing config already v6-compatible: has column_names/model_morph_key), spatie/laravel-backup ^8.8, fortify ^1.19, laravel-lang ^12, collision ^7, phpunit ^10, spatie/laravel-ignition ^2. PHP already ^8.3. No type-hint changes needed for the skeleton files in use.
    - _Requirements: 3.2, 3.5_
  - [x] 4.2 Decide LDAP: replace `adldap2` with `directorytree/ldaprecord-laravel` and re-implement AD auth, OR remove it and document the decision
    - DONE EARLY (in task 3) — required to unblock Laravel 9. Decision: AD is genuinely used (report "bagi pihak" lookup), so replaced adldap2 with directorytree/ldaprecord-laravel ^3. Rewrote the AD lookup in `Api/UserController::show` to LdapRecord; migrated `config/ldap.php` to LdapRecord shape (same LDAP_* env vars); deleted obsolete `config/ldap_auth.php`. Verified LdapRecord classes resolve and adldap2 is gone. Real AD connectivity to test only from production (staging uses dummy LDAP values).
    - _Requirements: 3.3_
  - [x] 4.3 Bump `laravel/framework` to 10; run the test suite green
    - Now on Laravel 10.50.3. Migrated `phpunit.xml` to the PHPUnit 10 schema. Baseline 20/20 green. Verified on staging: app serves (/->302, /login->200), auth + spatie v6 permissions + LdapRecord all working on real data (2161 reports intact).
    - NOTE: one remaining PHP deprecation (`PDO::MYSQL_ATTR_SSL_CA`, config/database.php:62) only fires on PHP 8.5, not our 8.3 target; left as Laravel ships it since the 8.4+ replacement would break on 8.3.
    - _Requirements: 3.1_

- [x] 5. Upgrade Laravel 10 → 11
  - [x] 5.1 Keep classic `app/Http/Kernel.php` structure (skip optional slim skeleton); bump remaining packages (sail, collision, phpunit, faker, fortify)
    - Classic structure preserved (no bootstrap/app.php rewrite; existing Kernel.php works on L11). collision ^8.1, phpunit ^11, tinker ^2.9. Removed spatie/laravel-ignition (merged into framework error handling in L11; resolver confirmed removal).
    - _Requirements: 3.1, 3.2_
  - [x] 5.2 Bump `laravel/framework` to 11; run the test suite green
    - Now on Laravel 11.56.1. Baseline 20/20 green (phpunit.xml already L11-compatible). Verified on staging: /->302, /login->200; auth + spatie v6 permissions + LdapRecord working on real data (2161 reports, 318 users).
    - Only deprecation remains the PHP 8.5-only PDO::MYSQL_ATTR_SSL_CA (not our 8.3 target).
    - _Requirements: 3.1_

- [x] 6. Upgrade Laravel 11 → 12
  - [x] 6.1 Bump `laravel/framework` to 12; resolve any remaining diffs
    - Now on Laravel 12.68.0. Bumped spatie/laravel-backup ^8 -> ^9 (v8 caps at L11). No other diffs needed.
    - _Requirements: 3.1, 3.4_
  - [x] 6.2 Verify login/2FA/LDAP and spatie `can:` gates still work; run the full suite green on Laravel 12 with the existing Blade UI
    - Baseline 20/20 green. Staging verified on L12: /->login, /login 200, /dashboard gated; Auth::attempt PASS; spatie can('aduan.all') & can('manage.backup') = yes; LdapRecord + laravel-backup 9 resolve; data intact (2161 reports, 318 users). Existing Blade UI unchanged and serving.
    - MILESTONE: app now runs on Laravel 12 with the original Blade/jQuery UI. Framework upgrade phase complete.
    - _Requirements: 3.4, 3.5_

- [x] 7. Introduce Livewire alongside Blade
  - [x] 7.1 `composer require livewire/livewire` (^3); wire `@livewireStyles`/`@livewireScripts` via Vite into `layouts/app.blade.php`
    - livewire/livewire 3.8.6 installed. Added `@vite(...)` + `@livewireStyles` in `<head>`, `@livewireScripts` before `</body>`. Vite build connected (Laravel 12 has the `@vite` directive).
    - _Requirements: 4.1_
  - [x] 7.2 Build a throwaway "hello" component to confirm Livewire + Alpine + Bootstrap coexist
    - Built `HealthCheck` component; verified rendered HTML of a real authed page contains Livewire (wire:snapshot) + Alpine (x-data) + Vite /build + Bootstrap theme + jQuery all coexisting. Permanent smoke test kept at tests/Feature/Livewire/HealthCheckTest.php (wire:click reactivity 0->1->2). Throwaway route/wrapper removed.
    - _Requirements: 4.2_
  - [x] 7.3 Verify audit-trail and auth middleware behavior on Livewire XHR requests
    - Confirmed `livewire/update` runs through `web` only (not `audit-trail`). Decision documented in sys-req/06: page loads stay audited; per-interaction Livewire updates are intentionally NOT audited (avoids audit spam); audit specific actions explicitly when needed. Staging server switched to `php artisan serve` so Livewire/Vite/static assets all route correctly.
    - _Requirements: 4.3_

- [x] 8. Convert reference-CRUD screens (establish reusable patterns)
  - [x] 8.1 Extract Category business logic into an Action/Service; build `Category\CategoryIndex` + `Category\CategoryForm` with server-side validation and pagination; replace inline-HTML badges with `App\Support\StatusBadge`
    - Added `App\Actions\Categories\SaveCategory` (image handling), `App\Support\StatusBadge`, and `CategoryIndex` (search+pagination+show+deactivate/restore) + `CategoryForm` (create/edit, WithFileUploads, server-side validation). Pattern established for later screens.
    - _Requirements: 5.1, 5.2, 5.3, 5.4_
  - [x] 8.2 Mount the components in the existing Blade wrapper; add Livewire feature tests; remove dead JSON methods and jQuery for Category
    - `category/index.blade.php` is now a thin `@livewire('category.category-index')` wrapper. Trimmed ReportCategoryController to just index(); removed dead category CRUD routes + `_modal` partials. 9 Livewire tests added.
    - _Requirements: 5.7_
  - [x] 8.3 Repeat the established pattern for Status, Division, and Type
    - Status converted: `Status\StatusIndex` (edit-only, seeded reference data); wrapper + trimmed controller/routes; removed status `_modal` partials (incl. an orphan create modal that wrongly referenced category.store); 4 Livewire tests added.
    - Division & Type: NOT converted — they are dead code (no routes registered; the `divisions` table migration is commented out so the table doesn't exist; Type has no routes/views). Converting them would be converting unreachable code. Flagged for removal in task 12 (cleanup).
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.7_

- [ ] 9. Convert User and Role management
  - [ ] 9.1 Build `User\UserIndex`, `User\UserForm`, `User\UserAssetManager`; extract logic to Actions; add tests; remove dead controller/jQuery code
    - _Requirements: 5.1, 5.2, 5.3, 5.7_
  - [ ] 9.2 Build `Role\RoleIndex`/`Role\RoleForm`; confirm spatie permissions still enforce correctly
    - _Requirements: 5.1, 5.2, 5.7_

- [ ] 10. Convert the Aduan (core) flow
  - [ ] 10.1 Extract report logic into Actions (CreateReport, TransitionReportStatus, StoreAttachment)
    - _Requirements: 5.1_
  - [ ] 10.2 Build `Aduan\ReportIndex` (search/filter/sort/pagination) with `can:aduan.all` authorization
    - _Requirements: 5.3_
  - [ ] 10.3 Build `Aduan\ReportForm` with `WithFileUploads` attachments and server-side validation
    - _Requirements: 5.2, 5.5_
  - [ ] 10.4 Build `Aduan\ReportShow` + trail panel, status transitions, and feedback; preserve existing behavior
    - _Requirements: 5.4, 5.5_
  - [ ] 10.5 Add Livewire feature tests for the Aduan flow; remove dead JSON endpoints and jQuery
    - _Requirements: 5.7_

- [ ] 11. Convert notifications and (optionally) Laporan/dashboard
  - [ ] 11.1 Build `Shared\NotificationBell` (`wire:poll`) rendering unread database notifications with mark-as-read, no full reload
    - _Requirements: 5.6_
  - [ ] 11.2 (Optional) Convert Laporan/dashboard screens, or leave charts on the existing API + ApexCharts
    - _Requirements: 5.1, 5.3_

- [ ] 12. Cleanup and hardening
  - [ ] 12.1 Remove unused frontend libs (jQuery/DataTables/dropzone) no longer referenced; keep still-used chart libs
    - _Requirements: 6.1_
  - [ ] 12.2 Fix `BackupContoller` → `BackupController` and global `use Str;` → `use Illuminate\Support\Str;`; consolidate duplicated `laporan` view trees
    - _Requirements: 6.2, 6.3_
  - [ ] 12.3 Load-test Livewire screens with large datasets; tune `wire:model`/`wire:poll` as needed
    - _Requirements: 6.4_
  - [ ] 12.4 Update `README.md` with the new stack/setup; (optional) upgrade Laravel 12 → 13 as a follow-up
    - _Requirements: 6.5_
