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

- [ ] 3. Upgrade Laravel 8 → 9
  - [ ] 3.1 Replace `fruitcake/laravel-cors` (built-in CORS) and `fideloper/proxy` (built-in TrustProxies); swap `facade/ignition` → `spatie/laravel-ignition`; apply Symfony Mailer changes
    - _Requirements: 3.2_
  - [ ] 3.2 Bump `laravel/framework` to 9, resolve config/skeleton diffs, run the test suite green
    - _Requirements: 3.1_

- [ ] 4. Upgrade Laravel 9 → 10
  - [ ] 4.1 Enforce PHP 8.1+; bump `spatie/laravel-permission` → 6 and `spatie/laravel-backup`; add required type hints
    - _Requirements: 3.2, 3.5_
  - [ ] 4.2 Decide LDAP: replace `adldap2` with `directorytree/ldaprecord-laravel` and re-implement AD auth, OR remove it and document the decision
    - _Requirements: 3.3_
  - [ ] 4.3 Bump `laravel/framework` to 10; run the test suite green
    - _Requirements: 3.1_

- [ ] 5. Upgrade Laravel 10 → 11
  - [ ] 5.1 Keep classic `app/Http/Kernel.php` structure (skip optional slim skeleton); bump remaining packages (sail, collision, phpunit, faker, fortify)
    - _Requirements: 3.1, 3.2_
  - [ ] 5.2 Bump `laravel/framework` to 11; run the test suite green
    - _Requirements: 3.1_

- [ ] 6. Upgrade Laravel 11 → 12
  - [ ] 6.1 Bump `laravel/framework` to 12; resolve any remaining diffs
    - _Requirements: 3.1, 3.4_
  - [ ] 6.2 Verify login/2FA/LDAP and spatie `can:` gates still work; run the full suite green on Laravel 12 with the existing Blade UI
    - _Requirements: 3.4, 3.5_

- [ ] 7. Introduce Livewire alongside Blade
  - [ ] 7.1 `composer require livewire/livewire` (^3); wire `@livewireStyles`/`@livewireScripts` via Vite into `layouts/app.blade.php`
    - _Requirements: 4.1_
  - [ ] 7.2 Build a throwaway "hello" component to confirm Livewire + Alpine + Bootstrap coexist
    - _Requirements: 4.2_
  - [ ] 7.3 Verify audit-trail and auth middleware behavior on Livewire XHR requests
    - _Requirements: 4.3_

- [ ] 8. Convert reference-CRUD screens (establish reusable patterns)
  - [ ] 8.1 Extract Category business logic into an Action/Service; build `Category\CategoryIndex` + `Category\CategoryForm` with server-side validation and pagination; replace inline-HTML badges with `App\Support\StatusBadge`
    - _Requirements: 5.1, 5.2, 5.3, 5.4_
  - [ ] 8.2 Mount the components in the existing Blade wrapper; add Livewire feature tests; remove dead JSON methods and jQuery for Category
    - _Requirements: 5.7_
  - [ ] 8.3 Repeat the established pattern for Status, Division, and Type
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
