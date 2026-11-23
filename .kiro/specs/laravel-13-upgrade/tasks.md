# Implementation Plan: Laravel 13 Upgrade

## Overview

This plan upgrades the KKDW Helpdesk from Laravel 12.68 to Laravel 13 without changing behavior. It follows the design sequence exactly: baseline capture, PHP gate, composer dry-run, raise Laravel 12-compatible dependencies, three isolated commit points (backup `^10`, ldaprecord `^4`, framework `^13`), documentation, staging checklist, rollback rehearsal, then the production runbook and post-deploy smoke checks.

Conventions used in this plan:

- **[MANUAL - operator]** marks a task the coding agent cannot do (production host, staging deploy, real AD, provider contact). The agent writes the template and the exact commands. The operator runs them and records the result in the named file under `.kiro/specs/laravel-13-upgrade/`. These tasks are never optional.
- **[COMMIT POINT - maintainer]** marks a hand-over. The agent lists the changed files, prints the single-run test command `vendor/bin/phpunit --display-all-issues`, and then stops and waits for the maintainer's explicit confirmation that the suite passed. The agent never runs `git commit`, `git add` or `git push` (Requirements 8.10, 10.3). The maintainer commits, then says to continue.
- Full test suite command, single-run, non-watch: `vendor/bin/phpunit --display-all-issues`. "OK, but there were issues!" means new test code triggered a PHPUnit deprecation and must be fixed, not suppressed (Requirement 8.6).
- All paths for records are relative to `.kiro/specs/laravel-13-upgrade/` unless they start with `app/`, `tests/`, `config/` or similar.
- Test traps to respect in every test task (Requirements 8.7, 8.8, 8.9): follow the existing `Livewire::test` assertion style and extend `Tests\FeatureTestCase`; a `findOrFail` miss inside a component action throws `ModelNotFoundException` (use `expectException`, not `assertStatus(404)`); `assertDontSee()` sees the whole component render, so assert on composite strings; never use `assertDatabaseHas()` on a `date`-cast column (assert through the model); never call `Permission::create` or `Role::create` directly, use the repository's existing seeders through `FeatureTestCase`, because a second create raises `PermissionAlreadyExists` on a fresh install.
- The CSRF and cache tests need the Laravel 13 code, so they are written after the framework bump inside the framework phase. LDAP tests are written first against LdapRecord 3, then adapted when LdapRecord 4 is installed.

## Tasks

- [ ] 1. Capture the pre-upgrade baseline (Laravel 12, nothing changed yet)
  - [ ] 1.1 Create the baseline folder and record the Test_Suite baseline
    - Create `.kiro/specs/laravel-13-upgrade/baseline/`.
    - Run `vendor/bin/phpunit --display-all-issues` once on the current Laravel 12 codebase, covering the Unit and Feature testsuites.
    - Write `baseline/test-suite.md` with: tests executed, assertions, failures, errors, skipped, and the fully qualified name of every test that does not pass (write "none" if all pass). Also record the count of test files under `tests/` (expected 52).
    - Do not edit any test or config file in this task.
    - _Requirements: 8.1, 1.6_

  - [ ] 1.2 Capture route list, role names and permission names
    - Write `scripts/upgrade/capture-baseline.php` (a standalone script that boots the app) that writes `baseline/routes.txt`: URI, HTTP method and route name from `route:list --json`, one line per route and method, sorted.
    - In the same script write `baseline/roles.txt` and `baseline/permissions.txt`: sorted names. Read them from `RoleSeeder` and `PermissionSeeder` run once against a throwaway fresh SQLite database file, never by calling `Permission::create` or `Role::create`, so `PermissionAlreadyExists` cannot occur.
    - Run the script on the unchanged Laravel 12 codebase and keep the output files.
    - _Requirements: 1.3, 1.6_

  - [ ] 1.3 Capture the Bahasa Malaysia interface strings
    - Extend `scripts/upgrade/capture-baseline.php` (or add `scripts/upgrade/capture-ui-strings.php`) to render the login screen, aduan form, aduan show page and the validation messages through the test client, strip markup, and store the text in `baseline/ui-strings/` one file per screen. Also store a sorted listing and content hash of the `lang/` directory in `baseline/ui-strings/lang-files.txt`.
    - Use an in-memory SQLite database and the repository seeders. Do not touch the real database.
    - _Requirements: 1.3, 1.6, 3.7_

  - [ ] 1.4 [MANUAL - operator] Capture the database schema baseline from staging MySQL 8
    - Precondition: staging is running the current Laravel 12 build with `php artisan migrate` applied. Schema comes from MySQL, not from the SQLite test database.
    - Run: `docker compose -f docker-compose.staging.yml exec staging-mysql mysqldump -uroot -pstaging_root --no-data --skip-comments --skip-add-drop-table helpdesk_staging > .kiro/specs/laravel-13-upgrade/baseline/schema.sql`
    - Run: `docker compose -f docker-compose.staging.yml exec staging-mysql mysql -uroot -pstaging_root -N -e "SELECT table_name, column_name, column_type, is_nullable FROM information_schema.columns WHERE table_schema='helpdesk_staging' ORDER BY table_name, ordinal_position" > .kiro/specs/laravel-13-upgrade/baseline/columns.tsv`
    - Run: `docker compose -f docker-compose.staging.yml exec staging-mysql mysql -uroot -pstaging_root -N -e "SELECT table_name, index_name, non_unique, seq_in_index, column_name FROM information_schema.statistics WHERE table_schema='helpdesk_staging' ORDER BY table_name, index_name, seq_in_index" > .kiro/specs/laravel-13-upgrade/baseline/indexes.tsv`
    - Run: `docker compose -f docker-compose.staging.yml exec staging-mysql mysql -uroot -pstaging_root -N -e "SELECT table_name, constraint_name, column_name, referenced_table_name, referenced_column_name FROM information_schema.key_column_usage WHERE table_schema='helpdesk_staging' AND referenced_table_name IS NOT NULL ORDER BY table_name, constraint_name, ordinal_position" > .kiro/specs/laravel-13-upgrade/baseline/foreign-keys.tsv`
    - Record the date and operator in `baseline/README.md`. Tell the agent when the four files exist.
    - _Requirements: 1.4, 1.6_

  - [ ] 1.5 Write the baseline comparison test for routes, roles and permissions
    - Create `tests/Feature/Upgrade/BaselineComparisonTest.php` extending `Tests\FeatureTestCase`.
    - Test 1: the live route list (URI, method, name, sorted) equals `baseline/routes.txt`, with zero additions and zero removals. On failure, list the added and removed entries in the message.
    - Test 2: role names and permission names after the repository seeders run equal `baseline/roles.txt` and `baseline/permissions.txt` exactly. Use the seeders already invoked by `FeatureTestCase`; do not create permissions directly.
    - The test must pass on Laravel 12 now and is the guard for Requirements 1.3 and 1.8 after each later step.
    - _Requirements: 1.3, 1.8_

- [ ] 2. Checkpoint - Baseline recorded
  - Ensure all tests pass, ask the user if questions arise. Confirm `baseline/` holds test-suite, routes, roles, permissions, ui-strings and the four schema files before continuing.

- [ ] 3. PHP gate (no `composer.json` or `composer.lock` change until this phase passes)
  - [ ] 3.1 Create the PHP gate record template
    - Create `php-gate.md` with empty fields: request date (if any), check date, operator, exact CLI `php -v` output, exact web-handler version output and how it was obtained, parsed `major.minor.patch` for each, verdict (both >= 8.4.0 and < 9.0.0), and a contingency section (request date, 10-working-day date, 30-day date, decision a/b/c with decision date and maintainer).
    - Do not change any file other than `php-gate.md` in this task.
    - _Requirements: 2.1, 2.3, 2.4_

  - [ ] 3.2 [MANUAL - operator] Record production PHP for the CLI and for the web handler
    - On the production host, run: `php -v` (the CLI binary the deploy will use; if several exist, also run `which php` and `php -r 'echo PHP_BINARY, PHP_EOL;'`).
    - For the web handler, use one of: the hosting control panel's PHP version display for the site (record a screenshot path), or a temporary protected route or script returning only `PHP_VERSION` that is removed immediately after use. Record which method was used. Do not use `phpinfo()`.
    - Paste both outputs verbatim into `php-gate.md` with the parsed `major.minor.patch` and today's date. Tell the agent the result.
    - If both values are >= 8.4.0, set the verdict to pass. If either is below 8.4.0, or either is missing, the verdict is fail and task 3.3 applies. Until the verdict is pass, no later task may change a `composer.json` constraint or `composer.lock`.
    - _Requirements: 2.1, 2.2, 2.5_

  - [ ] 3.3 [MANUAL - operator] Contingency only: PHP below 8.4 or no answer (skip when 3.2 passed, but mark it as skipped in `php-gate.md`)
    - Send the PHP 8.4 upgrade request to the hosting provider in writing and record the request date in `php-gate.md`.
    - Record the date 10 working days after the request. If no version is obtained by then, record the absence of a response and treat the version as below 8.4.
    - If the provider declines in writing, or does not answer within 30 calendar days, the maintainer records exactly one decision with its date and name: (a) request PHP 8.4 (with request date), (b) defer with a calendar review date, or (c) keep `spatie/laravel-backup` on `^9` with a documented alternative backup arrangement naming the responsible party and backup frequency.
    - The agent proceeds past this phase only when 3.2 is recorded as pass.
    - _Requirements: 2.2, 2.3, 2.4, 6.6_

- [ ] 4. Checkpoint - PHP gate resolved
  - Ensure all tests pass, ask the user if questions arise. The agent asks the maintainer to confirm that `php-gate.md` shows pass for both CLI and web handler before any dependency work starts.

- [ ] 5. Composer dry-run (authoritative dependency plan)
  - [ ] 5.1 Run the dry-run in a scratch copy and record the output
    - Do not modify the project's `composer.json` or `composer.lock`. Copy both to a scratch directory (for example `/tmp/l13-dry-run/`), set there `php ^8.4`, `laravel/framework ^13.0`, `spatie/laravel-backup ^10.0`, `directorytree/ldaprecord-laravel ^4.0`, `laravel/tinker ^3.0`, `phpunit/phpunit ^12.0`, `fruitcake/laravel-debugbar` in place of `barryvdh/laravel-debugbar`, and `giorgiosironi/eris` in `require-dev`. Leave `nunomaduro/collision` loose so Composer picks the lowest release allowing PHPUnit 12.
    - Run `composer update --dry-run --no-interaction` there and save the complete output to `dry-run.md`.
    - Record the pre-upgrade versions from the project `composer.lock` for: `spatie/laravel-backup`, `directorytree/ldaprecord-laravel`, `laravel/tinker`, `phpunit/phpunit`, `nunomaduro/collision`, `barryvdh/laravel-debugbar`, `laravel-lang/lang`, `spatie/laravel-permission`, `livewire/livewire`, `laravel/fortify`.
    - If Packagist is unreachable, record that in `dry-run.md` together with the statement that all conclusions come from reading maintenance-branch `composer.json` files and are unverified, make no `laravel/framework` change, and stop here.
    - _Requirements: 3.1, 3.10_

  - [ ] 5.2 Record resolved versions and illuminate constraints for the packages that stay
    - In `dry-run.md`, record for `spatie/laravel-permission`, `livewire/livewire` and `laravel/fortify` the version resolved by the dry-run and the `illuminate/*` constraint that version declares, showing that it admits `^13`.
    - Record whether the resolved `laravel/fortify` release adds `laravel/passkeys`, and whether it needs any new migration or published config file.
    - Record the resolved `laravel-lang/lang` version, and whether `laravel-lang/lang ^12` resolves with `laravel/framework ^13.0`.
    - Record the resolved `nunomaduro/collision` version and the Eris version, with its PHPUnit constraint.
    - _Requirements: 3.4, 3.5, 3.7, 3.12_

  - [ ] 5.3 Reconcile the specification if the dry-run disagrees
    - Compare `dry-run.md` with Requirement 3 and the design constraint table. If there is an undocumented conflict, or a listed package resolves outside its stated constraint, update `requirements.md` first, then `design.md`, and tell the maintainer. Make no further `composer.json` or application change until that update exists.
    - If Eris does not support PHPUnit 12, record the fallback and ask the maintainer to choose another library or data-provider-driven tests before task 7.6.
    - If everything matches, write "no discrepancies" with the date in `dry-run.md`.
    - _Requirements: 3.2, 3.3_

- [ ] 6. Checkpoint - Dry-run recorded and consistent
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 7. Raise the Laravel 12-compatible dev dependencies (framework stays on `^12`)
  - [ ] 7.1 Update dev dependencies in `composer.json` and `composer.lock`
    - Raise `laravel/tinker` to `^3.0`, `phpunit/phpunit` to `^12.0`, and `nunomaduro/collision` to the lowest version recorded in `dry-run.md` that allows PHPUnit 12.
    - Replace `barryvdh/laravel-debugbar` with `fruitcake/laravel-debugbar` in `require-dev` only.
    - Add `giorgiosironi/eris` to `require-dev` (or the fallback chosen in 5.3).
    - Leave `php`, `laravel/framework`, backup and ldaprecord untouched in this task. Leave `spatie/laravel-permission ^6`, `livewire/livewire ^3` and `laravel/fortify ^1` as they are.
    - Run `composer update` for exactly those packages and read the result. If a package fails, restore it to its recorded pre-upgrade version and record the package and failure in `dry-run.md`.
    - _Requirements: 3.3, 3.6, 3.8, 3.11_

  - [ ] 7.2 Migrate `phpunit.xml` to the PHPUnit 12 schema
    - Run `vendor/bin/phpunit --migrate-configuration`, review the diff, and keep the existing `<source><include>`, `cacheDirectory` and `<server>`/`<env>` entries.
    - Run `vendor/bin/phpunit --version` and a configuration validation run until zero schema errors and zero schema deprecation warnings are reported.
    - _Requirements: 3.9_

  - [ ] 7.3 Fix PHPUnit 12 deprecations in repository code
    - Run `vendor/bin/phpunit --display-all-issues`. For every deprecation originating under `app/`, `tests/`, `database/` or `config/`, change that code (for example `@dataProvider` doc-comments become `#[DataProvider]` attributes). Do not add suppression or deprecation-ignoring settings to `phpunit.xml` or to tests.
    - For each deprecation originating under `vendor/`, record the notice and the owning package in `dry-run.md`.
    - If an existing `Livewire::test` assertion, Livewire exception-handling or date-column assertion no longer passes, apply the smallest change limited to the affected test methods and list the changed pattern and the reason for the maintainer's attention at the commit point.
    - Do not delete, empty or skip any test.
    - _Requirements: 8.4, 8.5, 8.6, 8.8, 8.9_

  - [ ] 7.4 Write the Bahasa Malaysia validation message test (`laravel-lang/lang`)
    - Create `tests/Feature/Upgrade/ValidationMessagesTest.php`. Submit the login form with empty required fields and the aduan form with empty required fields, and assert the Bahasa Malaysia messages that `baseline/ui-strings/` shows for those screens, using composite message strings rather than short words that also appear elsewhere in the render.
    - Add a test that the login screen shows the same input fields and Bahasa Malaysia text as the baseline screen capture (the field-set part of Requirement 3.5).
    - _Requirements: 3.5, 3.7_

  - [ ] 7.5 Write example tests for the Wakil BP field mapping (P1 safety net)
    - Create `tests/Feature/Aduan/WakilBpMappingTest.php` using `Livewire::test(ReportForm::class)` with an authenticated submitter, copying an existing test in `tests/Feature/Livewire` or `tests/Feature/Aduan` as the style template. Use a local `User` match for the represented person so no LDAP call is made.
    - Cases: `is_bp` set stores the represented person's name, email and telephone as the complainant and the submitter as the Wakil; `is_bp` unset stores the submitter's own values and no Wakil; the show screen, the aduan listing and the notification e-mail content present the stored complainant identity with the submitter identified separately as the Wakil. Assert no swap in either direction.
    - Read the memory-recorded inversion trap (the `bp_*` fields) in `ReportForm` before writing expectations, and do not assert any date column through `assertDatabaseHas`.
    - _Requirements: 7.5, 7.9_

  - [ ]* 7.6 Write property test for the Wakil BP field mapping
    - **Property 1: Wakil BP field mapping is an invariant**
    - Create `tests/Feature/Aduan/WakilBpMappingPropertyTest.php` using Eris with at least 100 iterations, generating `is_bp` and random name, email and telephone for the represented person and the submitter. Tag it `Feature: laravel-13-upgrade, Property 1: Wakil BP field mapping is an invariant`.
    - **Validates: Requirements 7.5, 7.9**

  - [ ] 7.7 [COMMIT POINT - maintainer] Dev-dependency raise (preparatory commit, not one of the three isolated commits)
    - Hand over: the list of changed files (`composer.json`, `composer.lock`, `phpunit.xml`, new tests, any deprecation fixes) and the single-run command `vendor/bin/phpunit --display-all-issues`.
    - Suggested commit message: `Raise L12-compatible dev dependencies (tinker 3, phpunit 12, collision, fruitcake debugbar, eris)`.
    - Do not commit. Stop and wait for the maintainer to confirm that the suite passed with zero failures and zero errors, and that the test count is at or above the baseline in `baseline/test-suite.md`. Do not start task 9 until that confirmation arrives. If the maintainer reports a failure, restore the failing package to its recorded version and record the failing test names in `dry-run.md`.
    - _Requirements: 8.2, 8.3, 8.10, 3.11_

- [ ] 8. Checkpoint - Dev dependencies green
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 9. Commit point 1: `spatie/laravel-backup ^10`
  - [ ] 9.1 Raise `spatie/laravel-backup` and the `php` constraint
    - Set `php` to `^8.4` and `spatie/laravel-backup` to `^10.0` in `composer.json`, run `composer update spatie/laravel-backup` and keep the `composer.lock` changes limited to what that resolve requires. Do not touch `laravel/framework`.
    - Confirm the resolved `spatie/laravel-backup` version is in 10.x and note it in `dry-run.md`.
    - _Requirements: 2.5, 6.5, 3.3_

  - [ ] 9.2 Diff and adjust `config/backup.php`
    - Publish the v10 config to a temporary path (`php artisan vendor:publish --tag=backup-config --force` is not allowed in place; copy the file from `vendor/spatie/laravel-backup/config/backup.php` instead) and diff it against `config/backup.php`.
    - Write `backup-config-diff.md` listing every added, removed or changed option, with "keep current" or "adopt v10 default" and a reason. Apply the required changes to `config/backup.php`.
    - Confirm the sources still cover the MySQL 8 database (`source.databases`) and `storage/` (`source.files.include`), and that the destination still writes under `storage/app/<slug(app.name)>` so `BackupController` keeps listing archives.
    - Check whether v10 publishes a migration. If it does, record it in `backup-config-diff.md` under the dependency name.
    - _Requirements: 6.1, 1.4_

  - [ ] 9.3 Update the PHP prerequisite text and the staging PHP image
    - `DEPLOYMENT.md`: replace "PHP 8.3+" in the server prerequisites with a minimum of PHP 8.4.
    - `docker/staging/Dockerfile`: change `FROM php:8.3-cli` to `php:8.4-cli` and update its header comment. `docker-compose.staging.yml`: change the image tag `helpdesk-staging/app:php8.3` to `php8.4` and the header comment.
    - These changes belong to the same commit as the `php` constraint because staging must report the same PHP major.minor as production before the Laravel 13 deploy.
    - _Requirements: 2.6, 2.7_

  - [ ] 9.4 Extend the backup feature tests
    - Extend `tests/Feature/Backup/BackupTest.php` (keep existing tests unchanged): a user holding `manage.backup` opens the backup screen and sees the archive list with the same columns as before; a user without the permission receives the same access-denied response and no archive is created when requesting the screen or triggering a backup run.
    - Fake the archive files with the existing test style (a temp storage directory), not a real `backup:run`.
    - Authorization failures are assertable with `assertForbidden()`; do not assert 404 for missing models.
    - _Requirements: 6.2, 6.9_

  - [ ] 9.5 [COMMIT POINT - maintainer] Commit 1: backup v10
    - Hand over the changed files: `composer.json`, `composer.lock`, `config/backup.php`, `DEPLOYMENT.md`, `docker/staging/Dockerfile`, `docker-compose.staging.yml`, `tests/Feature/Backup/BackupTest.php`, `backup-config-diff.md`. The commit must contain no `laravel/framework` change and nothing unrelated.
    - Single-run command: `vendor/bin/phpunit --display-all-issues`.
    - Suggested commit message: `Upgrade spatie/laravel-backup to ^10, require PHP ^8.4`.
    - Do not commit. Stop and wait for the maintainer to confirm the suite passed (zero failures and zero errors beyond baseline). If it failed, restore `spatie/laravel-backup` to 9.4.1 in `composer.lock`, record the package and failing test names, and raise nothing further.
    - _Requirements: 6.5, 8.2, 8.10, 10.3, 3.11_

  - [ ] 9.6 [MANUAL - operator] Backup and restore round trip on staging
    - Rebuild and start staging on PHP 8.4: `docker compose -f docker-compose.staging.yml up -d --build`. Confirm: `docker compose -f docker-compose.staging.yml exec staging-app php -v` shows 8.4.x, and that its major.minor equals the production value in `php-gate.md`. If it does not, block the Laravel 13 staging deploy or record the accepted difference, the rationale and the maintainer.
    - Run: `time docker compose -f docker-compose.staging.yml exec staging-app php artisan backup:run`. Record the elapsed time, the single archive's file name and size (`ls -l storage/app/*/`), and any error. If no archive appears within 60 minutes, or the run errors, record the failing step, keep `spatie/laravel-backup` at 9.4.1 and block production.
    - Inspect the archive: it must hold one dump covering every table (`unzip -l <archive>` then compare the table list with `SHOW TABLES`) and every file under `storage/` present at the start of the run.
    - Restore into an empty MySQL 8 database on staging and boot the app against it. Confirm: the login screen is served; a user that existed in the source database authenticates; the aduan total and the set of aduan reference numbers equal the source (compare `SELECT COUNT(*)` and a sorted reference list); at least 5 aduan with attachments (or all if fewer) open and download their attachment with no missing-file error.
    - Record everything in `backup-restore.md` with date, operator and verdicts. Tell the agent the result.
    - _Requirements: 6.3, 6.4, 6.7, 6.8, 2.7, 2.8_

- [ ] 10. Checkpoint - Backup commit confirmed
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 11. Commit point 2: `directorytree/ldaprecord-laravel ^4`
  - [ ] 11.1 Write LDAP example tests against the directory emulator (on LdapRecord 3)
    - Create `tests/Feature/Aduan/LdapLookupTest.php` (report-form call site, `Livewire::test(ReportForm::class)`) and extend `tests/Feature/Api/GetUserLookupTest.php` (API call site). Use `LdapRecord\Laravel\Testing\DirectoryEmulator::setup()` with fake entries, or stubs if the emulator cannot express a case. Extend existing tests instead of duplicating them.
    - Cases at each call site: a `samaccountname` matching exactly one entry returns the single string for `cn`, `physicaldeliveryofficename`, `userprincipalname` (plus `telephonenumber` and `mobile` at the report-form site); empty `telephonenumber` falls back to `mobile`; no match returns null, shows the "Pengguna tidak dijumpai" message and leaves the Wakil BP fields empty; an emulated connection or bind failure shows the "Tidak dapat connect ke AD. Sila hubungi admin" message, makes a single attempt and keeps the values already entered in the form, and a non-Wakil aduan still submits; empty or different `physicaldeliveryofficename` without the represent-any-bahagian permission shows "Anda hanya boleh mewakili pengguna dari bahagian anda sahaja" and leaves fields empty; with that permission the lookup succeeds.
    - For the connection failure case, throw the exception type LdapRecord 4 is expected to throw from the stub, and add one case that throws a plain `\Error` to cover the `Exception` versus `\Throwable` catch difference noted in the design.
    - A `findOrFail` miss in a component action must be asserted with `expectException`, not a status assertion.
    - _Requirements: 7.2, 7.3, 7.4, 7.8_

  - [ ]* 11.2 Write property test for the LDAP lookup
    - **Property 2: LDAP lookup never raises and maps attributes faithfully**
    - Create `tests/Feature/Api/LdapLookupPropertyTest.php` using Eris, at least 100 iterations, generating arbitrary `samaccountname` strings including empty, over-length and strings containing `*`, `(`, `)`, `\` and NUL, and random attribute presence. Assert a resolved attribute set or a null result with the right message, never an unhandled exception. Tag it `Feature: laravel-13-upgrade, Property 2: LDAP lookup never raises and maps attributes faithfully`.
    - **Validates: Requirements 7.2, 7.3, 7.4, 7.8**

  - [ ] 11.3 Raise `directorytree/ldaprecord-laravel` to `^4.0` and adapt the call sites
    - Set `directorytree/ldaprecord-laravel` to `^4.0` in `composer.json`, run `composer update directorytree/ldaprecord-laravel directorytree/ldaprecord`, and keep `laravel/framework` on `^12`.
    - Update `app/Livewire/Aduan/ReportForm.php` and `app/Http/Controllers/Api/UserController.php` only where `query()`, `select()`, `findBy('samaccountname', ...)` or `getFirstAttribute()` changed in v4. Do not touch `bahagianAllowed()` or `canRepresentAnyBahagian()` and do not change the field mapping.
    - If v4 throws a non-`Exception` `Error` for an unreachable server, widen the `UserController` catch clause to `\Throwable`. Do this only if the review shows it is needed.
    - Adapt the tests from 11.1 to the LdapRecord 4 emulator API, keeping the same cases.
    - _Requirements: 7.1, 7.2, 7.3, 7.4_

  - [ ] 11.4 Record the LdapRecord API review and timeout check
    - Write `ldap-review.md` listing every changed method signature, renamed method and changed return type for `query()`, `select()`, `findBy()` and `getFirstAttribute()`, or "no change" for each, plus the exception type thrown for an unreachable server.
    - Check `config/ldap.php` for the connection timeout and retry options under v4. Record the option name and confirm the 10-second limit and no retry. Any change is made in configuration only, not in application code.
    - Record whether LdapRecord 3 on Laravel 13 is allowed by the dry-run (this decides whether a partial revert is possible, for the runbook).
    - _Requirements: 7.1, 7.4_

  - [ ] 11.5 [COMMIT POINT - maintainer] Commit 2: LdapRecord v4
    - Hand over the changed files: `composer.json`, `composer.lock`, the two call-site files if edited, `config/ldap.php` if edited, the LDAP tests and `ldap-review.md`. The commit contains no unrelated change and no framework change.
    - Single-run command: `vendor/bin/phpunit --display-all-issues`.
    - Suggested commit message: `Upgrade directorytree/ldaprecord-laravel to ^4`.
    - Do not commit. Stop and wait for the maintainer's confirmation that the suite passed. If it failed, restore the package to 3.4.3 and record the failing tests.
    - _Requirements: 7.6, 8.2, 8.10, 10.3, 3.11_

- [ ] 12. Checkpoint - LdapRecord commit confirmed
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 13. Commit point 3: `laravel/framework ^13`
  - [ ] 13.1 Record the Laravel 12 resolved prefixes before the bump
    - While the framework is still 12.x, write `scripts/upgrade/resolve-prefixes.php` that, for each of `.env.example`, `.env.server` and `.env.staging`, loads that file's `APP_NAME` and prints the Laravel 12 values `Str::slug(APP_NAME, '_').'_session'`, `'_cache'` and `'_database_'`.
    - Save the output to `prefix-baseline.md` (expected: `laravel_*` for the first two files and `sistem_meja_bantuan_kkdw_staging_*` for staging).
    - _Requirements: 5.1, 5.2, 5.7_

  - [ ] 13.2 Pin the prefixes in the three env files and add the session serialization key
    - Add `SESSION_COOKIE`, `CACHE_PREFIX` and `REDIS_PREFIX` to each of `.env.example`, `.env.server` and `.env.staging`, using exactly the values in `prefix-baseline.md` for that file. Do not change any other variable. Do not edit `.env`.
    - In `config/session.php` add `'serialization' => 'php'` and leave the cookie-name logic unchanged.
    - _Requirements: 5.1, 5.3_

  - [ ] 13.3 Raise `laravel/framework` to `^13.0`
    - Precondition: the previous checkpoints reported zero failures and zero errors on the raised dependencies. Set `laravel/framework` to `^13.0` in `composer.json` and run `composer update laravel/framework` plus any packages the resolve requires. Confirm the locked version is `>= 13.0.0` and `< 14.0.0`.
    - Resolve any other required `config/` and skeleton-file changes that the Laravel 13 upgrade guide lists and that are not covered by tasks 13.4 and 13.5, and record them in `dependency-changes.md`. Retain `app/Http/Kernel.php`, `app/Console/Kernel.php`, `app/Exceptions/Handler.php` and the `config/app.php` provider list; there is no slim-skeleton migration.
    - _Requirements: 1.1, 1.2, 1.7_

  - [ ] 13.4 Rename the CSRF middleware
    - Confirm from the installed Laravel 13 source the exact `Illuminate\Foundation\Http\Middleware\PreventRequestForgery` class name, its `$except` property and its origin-verification hooks, and note them in `dependency-changes.md`.
    - Replace `app/Http/Middleware/VerifyCsrfToken.php` with `app/Http/Middleware/PreventRequestForgery.php` in the same namespace, extending the framework class, with an empty `$except` list. Swap the single `web` group entry in `app/Http/Kernel.php`, position unchanged.
    - Search `app/`, `config/`, `routes/`, `tests/` and `bootstrap/` and remove every remaining `VerifyCsrfToken` reference.
    - _Requirements: 4.1, 4.2, 1.2_

  - [ ] 13.5 Set `serializable_classes` in `config/cache.php`
    - Confirm the option's semantics against the Laravel 13 `config/cache.php` skeleton in `vendor/` and set the value that permits the `login_stats` array and no objects (design expectation: `false`).
    - Add a comment above the key stating that the only cache write is `Cache::remember('login_stats', 600, ...)` at `app/Providers/FortifyServiceProvider.php:53`, an array of four integers with no PHP objects.
    - _Requirements: 5.5_

  - [ ] 13.6 Write the CSRF origin behavior feature tests
    - Create `tests/Feature/Security/RequestForgeryOriginTest.php` against one authenticated state-changing `web` route (pick an existing one, for example a POST route covered by an existing feature test, and copy its setup).
    - The framework skips CSRF checks when running under the testing environment. Bypass that skip for the test duration (confirm the mechanism in the installed `PreventRequestForgery` source, for example running the request under a non-testing environment name) and document the mechanism in a comment in the test.
    - Cases: (1) valid token plus `Sec-Fetch-Site: same-origin` reaches the handler and is not 419; (2) `Sec-Fetch-Site: cross-site` returns 419, changes no data, and writes a log entry containing the requested path (assert on the log through `Log::spy()` or the log driver already used by the tests); (3a) no `Sec-Fetch-Site` header with a valid token passes; (3b) no header with an invalid token returns 419.
    - Add one assertion that the `$except` list of `App\Http\Middleware\PreventRequestForgery` is empty.
    - _Requirements: 4.3, 4.4, 4.5, 4.2, 4.8_

  - [ ] 13.7 Write the `login_stats` cache tests
    - Create `tests/Feature/Upgrade/LoginStatsCacheTest.php` with the file cache store.
    - Test 1: a pre-seeded serialized array in the same shape as the Laravel 12 payload (`total`, `resolved`, `rate`, `bahagian`) is read back by `Cache::get('login_stats')` with identical contents and no unserialization error while `serializable_classes` is set.
    - Test 2: a corrupted or unreadable `login_stats` entry is treated as a miss, recomputed through the code path at `FortifyServiceProvider.php:53`, stored with a 600-second time-to-live, and the login page renders without error.
    - Test 3: `config('cache.serializable_classes')` permits no object classes.
    - _Requirements: 5.5, 5.6, 5.8_

  - [ ] 13.8 Write the prefix stability example test
    - Create `tests/Unit/Upgrade/CachePrefixStabilityTest.php` that loads each of the three env files, resolves the session cookie, cache prefix and Redis prefix through the Laravel 13 config logic, and asserts they equal `prefix-baseline.md` character for character (read the values from that file) and contain none of `-session`, `-cache-` or `-database-`.
    - On mismatch, the failure message names which of the three settings differs and shows both the Laravel 12 value and the Laravel 13 value.
    - _Requirements: 5.1, 5.2, 5.7_

  - [ ]* 13.9 Write property test for cache key prefix stability
    - **Property 3: Cache key prefixing is stable**
    - Create `tests/Unit/Upgrade/CachePrefixPropertyTest.php` using Eris, at least 100 iterations over arbitrary key strings. Assert the fully qualified key under the pinned `CACHE_PREFIX` equals the key built from the Laravel 12 formula `Str::slug(APP_NAME, '_').'_cache'` for the same input, and that it contains no hyphenated form. Tag it `Feature: laravel-13-upgrade, Property 3: Cache key prefixing is stable`.
    - **Validates: Requirements 5.1, 5.2**

  - [ ] 13.10 Extend the smoke test for framework version and debug-off boot
    - Extend `tests/Feature/SmokeTest.php` (keep existing methods): the installed framework version starts with `13.`, the app boots with `APP_DEBUG=false` and the login page returns 200, with `fruitcake/laravel-debugbar` present only as a dev dependency.
    - _Requirements: 1.7, 3.6_

  - [ ] 13.11 Run the baseline comparisons and record the outcome
    - Run `BaselineComparisonTest` and the baseline capture script again on Laravel 13, then diff the new route, role, permission and UI-string captures against `baseline/` (write the new captures to `after/` and diff with `diff -r baseline after`). Record the result in `baseline-comparison.md`.
    - If any difference exists, revert it, or record it with the dependency that requires it and ask the maintainer to accept it. The upgrade remains incomplete until each difference is reverted or accepted.
    - Run `vendor/bin/phpunit --display-all-issues` and confirm zero failures, zero errors, test count at or above baseline, zero deprecations from repository code, and no test file removed (still 52 or more).
    - The schema comparison on MySQL is a manual task in the staging phase (16.6).
    - _Requirements: 1.3, 1.4, 1.8, 8.4, 8.5, 8.6_

  - [ ] 13.12 [COMMIT POINT - maintainer] Commit 3: Laravel 13
    - Hand over the changed files: `composer.json`, `composer.lock`, `app/Http/Middleware/PreventRequestForgery.php` (and the deleted `VerifyCsrfToken.php`), `app/Http/Kernel.php`, `config/session.php`, `config/cache.php`, the three env files, the new tests, `prefix-baseline.md`, `baseline-comparison.md`, `scripts/upgrade/`. The commit contains no backup or LdapRecord constraint change.
    - Single-run command: `vendor/bin/phpunit --display-all-issues`.
    - Suggested commit message: `Upgrade laravel/framework to ^13.0, rename CSRF middleware, pin session and cache prefixes`.
    - Do not commit. Stop and wait for the maintainer to confirm that the suite passed and that the CSRF tests pass.
    - _Requirements: 1.1, 8.2, 8.4, 8.10, 10.3_

- [ ] 14. Checkpoint - Framework commit confirmed
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 15. Documentation
  - [ ] 15.1 Update `README.md`
    - Update the stack summary (about line 9), the local requirements (about line 34), the staging section (about line 48) and the testing section so that the Laravel, PHP, Livewire and PHPUnit versions equal the `composer.lock` versions to major.minor (Laravel 13.x, PHP 8.4, Livewire 3.x, PHPUnit 12.x). No reference to Laravel 12, PHPUnit 11 or PHP 8.3 may remain in any section.
    - _Requirements: 11.1_

  - [ ] 15.2 Update `DEPLOYMENT.md`
    - State the PHP requirement as the version in `php-gate.md`, name the resulting Laravel and Livewire versions, keep MySQL 8.0, and remove any "PHP 8.3+".
    - Add the three pinned variables `SESSION_COOKIE`, `CACHE_PREFIX` and `REDIS_PREFIX` with the instruction to add them to the production `.env` computed from its own `APP_NAME` using the Laravel 12 formula, and any deploy command whose text or order changed.
    - Add the ordered rollback procedure from the design (maintenance mode, `git checkout <laravel-12-commit-hash>`, `composer install --no-dev --optimize-autoloader` from the Laravel 12 lock, host PHP revert only if needed, `php artisan migrate:rollback --step=<n>` or database dump restore for any dependency-supplied migration, `php artisan optimize:clear` then `config:cache` and `route:cache`, `php artisan up`, verify login and the aduan count), the statement that no session or cache flush is needed in either direction, and which partial reverts the dry-run confirmed.
    - Add the post-deploy LDAP smoke check: look up one known `samaccountname` and confirm the returned name, bahagian, email and telephone are non-empty and belong to that person, with the note that staging uses dummy LDAP values so real AD is confirmed only in production.
    - _Requirements: 11.2, 10.1, 10.5, 7.7, 2.6_

  - [ ] 15.3 Write the spec-folder records
    - Create `dependency-changes.md`: every package whose version differs between the pre-upgrade and post-upgrade `composer.lock`, with name, old version, new version and reason (required for Laravel 13 compatibility, pulled in transitively, or replaced package). Record any dependency-published migration against the dependency name, or "none".
    - Create `verified-not-applicable.md` listing the already-verified items: `upsert`, `JobAttempted`/`exceptionOccurred`, the `QueueBusy` `$connection` property, pagination view names, `Str` UUID/ULID/random factories in tests, custom `array_first`/`array_last` helpers, morph pivot and custom pivot classes, custom cache/queue/dispatcher/response-factory driver implementations, and `Container::call` nullable class defaults, each with the reason and marked as not requiring re-investigation.
    - Create `open-decisions.md` with every open item or decision by exception (the `laravel-lang/lang` outcome, any origin-verification mitigation, any alternative backup arrangement), each with the decision and the condition for revisiting. Record the next review deadline: Laravel 13 security-support end date 17 Mar 2028.
    - _Requirements: 11.3, 11.4, 11.6, 1.5, 1.4_

  - [ ] 15.4 Write the documentation version consistency test
    - Create `tests/Unit/Upgrade/DocsVersionConsistencyTest.php` that reads `composer.lock` and asserts `README.md` and `DEPLOYMENT.md` contain the locked Laravel and PHPUnit major.minor, `README.md` contains none of "Laravel 12", "PHPUnit 11" and "PHP 8.3", and `DEPLOYMENT.md` does not contain "PHP 8.3+".
    - _Requirements: 11.1, 11.2, 11.5_

  - [ ] 15.5 [COMMIT POINT - maintainer] Commit 4: documentation and records
    - Hand over `README.md`, `DEPLOYMENT.md`, the spec-folder records and the new test, with the single-run command `vendor/bin/phpunit --display-all-issues`. Suggested message: `Document Laravel 13 upgrade and rollback procedure`.
    - Do not commit. Stop and wait for the maintainer's confirmation that the suite passed.
    - _Requirements: 8.10, 11.1, 11.2_

- [ ] 16. Checkpoint - Documentation confirmed
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 17. Staging verification
  - [ ] 17.1 Create the staging checklist template
    - Create `staging-checklist.md` with fields for commit hash, deploy date and tester, a table of the nine items (a) login via the custom `username` Fortify flow, (b) aduan creation with at least one attachment within the configured size limit, (c) a status transition on an existing aduan, (d) feedback submission, (e) notification bell unread count, (f) each laporan chart screen, (g) a backup run, (h) the Wakil BP lookup, (i) logout, each with verdict, date, tester and observed result; a table for the five CSRF flows (login, aduan creation, attachment upload, status transition, feedback) with a 419 count and whether `Sec-Fetch-Site` was present; and sections for session survival, `login_stats`, Vite and Livewire assets, queue dispatch, data size, and schema comparison.
    - Pass criteria to print at the top: each item completes in 10 seconds or less, zero JavaScript console errors, zero failed asset or data requests, zero unhandled-exception entries in the application log.
    - _Requirements: 9.2, 9.3, 4.6_

  - [ ] 17.2 [MANUAL - operator] Pre-deploy checks on the Laravel 12 staging build
    - Confirm the data size precondition: `docker compose -f docker-compose.staging.yml exec staging-mysql mysql -uroot -pstaging_root helpdesk_staging -e "SELECT (SELECT COUNT(*) FROM reports) AS aduan, (SELECT COUNT(*) FROM users) AS users"`. Replace the table names with the real ones if they differ. If aduan < 2100 or users < 300, record a failed checklist verdict and restore the production data copy with `scripts/staging-refresh-from-dump.sh` before continuing.
    - While staging still runs the Laravel 12 build, log in through the browser and save the session cookie value in a local file outside the repository. Open the dashboard once so `login_stats` is cached, and note the time (it expires after 600 seconds, so the Laravel 13 read in 17.3 must happen within 10 minutes of the deploy, or the Laravel 12 entry must be recreated just before deploy).
    - Record the data counts, the cookie capture and the time in `staging-checklist.md`.
    - _Requirements: 9.7, 5.4, 5.6_

  - [ ] 17.3 [MANUAL - operator] Deploy the verified commit to staging and check session and cache survival
    - Check out the exact commit that passed the suite (record `git rev-parse HEAD` in `staging-checklist.md`) and deploy it: `docker compose -f docker-compose.staging.yml up -d --build`, then `docker compose -f docker-compose.staging.yml exec staging-app composer install --no-dev --optimize-autoloader`, `docker compose -f docker-compose.staging.yml exec staging-app php artisan migrate --force`, `docker compose -f docker-compose.staging.yml exec staging-app php artisan optimize:clear`.
    - Confirm: `docker compose -f docker-compose.staging.yml exec staging-app php -v` (major.minor equals `php-gate.md`) and `docker compose -f docker-compose.staging.yml exec staging-app php artisan --version` shows Laravel 13.x.
    - Request an authenticated page with the saved Laravel 12 cookie (browser cookie replace, or `curl -b "<cookie-name>=<value>" -i http://localhost:8088/dashboard`) and confirm the page is served as the same user with no redirect to login. Open the dashboard and confirm it renders without error while the Laravel 12 `login_stats` entry is still within its 600 seconds.
    - Record the results. `migrate` must report nothing to migrate unless a dependency migration was recorded.
    - _Requirements: 5.4, 5.6, 2.7, 1.7_

  - [ ] 17.4 [MANUAL - operator] Run the five CSRF flows through the production-equivalent proxy path
    - Through the same proxy path as production (not the direct `localhost:8088` port if production sits behind a proxy), execute once each: login, aduan creation, attachment upload, status transition, feedback submission. Watch the browser network tab and `docker compose -f docker-compose.staging.yml exec staging-app tail -n 100 storage/logs/laravel.log`.
    - Record for each flow whether it returned 419, and whether the requests carried the `Sec-Fetch-Site` header (and its value). All five must show zero 419.
    - If any flow returns 419, apply a mitigation that restores all five, preferring trusted-origin configuration over disabling origin verification, and record the mitigation, why trusted-origin configuration was or was not usable, and the residual risk in `open-decisions.md`. Tell the agent so it can make any needed code or config change as a new task before re-running.
    - _Requirements: 4.6, 4.7_

  - [ ] 17.5 [MANUAL - operator] Execute the nine-item checklist, assets and queue checks
    - Execute items (a) to (i) from `staging-checklist.md` and record verdict, date, tester and observed result for each. Item (g): `docker compose -f docker-compose.staging.yml exec staging-app php artisan backup:run` and confirm one new archive. Item (h): the Wakil BP lookup shows the dummy-LDAP error message path, since staging has no real AD.
    - Assets: confirm every `@vite`, `@livewireStyles` and `@livewireScripts` reference loads (network tab, no 404), the page has its stylesheet applied, and Livewire actions respond on the exercised screens.
    - Queue: trigger an aduan notification e-mail, confirm a row is enqueued on the `database` connection, run `docker compose -f docker-compose.staging.yml exec staging-app php artisan queue:work --stop-when-empty`, confirm dispatch within 60 seconds and `docker compose -f docker-compose.staging.yml exec staging-app php artisan queue:failed` lists zero entries.
    - If any item fails, production is blocked. Re-run the failed item and every item on the same screen on a rebuilt staging deploy before the block is lifted.
    - _Requirements: 9.2, 9.3, 9.4, 9.5, 9.6, 6.2_

  - [ ] 17.6 [MANUAL - operator] Compare the staging MySQL schema with the baseline
    - Re-run the four commands of task 1.4 writing to `after/` (`schema.sql`, `columns.tsv`, `indexes.tsv`, `foreign-keys.tsv`), then run `diff -r .kiro/specs/laravel-13-upgrade/baseline .kiro/specs/laravel-13-upgrade/after` limited to those four files.
    - Expected: no difference. Record the result in `baseline-comparison.md`. Any difference is reverted, or recorded against the dependency that published the migration and accepted by the maintainer.
    - _Requirements: 1.4, 1.8_

  - [ ] 17.7 [MANUAL - operator] Sign off the staging run
    - Confirm that all nine items, the five CSRF flows, the session and cache survival, the data-size precondition, the backup round trip (9.6) and the schema comparison are recorded as passed. Record the exact verified commit identifier in `staging-checklist.md`. Production must deploy this same identifier.
    - _Requirements: 9.1, 9.8_

- [ ] 18. Checkpoint - Staging passed
  - Ensure all tests pass, ask the user if questions arise. The agent asks the maintainer to confirm that `staging-checklist.md` shows every item passed before the rollback rehearsal.

- [ ] 19. Rollback rehearsal on staging
  - [ ] 19.1 [MANUAL - operator] Rehearse the rollback and time it
    - Start a stopwatch. Follow the rollback list in `DEPLOYMENT.md` on staging, with the Laravel 12 commit hash recorded in `staging-checklist.md`: `docker compose -f docker-compose.staging.yml exec staging-app php artisan down`, `git checkout <laravel-12-commit-hash>`, `docker compose -f docker-compose.staging.yml exec staging-app composer install --no-dev --optimize-autoloader`, `docker compose -f docker-compose.staging.yml exec staging-app php artisan optimize:clear`, `docker compose -f docker-compose.staging.yml exec staging-app php artisan up`. Use `migrate:rollback --step=<n>` only if a dependency migration was applied.
    - Staging PHP stays on 8.4 because Laravel 12 supports it. Do not use the PHP 8.3 image unless the rollback requires it.
    - Confirm `docker compose -f docker-compose.staging.yml exec staging-app php artisan --version` shows 12.x, login works, and an existing session still works (no flush was done). Run `vendor/bin/phpunit --display-all-issues` on the rolled-back tree and confirm zero failures and zero errors.
    - Record in `rollback-rehearsal.md`: the elapsed wall-clock time, the per-step times, the 12.x version output, and the suite result. If the total exceeds 60 minutes, record which steps exceeded the budget and ask the agent to shorten them in `DEPLOYMENT.md` before production is scheduled.
    - Return staging to the Laravel 13 commit afterwards: `git checkout <laravel-13-commit-hash>` and redeploy as in 17.3.
    - _Requirements: 10.4, 10.5, 10.6_

- [ ] 20. Checkpoint - Rollback rehearsed
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 21. Production deploy runbook and post-deploy smoke checks
  - [ ] 21.1 Write the production runbook
    - Create `production-runbook.md` from `DEPLOYMENT.md` and the records, with ordered steps: PHP re-check, dump and tarball preconditions, aduan count record, adding the three pinned env variables to the production `.env`, deploying the staging-verified commit identifier, post-deploy smoke checks, rollback trigger and rollback steps.
    - Include fill-in fields for each recorded value: PHP check date, dump and tarball paths and sizes, Laravel 12 commit hash, pre-deploy aduan count, deployed commit identifier, smoke check verdicts with times.
    - State the note that real AD connectivity can only be confirmed in production because staging uses dummy LDAP values.
    - _Requirements: 10.1, 10.2, 7.7, 9.8, 5.1_

  - [ ] 21.2 [MANUAL - operator] Re-check production PHP if the recorded check is older than 30 days
    - Compare today's date with the check date in `php-gate.md`. If more than 30 calendar days old, repeat the commands of 3.2 (`php -v` on the production CLI and the web-handler check), append the new outputs, parsed versions and date to `php-gate.md`.
    - If either is now below 8.4.0, production is blocked, and the contingency in 3.3 applies.
    - _Requirements: 2.9_

  - [ ] 21.3 [MANUAL - operator] Take and verify the pre-deploy backups
    - Within 60 minutes before the deploy, outside the repository working tree (for example `/var/backups/helpdesk/`): `mysqldump --single-transaction -u<user> -p <database> | gzip > /var/backups/helpdesk/pre-l13-$(date +%Y%m%d-%H%M).sql.gz` and `tar -czf /var/backups/helpdesk/pre-l13-storage-$(date +%Y%m%d-%H%M).tar.gz -C <app-path> storage/app`.
    - Verify both: `gzip -t /var/backups/helpdesk/pre-l13-*.sql.gz` and `tar -tzf /var/backups/helpdesk/pre-l13-storage-*.tar.gz > /dev/null`.
    - Record the current Laravel 12 commit hash (`git rev-parse HEAD`) and the aduan count (`php artisan tinker --execute="echo \App\Models\Report::count();"`, or the equivalent model name used by the app) in `production-runbook.md`, and the date after which the backups may be deleted (sign-off date plus 14 days).
    - _Requirements: 10.2, 10.8_

  - [ ] 21.4 [MANUAL - operator] Deploy the verified commit to production
    - Add `SESSION_COOKIE`, `CACHE_PREFIX` and `REDIS_PREFIX` to the production `.env`, computed from its real `APP_NAME` with the Laravel 12 formula, using the values in `prefix-baseline.md` as the pattern. Confirm that the production values equal what the running Laravel 12 app resolves now: `php artisan tinker --execute="echo config('session.cookie'), ' ', config('cache.prefix');"` run before the deploy.
    - Inside the maintenance window: `php artisan down`, `git fetch`, `git checkout <commit-id-from-staging-checklist>`, `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force` (expected to report nothing to migrate), `php artisan optimize:clear`, `php artisan config:cache`, `php artisan route:cache`, `php artisan up`. Use the build step already in `DEPLOYMENT.md` for the Vite assets.
    - Confirm `php artisan --version` shows Laravel 13.x and the same session cookie and cache prefix values as before the deploy. A difference marks the verification failed and triggers the rollback.
    - Record the deployed commit identifier and the times in `production-runbook.md`.
    - _Requirements: 5.1, 5.7, 9.8, 1.7_

  - [ ] 21.5 [MANUAL - operator] Post-deploy smoke checks: login, aduan creation, status transition
    - Within the maintenance window: log in; create an aduan; perform a status transition on an existing aduan; confirm none returns HTTP 419, and that a user logged in before the deploy is still logged in. Check `tail -n 100 storage/logs/laravel.log` for unhandled exceptions.
    - Record the verdict and time for each item in `production-runbook.md`. If any item fails, go to 21.7 before the window closes.
    - _Requirements: 10.7, 4.7, 5.4_

  - [ ] 21.6 [MANUAL - operator] Post-deploy LDAP smoke check against real Active Directory
    - Look up one known `samaccountname` through the Wakil BP lookup in the aduan form (and through the staff lookup API endpoint). Confirm the returned name, bahagian, email and telephone are non-empty and belong to that person. Do not create a real aduan for this check unless it is cancelled afterward by the usual process.
    - Record the verdict, date and the `samaccountname` used (not the person's details) in `production-runbook.md`. If the lookup fails, go to 21.7. A partial revert of only the LdapRecord commit is allowed only if `ldap-review.md` confirmed that LdapRecord 3 admits Laravel 13.
    - _Requirements: 7.7, 10.7_

  - [ ] 21.7 [MANUAL - operator] Rollback decision and sign-off
    - If any smoke check in 21.5 or 21.6 failed, execute the ordered rollback from `DEPLOYMENT.md` before the maintenance window closes, record which item failed, then confirm login works and the aduan count equals the pre-deploy figure recorded in 21.3.
    - If all smoke checks passed, record production sign-off with the date, and the date until which the dump and tarball are retained (at least 14 days). Record in `open-decisions.md` that the next framework review deadline is 17 Mar 2028.
    - _Requirements: 10.7, 10.8, 10.2, 1.5_

- [ ] 22. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are the optional property-based tests (Properties 1 to 3). Every example-based test task is required.
- Manual and operator tasks are not optional. The agent writes the templates and commands. It does not claim to have run anything on the production host, on staging deploys, against real Active Directory, or with the hosting provider.
- No task commits. Each COMMIT POINT hands over the files and the command `vendor/bin/phpunit --display-all-issues` and waits for the maintainer.
- Design sequence note: the design's step 3 dev-dependency raise has no commit of its own in Requirement 10.3, which only constrains the three isolated commits. This plan gives it a fourth, preparatory commit (7.7) so the three isolated commits stay clean. The `php ^8.4` constraint travels with the backup commit, as the design says.
- Test files are created after the code they cover: CSRF, `login_stats` and prefix tests are written after the framework bump, and LDAP tests are written on LdapRecord 3 and then adapted to v4.
- Requirement 6.8 and the staging deploy of commit 1 are exercised in 9.6, before the LdapRecord and framework commits.

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["1.2", "1.3"] },
    { "id": 2, "tasks": ["1.4"] },
    { "id": 3, "tasks": ["1.5"] },
    { "id": 4, "tasks": ["3.1"] },
    { "id": 5, "tasks": ["3.2"] },
    { "id": 6, "tasks": ["3.3"] },
    { "id": 7, "tasks": ["5.1"] },
    { "id": 8, "tasks": ["5.2"] },
    { "id": 9, "tasks": ["5.3"] },
    { "id": 10, "tasks": ["7.1"] },
    { "id": 11, "tasks": ["7.2"] },
    { "id": 12, "tasks": ["7.3"] },
    { "id": 13, "tasks": ["7.4", "7.5"] },
    { "id": 14, "tasks": ["7.6"] },
    { "id": 15, "tasks": ["7.7"] },
    { "id": 16, "tasks": ["9.1"] },
    { "id": 17, "tasks": ["9.2", "9.3"] },
    { "id": 18, "tasks": ["9.4"] },
    { "id": 19, "tasks": ["9.5"] },
    { "id": 20, "tasks": ["9.6"] },
    { "id": 21, "tasks": ["11.1"] },
    { "id": 22, "tasks": ["11.2"] },
    { "id": 23, "tasks": ["11.3"] },
    { "id": 24, "tasks": ["11.4"] },
    { "id": 25, "tasks": ["11.5"] },
    { "id": 26, "tasks": ["13.1"] },
    { "id": 27, "tasks": ["13.2"] },
    { "id": 28, "tasks": ["13.3"] },
    { "id": 29, "tasks": ["13.4", "13.5"] },
    { "id": 30, "tasks": ["13.6", "13.7", "13.8"] },
    { "id": 31, "tasks": ["13.9", "13.10"] },
    { "id": 32, "tasks": ["13.11"] },
    { "id": 33, "tasks": ["13.12"] },
    { "id": 34, "tasks": ["15.1", "15.2"] },
    { "id": 35, "tasks": ["15.3", "15.4"] },
    { "id": 36, "tasks": ["15.5"] },
    { "id": 37, "tasks": ["17.1"] },
    { "id": 38, "tasks": ["17.2"] },
    { "id": 39, "tasks": ["17.3"] },
    { "id": 40, "tasks": ["17.4"] },
    { "id": 41, "tasks": ["17.5"] },
    { "id": 42, "tasks": ["17.6"] },
    { "id": 43, "tasks": ["17.7"] },
    { "id": 44, "tasks": ["19.1"] },
    { "id": 45, "tasks": ["21.1"] },
    { "id": 46, "tasks": ["21.2"] },
    { "id": 47, "tasks": ["21.3"] },
    { "id": 48, "tasks": ["21.4"] },
    { "id": 49, "tasks": ["21.5"] },
    { "id": 50, "tasks": ["21.6"] },
    { "id": 51, "tasks": ["21.7"] }
  ]
}
```
