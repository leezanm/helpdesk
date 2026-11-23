# Requirements Document

_Feature: `laravel-13-upgrade` — upgrading the KKDW Helpdesk from Laravel 12 to Laravel 13._

## Introduction

The KKDW Helpdesk ("Sistem Meja Bantuan") currently runs on Laravel 12.68.0, PHP `^8.3`,
Livewire 3.8.6, PHPUnit 11.5, on the classic (non-slim) application skeleton. The previous spec
`.kiro/specs/laravel-livewire-upgrade` took the app from Laravel 8 to Laravel 12 and deliberately
deferred Laravel 13 to a separate follow-up (its task 12.4). This spec is that follow-up.

The driver is the support window. Laravel 12 bug fixes ended 13 Aug 2026 and its security fixes
end 24 Feb 2027; Laravel 13 (released 17 Mar 2026) receives security fixes until 17 Mar 2028. As
of 15 Nov 2026 the application is already past bug-fix support, so the cost of waiting is rising.

A feasibility investigation has already been completed. Its conclusions shape the scope below:

- The classic skeleton remains supported on Laravel 13 (`Illuminate\Foundation\Http\Kernel` still
  exists on the 13.x branch), so the slim-skeleton rewrite is out of scope.
- Application code changes are small. Only three framework breaking changes apply: the CSRF
  middleware rename plus its new request-origin verification, the hyphenated default
  cache/Redis/session-cookie prefixes, and the new `serializable_classes` cache option.
- The real scope is dependency work: `spatie/laravel-backup` must move to `^10` (which requires
  PHP 8.4) and `directorytree/ldaprecord-laravel` must move to `^4`.
- `spatie/laravel-permission` stays on `^6`, `livewire/livewire` stays on `^3`, and
  `laravel/fortify` stays on `^1`, because each of those branches already allows
  `illuminate ^13`. No Livewire 4 or permission v7/v8 migration is required.

The upgrade is **behavior-preserving**: no new features, no UI changes, no data model changes.

### Known caveat

Composer had no network access during the investigation. Every package conclusion was reached by
reading each package's `composer.json` on its maintenance branch, not from an actual dependency
resolve. An authoritative `composer update --dry-run` is therefore an early, blocking step rather
than a confirmation at the end.

### Effort and risk (from the investigation, recorded here as context, not as requirements)

Approximately 4.5–6 working days of hands-on effort if PHP 8.4 is already available on the
production host, plus infrastructure wait time if it is not. Risk by area: application code LOW;
Livewire and spatie/laravel-permission LOW; `spatie/laravel-backup` v10 MEDIUM;
`directorytree/ldaprecord-laravel` v4 MEDIUM (verifiable only against real AD in production);
CSRF `Sec-Fetch-Site` behavior MEDIUM; production PHP 8.4 availability MEDIUM–HIGH and a potential
hard blocker.

## Glossary

- **Aduan** — a complaint/report, the core entity `Report`.
- **Wakil BP** — the "bagi pihak" (on-behalf-of) path in the aduan form, where a complaint is
  lodged for another person; the LDAP lookup feeds the represented person's details.
- **Helpdesk_Application** — the deployed KKDW Helpdesk application.
- **Upgrade_Process** — the sequence of code, dependency and configuration changes performed
  under this spec, including its git commits.
- **Test_Suite** — the 52 PHPUnit test files under `tests/` (Feature and Unit).
- **Staging_Environment** — the staging deployment created by the `laravel-livewire-upgrade`
  spec, reused here.
- **Production_Environment** — the government-managed production host running the
  Helpdesk_Application.
- **Backup_Module** — `spatie/laravel-backup` together with `config/backup.php`,
  `app/Http/Controllers/BackupController.php` and the `manage.backup` permission.
- **LDAP_Lookup** — the two `LdapRecord` call sites: the Wakil BP lookup in the aduan report form
  (`app/Livewire/Aduan/ReportForm.php`) and the staff lookup API endpoint
  (`app/Http/Controllers/Api/UserController.php`).
- **Deployment_Runbook** — the written deploy and rollback procedure for this upgrade,
  stored with this spec and reflected in `DEPLOYMENT.md`.
- **Behavior-preserving** — user-visible functionality, permissions and stored data are
  unchanged after the step.

---

## Requirements

### Requirement 1: Reach a supported framework version without behavior change

**User Story:** As a maintainer, I want the Helpdesk_Application running on Laravel 13, so that
the application receives security fixes beyond 24 Feb 2027.

#### Acceptance Criteria

1. WHEN the Upgrade_Process completes, THE Helpdesk_Application SHALL declare `laravel/framework ^13.0` in `composer.json` and SHALL record in `composer.lock` a resolved `laravel/framework` version of at least 13.0.0 and below 14.0.0.
2. WHEN the Upgrade_Process completes, THE Helpdesk_Application SHALL retain the classic skeleton files `app/Http/Kernel.php`, `app/Console/Kernel.php`, `app/Exceptions/Handler.php` and the provider list in `config/app.php`, with no slim-skeleton migration, and with the CSRF middleware rename required by Requirement 4 as the only permitted change to the middleware registrations in `app/Http/Kernel.php`.
3. WHEN the Upgrade_Process completes, THE Helpdesk_Application SHALL expose the same routes as the recorded pre-upgrade baseline, matching on URI, HTTP method and route name with zero additions and zero removals, SHALL expose the same role names and permission names as that baseline with zero additions and zero removals, and SHALL render the same Bahasa Malaysia interface strings as that baseline with zero differences.
4. WHEN the Upgrade_Process completes, THE Upgrade_Process SHALL leave the database schema identical to the recorded pre-upgrade baseline in table names, column names, column types, nullability, indexes and foreign keys, and SHALL introduce no migration other than one published by an upgraded dependency and recorded in this spec folder against that dependency's name.
5. WHEN the Upgrade_Process completes, THE Upgrade_Process SHALL record in this spec folder the Laravel 13 security-support end date of 17 Mar 2028 as the next review deadline.
6. WHEN the Upgrade_Process begins, THE Upgrade_Process SHALL capture and store in this spec folder a pre-upgrade baseline comprising the route list, the role and permission names, the Bahasa Malaysia interface strings and the database schema structure, before any dependency version is changed.
7. WHEN the upgraded Helpdesk_Application is booted, THE Helpdesk_Application SHALL report an installed framework version in the 13.x range and SHALL complete the boot without an unhandled exception.
8. IF the post-upgrade comparison of routes, role names, permission names, interface strings or database schema differs from the recorded baseline, THEN THE Upgrade_Process SHALL remain incomplete and SHALL NOT be deployed to the Production_Environment until each difference is either reverted or recorded in this spec folder as required by a named upgraded dependency and accepted by the maintainer.

### Requirement 2: Gate the upgrade on production PHP 8.4

**User Story:** As a maintainer, I want the production PHP version confirmed before any dependency
is touched, so that the upgrade is not discovered to be impossible halfway through.

#### Acceptance Criteria

1. WHEN the Upgrade_Process begins and before any version constraint in `composer.json` is changed, THE Upgrade_Process SHALL obtain the Production_Environment PHP version both for the command line (via `php -v` on that host) and for the handler serving web requests, and SHALL record in this spec folder the exact output of each, the full `major.minor.patch` value read from it, and the calendar date of the check.
2. WHILE either recorded Production_Environment PHP version is below 8.4.0, or while any of the values required by criterion 1 is not yet recorded, THE Upgrade_Process SHALL make no change to any version constraint in `composer.json` and no change to `composer.lock`.
3. IF the Production_Environment PHP version cannot be obtained within 10 working days of the request to the hosting provider, THEN THE Upgrade_Process SHALL record the date the request was made, the date the 10 working days elapsed, and the absence of a response, and SHALL treat the version as below 8.4 for the purpose of criterion 2.
4. IF either recorded Production_Environment PHP version is below 8.4.0 and the hosting provider has either declined a PHP 8.4 upgrade in writing or not responded in writing within 30 calendar days of the request, THEN THE Upgrade_Process SHALL record, with the decision date and the deciding maintainer named, exactly one decision from (a) requesting a PHP 8.4 upgrade from the hosting provider with the request date, (b) deferring the upgrade with a stated calendar review date, and (c) proceeding without `spatie/laravel-backup ^10` by keeping that package on its current major version and recording an alternative backup arrangement that names the responsible party and the backup frequency.
5. WHEN both recorded Production_Environment PHP versions are 8.4.0 or higher and below 9.0.0, THE Upgrade_Process SHALL set the `php` constraint in `composer.json` to `^8.4`.
6. WHEN the `php` constraint is changed, THE Upgrade_Process SHALL update the server prerequisites section of `DEPLOYMENT.md` to state a minimum PHP version of 8.4, replacing the existing "PHP 8.3+" text, in the same commit as the `composer.json` change.
7. WHEN the PHP gate is resolved under criterion 5 and before any Laravel 13 code is deployed to it, THE Staging_Environment SHALL report a PHP version whose major and minor components equal those of the recorded Production_Environment version.
8. IF the Staging_Environment PHP major and minor components differ from the recorded Production_Environment version, THEN THE Upgrade_Process SHALL block the Laravel 13 deploy to the Staging_Environment until the versions match, or SHALL record the accepted difference together with the stated rationale and the maintainer who accepted it.
9. WHEN the Production_Environment deploy is prepared and the recorded PHP version check is more than 30 calendar days old, THE Upgrade_Process SHALL repeat the check of criterion 1 and SHALL record the new result, and IF the newly recorded version is below 8.4.0, THEN THE Upgrade_Process SHALL block the Production_Environment deploy.

### Requirement 3: Resolve dependencies to Laravel 13-compatible versions

**User Story:** As a developer, I want an authoritative dependency plan produced by Composer
itself, so that the upgrade order is based on real resolution and not on inspection of
maintenance branches.

#### Acceptance Criteria

1. WHEN network access to Packagist is available, and before the first `composer.json` version constraint change is committed, THE Upgrade_Process SHALL run `composer update --dry-run` with `laravel/framework` constrained to `^13.0`, and SHALL record in this spec folder the complete command output (every reported conflict plus the version resolved for each package named in criteria 3, 4, 6 and 7) together with the pre-upgrade installed version of those packages as read from `composer.lock`: `spatie/laravel-backup` 9.4.1, `directorytree/ldaprecord-laravel` 3.4.3, `laravel/tinker` 2.11, `phpunit/phpunit` 11.5, `nunomaduro/collision` 8.9, `barryvdh/laravel-debugbar` 3.16, `laravel-lang/lang` 12.17, `spatie/laravel-permission` 6.25, `livewire/livewire` 3.8.6, `laravel/fortify` 1.39.
2. IF the recorded dry-run output reports a conflict that is not described in this document, or resolves any package named in criteria 3, 4, 6 or 7 to a version outside the constraint stated there, THEN THE Upgrade_Process SHALL update this requirements document to match the recorded output, and SHALL make no further `composer.json` or application code change until that update is committed.
3. WHEN the recorded dry-run output confirms the planned versions, THE Upgrade_Process SHALL raise `spatie/laravel-backup` to `^10.0`, `directorytree/ldaprecord-laravel` to `^4.0`, `laravel/tinker` to `^3.0`, `phpunit/phpunit` to `^12.0`, and `nunomaduro/collision` to the lowest version whose own `phpunit/phpunit` constraint allows `^12.0`.
4. WHEN dependencies are raised, THE Upgrade_Process SHALL keep `spatie/laravel-permission` on `^6`, `livewire/livewire` on `^3` and `laravel/fortify` on `^1`, and SHALL record in this spec folder, for each of these three packages, the version resolved by the dry-run together with the `illuminate/*` constraint that version declares, showing that the constraint admits `^13`.
5. WHEN `laravel/fortify` is updated within `^1`, THE Upgrade_Process SHALL record in this spec folder whether the resolved release adds the `laravel/passkeys` dependency, and SHALL confirm the login flow is unaffected by all three of: the Test_Suite authentication tests running with zero failures and zero errors, the login screen presenting the same input fields and the same Bahasa Malaysia text as before the upgrade, and no new migration or published configuration file being required by that dependency.
6. WHEN the dev dependency `barryvdh/laravel-debugbar` is replaced with `fruitcake/laravel-debugbar`, THE Upgrade_Process SHALL place the replacement in the `require-dev` section only, and SHALL confirm that the Test_Suite runs with zero failures and zero errors and that the Helpdesk_Application boots with debug mode disabled.
7. WHEN the dry-run output is recorded, THE Upgrade_Process SHALL confirm, before `laravel/framework` is bumped, that `laravel-lang/lang ^12` resolves together with `laravel/framework ^13.0` and that the Bahasa Malaysia validation messages it supplies render unchanged on the login form and the aduan form, and SHALL record that result in this spec folder.
8. WHEN dependencies are raised, THE Upgrade_Process SHALL first raise, while `laravel/framework` remains on `^12`, every package whose target version declares an `illuminate/*` constraint that admits `^12`, and SHALL bump `laravel/framework` to `^13.0` only after a full Test_Suite run on those raised dependencies reports zero failures and zero errors.
9. WHEN `phpunit/phpunit ^12` is installed, THE Upgrade_Process SHALL validate `phpunit.xml` against the PHPUnit 12 schema and SHALL resolve every reported schema error and deprecation, so that a subsequent validation run and a subsequent Test_Suite run each report zero schema errors and zero schema deprecation warnings.
10. IF network access to Packagist is unavailable, THEN THE Upgrade_Process SHALL remain blocked before the first `composer.json` version change, SHALL record in this spec folder that the compatibility conclusions in this document come from reading maintenance-branch `composer.json` files and are unverified by a Composer resolve, and SHALL make no change to the `laravel/framework` constraint.
11. IF the Test_Suite reports any failure or error after a dependency version is raised, THEN THE Upgrade_Process SHALL restore that package to its recorded pre-upgrade version, SHALL record the package involved and the failing test names in this spec folder, and SHALL raise no further dependency until that failure is resolved or an explicit decision to accept it is recorded.
12. IF `laravel-lang/lang ^12` does not resolve alongside `laravel/framework ^13.0`, or its Bahasa Malaysia messages fail to render on the login or aduan form, THEN THE Upgrade_Process SHALL record in this spec folder either the version that does resolve, or a decision to remove the package together with the replacement source of the Bahasa Malaysia validation messages.

### Requirement 4: Adopt the renamed CSRF middleware and its origin verification

**User Story:** As a user on the government network, I want forms to keep submitting successfully
after the upgrade, so that aduan can still be created and updated.

#### Acceptance Criteria

1. WHEN the framework is upgraded, THE Upgrade_Process SHALL replace `app/Http/Middleware/VerifyCsrfToken.php` with a `PreventRequestForgery` middleware in the same namespace that extends the Laravel 13 `PreventRequestForgery` framework class, SHALL replace the `web` group entry in `app/Http/Kernel.php` with the renamed class, and SHALL leave no reference to the deprecated `VerifyCsrfToken` alias in application code.
2. WHEN the new middleware is in place, THE Upgrade_Process SHALL keep the CSRF exception list empty, so that zero paths are exempted from CSRF and origin verification.
3. WHEN a POST, PUT, PATCH or DELETE request to a route in the `web` middleware group arrives with a CSRF token matching the session token and a `Sec-Fetch-Site` value of `same-origin`, THE Helpdesk_Application SHALL pass the request to its route handler and SHALL NOT return HTTP 419.
4. IF a state-changing request to a route in the `web` middleware group arrives with a `Sec-Fetch-Site` value of `cross-site`, THEN THE Helpdesk_Application SHALL reject the request with HTTP 419, SHALL NOT apply any data change from that request, and SHALL record the rejection in the application log including the requested path.
5. IF a state-changing request to a route in the `web` middleware group arrives with no `Sec-Fetch-Site` header, THEN THE Helpdesk_Application SHALL decide the outcome on CSRF token validity alone and SHALL NOT reject the request on origin grounds.
6. WHEN the Staging_Environment is verified, THE Upgrade_Process SHALL execute login, aduan creation, attachment upload, status transition and feedback submission at least once each through the same proxy path used by the Production_Environment, SHALL require all five flows to complete with zero HTTP 419 responses, and SHALL record in this spec folder whether the observed requests carried or lacked the `Sec-Fetch-Site` header.
7. IF any of the five flows in criterion 6 is rejected with HTTP 419 in the Staging_Environment or Production_Environment, THEN THE Upgrade_Process SHALL apply a mitigation that restores all five flows to zero HTTP 419 responses, SHALL prefer trusted-origin configuration over disabling origin verification, and SHALL record in this spec folder the mitigation applied, the reason trusted-origin configuration was or was not usable, and the residual risk accepted.
8. WHEN the upgrade is complete, THE Test_Suite SHALL include feature tests that assert the outcomes of criteria 3, 4 and 5 for at least one state-changing route, and all such tests SHALL pass.

### Requirement 5: Preserve active sessions and cached data across the deploy

**User Story:** As a logged-in user, I want to stay logged in after the upgrade is deployed, so
that the upgrade is invisible to me.

#### Acceptance Criteria

1. WHEN the framework is upgraded to Laravel 13, THE Upgrade_Process SHALL set `SESSION_COOKIE`, `CACHE_PREFIX` and `REDIS_PREFIX` explicitly in each of `.env.example`, `.env.server` and `.env.staging` to the underscore-slug values that the Laravel 12 release resolves from that same file's `APP_NAME` (`{underscore_slug}_session`, `{underscore_slug}_cache`, `{underscore_slug}_database_`), leaving no one of the three files dependent on a framework fallback.
2. WHEN the session cookie name and the cache key prefix resolved from a given env file by the Laravel 12 release are compared with the values resolved from the same env file by the Laravel 13 release, THE Helpdesk_Application SHALL resolve both values as character-for-character identical strings that contain no hyphen-separated form (`-session`, `-cache-`, `-database-`).
3. WHEN `config/session.php` is updated for Laravel 13, THE Upgrade_Process SHALL set the session `serialization` option to `php`, which is the value in use on Laravel 12, in place of the Laravel 13 skeleton value `json`.
4. WHEN a user holds a session cookie issued by the Laravel 12 release less than `SESSION_LIFETIME` (120 minutes) before the Laravel 13 release is deployed, and that user requests an authenticated page after the deploy, THE Helpdesk_Application SHALL serve the requested page as that same authenticated user without redirecting to the login page.
5. WHEN `config/cache.php` is updated for Laravel 13, THE Upgrade_Process SHALL set the `serializable_classes` option to a value that permits the array payload written by the single cache write in the application, the `login_stats` entry at `app/Providers/FortifyServiceProvider.php:53`, and SHALL record that this payload is an array containing no PHP objects.
6. WHEN the `login_stats` cache entry written by the Laravel 12 release is read by the deployed Laravel 13 release within its 600-second time-to-live, THE Helpdesk_Application SHALL return the stored array with the same contents and SHALL raise no unserialization error.
7. IF the session cookie name, cache key prefix or Redis key prefix resolved by the Laravel 13 release differs from the value recorded from the Laravel 12 release for the same env file, THEN THE Upgrade_Process SHALL mark the upgrade verification as failed, SHALL NOT deploy the release, and SHALL report which of the three settings differs together with the Laravel 12 value and the Laravel 13 value.
8. IF the `login_stats` cache entry cannot be read or cannot be unserialized after the upgrade, THEN THE Helpdesk_Application SHALL treat the entry as a cache miss, SHALL recompute and store the login statistics with the same 600-second time-to-live, and SHALL render the requesting page without an error indication to the user.

### Requirement 6: Upgrade the Backup_Module with a verified backup and restore

**User Story:** As a system administrator, I want backups to keep working and to be provably
restorable, so that the upgrade does not quietly break disaster recovery.

#### Acceptance Criteria

1. WHEN `spatie/laravel-backup ^10` is installed, THE Upgrade_Process SHALL compare `config/backup.php` against the v10 published configuration and SHALL record, for every option that changed, was added, or was removed, whether the Helpdesk_Application keeps its current value or adopts the v10 default, and SHALL leave the configured backup sources covering both the MySQL 8 database and the `storage/` directory.
2. WHEN the Backup_Module upgrade is deployed to the Staging_Environment, THE Helpdesk_Application SHALL allow a user holding the `manage.backup` permission to open the backup screen, see the list of stored backup archives with the same columns shown before the upgrade, and trigger a backup run.
3. WHEN a backup is triggered on the Staging_Environment after the upgrade, THE Backup_Module SHALL produce exactly one archive that contains a dump of every table in the source MySQL 8 database and every file present under `storage/` at the start of the run, and THE Upgrade_Process SHALL record the archive file size and the elapsed run time.
4. WHEN the archive produced in criterion 3 is restored into an empty MySQL 8 database on the Staging_Environment, THE Helpdesk_Application SHALL serve the login screen, authenticate a user account that existed in the source database, and display an aduan list whose total record count and set of aduan reference numbers are identical to those of the source database.
5. WHEN the Backup_Module upgrade is committed, THE Upgrade_Process SHALL place it in a commit containing only the `spatie/laravel-backup` constraint change in `composer.json`, the matching `composer.lock` entries, and the `config/backup.php` changes from criterion 1, with no `laravel/framework` version change and no other unrelated change.
6. IF `spatie/laravel-backup ^10` cannot run because the recorded Production_Environment PHP version is below 8.4, THEN THE Upgrade_Process SHALL treat this as the blocking condition in Requirement 2 and SHALL resolve it through the Requirement 2 criterion 4 decision, including its option (c) documented alternative backup arrangement, rather than lowering the `laravel/framework ^13.0` target.
7. WHEN the restore in criterion 4 completes, THE Helpdesk_Application SHALL open and download the attachment of each sampled aduan, the sample being at least 5 aduan that have attachments in the source database or all such aduan if fewer than 5 exist, without a missing-file error.
8. IF a backup run on the Staging_Environment reports an error, produces no archive within 60 minutes, or produces an archive that fails the content check in criterion 3, THEN THE Upgrade_Process SHALL record the failing step, SHALL keep `spatie/laravel-backup` at the pre-upgrade 9.4.1 resolution in `composer.lock`, and SHALL block the Production_Environment deploy until a successful backup-and-restore round trip is recorded.
9. IF a user without the `manage.backup` permission requests the backup screen or triggers a backup run after the upgrade, THEN THE Helpdesk_Application SHALL deny the request with the same access-denied response used before the upgrade and SHALL create no backup archive.

### Requirement 7: Upgrade LdapRecord and protect the Wakil BP path

**User Story:** As a user lodging an aduan on behalf of someone else, I want the staff lookup to
keep resolving names correctly, so that the complaint records the right person.

#### Acceptance Criteria

1. WHEN `directorytree/ldaprecord-laravel ^4` and its `directorytree/ldaprecord ^4` dependency are installed, THE Upgrade_Process SHALL update both LDAP_Lookup call sites so that each `query()`, `select()`, `findBy('samaccountname', …)` and `getFirstAttribute()` call executes without a fatal or undefined-method error, and SHALL record in this spec folder every changed method signature, renamed method and changed return type for those four methods.
2. WHEN a `samaccountname` matching exactly one stubbed directory entry is looked up against a stubbed LdapRecord directory, THE LDAP_Lookup SHALL return the same single string value that the Laravel 12 implementation returns for the same stub data, for each attribute that call site selects: `cn`, `physicaldeliveryofficename` and `userprincipalname` at both call sites, plus `telephonenumber` and `mobile` at the report-form call site, and WHEN `telephonenumber` is empty for that entry, THE report-form LDAP_Lookup SHALL resolve the telephone value from `mobile` instead.
3. IF the directory returns no match for a looked-up `samaccountname`, THEN THE LDAP_Lookup SHALL return a null result, THE Helpdesk_Application SHALL present an error message indicating that the user was not found, SHALL leave the Wakil BP identity fields empty, and SHALL NOT raise an unhandled exception at either call site.
4. IF the directory is unreachable, rejects the bind, or does not respond within 10 seconds, THEN THE LDAP_Lookup SHALL abort the lookup after that single attempt with no retry, THE Helpdesk_Application SHALL present the existing error message indicating that the Active Directory connection failed and that the administrator should be contacted, SHALL retain all values the submitter has already entered in the aduan form, and SHALL allow an aduan that does not use the Wakil BP path to be submitted successfully.
5. WHEN an aduan is submitted through the Wakil BP path after the upgrade with `is_bp` set, THE Helpdesk_Application SHALL store the represented person's resolved `cn`, `userprincipalname` and telephone value as the complainant name, email and telephone, and SHALL store the authenticated submitter as the representative (Wakil), with no swap of those two identities in either direction.
6. WHEN the LdapRecord upgrade is committed, THE Upgrade_Process SHALL place it in its own commit containing no unrelated changes.
7. WHEN the Production_Environment deploy is planned, THE Deployment_Runbook SHALL state that real Active Directory connectivity can only be confirmed in production because the Staging_Environment uses dummy LDAP values, and SHALL list the post-deploy LDAP smoke check as: look up one known `samaccountname` and confirm that the returned name, bahagian, email and telephone values are non-empty and belong to that person.
8. IF the looked-up person's `physicaldeliveryofficename` is empty or differs from the authenticated submitter's bahagian AND the submitter does not hold the represent-any-bahagian permission, THEN THE Helpdesk_Application SHALL reject the lookup with the existing error message indicating that representation is limited to the submitter's own bahagian, and SHALL leave the Wakil BP identity fields empty.
9. WHEN an aduan stored through the Wakil BP path is displayed in the aduan detail view, in the aduan listing and in the notification e-mail, THE Helpdesk_Application SHALL present the same complainant name, email and telephone values that were stored for that aduan under criterion 5, and SHALL identify the submitter separately as the Wakil.

### Requirement 8: Keep the Test_Suite green on PHPUnit 12

**User Story:** As a maintainer, I want the full Test_Suite passing at every step, so that
regressions are caught before they reach users.

#### Acceptance Criteria

1. WHEN the Upgrade_Process begins and before any dependency version is changed, THE Upgrade_Process SHALL execute the full Test_Suite (both the Unit and Feature testsuites declared in `phpunit.xml`) once in single-run, non-watch mode on the current Laravel 12 codebase, and SHALL record as the baseline: the number of tests executed, the number of assertions, the counts of failures, errors and skipped tests, and the fully qualified name of every test that does not pass.
2. WHEN one upgrade step completes, THE Upgrade_Process SHALL execute the full Test_Suite once in single-run mode, and SHALL begin the next upgrade step only if that run reports zero failures and zero errors other than the test names recorded in the baseline under criterion 1.
3. IF a Test_Suite run after an upgrade step reports a failure or error whose test name is not in the recorded baseline, THEN THE Upgrade_Process SHALL halt before starting the next upgrade step and SHALL report to the maintainer the failing test names and the upgrade step in which they first appeared.
4. WHEN the Test_Suite runs on Laravel 13 with PHPUnit 12, THE Test_Suite SHALL report zero failures and zero errors across all 52 test files under `tests/`, and the number of tests executed SHALL be greater than or equal to the baseline count recorded under criterion 1.
5. THE Upgrade_Process SHALL NOT reduce the number of test files below 52, delete or empty an existing test method, or mark a test that passed in the baseline as skipped or incomplete, in order to obtain a passing run.
6. WHEN a Test_Suite run on PHPUnit 12 reports a deprecation notice originating from code under `app/`, `tests/`, `database/` or `config/`, THE Upgrade_Process SHALL change that code so the notice no longer appears in a subsequent run, SHALL NOT add deprecation suppression or deprecation-ignoring configuration to `phpunit.xml` or to test code, and SHALL treat deprecation notices originating from packages under `vendor/` as out of scope by recording the notice together with the owning package name.
7. WHILE the Test_Suite is running against a fresh in-memory SQLite database, IF a permission or role name being seeded already exists in that database, THEN THE Test_Suite SHALL reuse the existing record, SHALL complete the permission, role, report-status and report-trail-status seeding for that test case without raising `PermissionAlreadyExists`, and SHALL leave the existing role-to-permission assignments unchanged.
8. WHEN a test that asserts a Livewire component or compares a date column is touched during the Upgrade_Process, THE Upgrade_Process SHALL keep the repository's existing `Livewire::test` assertion style, Livewire exception-handling style, and date-column database assertion style unchanged.
9. IF an existing `Livewire::test` assertion, Livewire exception-handling or date-column assertion pattern no longer produces a passing result under PHPUnit 12, THEN THE Upgrade_Process SHALL apply the smallest change that restores the pass, SHALL limit that change to the affected test methods, and SHALL report the changed pattern and the reason to the maintainer before the commit point.
10. WHEN the Upgrade_Process reaches a commit point, THE Upgrade_Process SHALL hand the change to the maintainer together with the single-run command for the full Test_Suite, SHALL NOT create the commit itself, and SHALL wait for the maintainer's explicit confirmation that the Test_Suite passed before starting the next task.

### Requirement 9: Verify on staging before production

**User Story:** As a maintainer, I want the release exercised on staging first, so that the
production deploy is a repeat of a known-good run.

#### Acceptance Criteria

1. WHEN the Laravel 13 branch passes the Test_Suite with zero failures, THE Upgrade_Process SHALL deploy that same branch commit to the Staging_Environment, and SHALL NOT deploy to the Production_Environment before the Staging_Environment checklist in criterion 2 has completed with every item recorded as passed.
2. WHEN the Staging_Environment deploy completes, THE Upgrade_Process SHALL execute a written checklist of exactly these nine items and SHALL record, for each item, a pass or fail verdict with the date, the tester identity, and the observed result: (a) login through the custom `username` Fortify flow, (b) aduan creation with at least one attachment within the application's configured attachment size limit, (c) a status transition on an existing aduan, (d) feedback submission on an aduan, (e) the notification bell unread count, (f) each laporan chart screen, (g) a backup run, (h) the Wakil BP lookup, (i) logout.
3. WHILE the checklist in criterion 2 is being executed, THE Upgrade_Process SHALL record an item as passed only if the exercised screen completes its action within 10 seconds, reports zero JavaScript console errors, reports zero failed asset or data requests, and writes zero unhandled-exception entries to the application log.
4. WHEN the Staging_Environment assets are built with the existing Vite configuration, THE Upgrade_Process SHALL confirm that every `@vite`, `@livewireStyles` and `@livewireScripts` reference in the layout resolves to a served file, that the laid-out page renders with its stylesheet applied, and that Livewire actions on the exercised screens respond to user interaction.
5. WHEN an aduan notification e-mail is triggered on the Staging_Environment, THE Helpdesk_Application SHALL enqueue the notification on the `database` queue connection, and WHEN a queue worker is running, THE Helpdesk_Application SHALL dispatch the queued notification within 60 seconds and leave zero entries in the failed-jobs store.
6. IF any checklist item is recorded as failed, THEN THE Upgrade_Process SHALL block the Production_Environment deploy, and SHALL require that the failed item and every item sharing its screen be re-executed and recorded as passed on a rebuilt Staging_Environment deploy before the block is lifted.
7. WHEN the Staging_Environment is prepared for the checklist, THE Upgrade_Process SHALL confirm that it holds the restored production data copy of at least 2,100 aduan records and at least 300 user records, and SHALL record a failed verdict for the checklist if either count is lower.
8. WHEN every checklist item is recorded as passed, THE Upgrade_Process SHALL record the exact commit identifier that was verified, and THE Upgrade_Process SHALL deploy that same commit identifier to the Production_Environment.

### Requirement 10: Provide a rollback path

**User Story:** As a maintainer, I want a tested way back to Laravel 12, so that a failed deploy
can be reversed within one maintenance window.

#### Acceptance Criteria

1. THE Deployment_Runbook SHALL record the rollback procedure as an ordered list of commands covering the Laravel 12 git revision to return to identified by commit hash, restoring the Laravel 12 `composer.lock` and installing from that lock file, reverting the host PHP version to the version recorded under Requirement 2 if the host was raised to 8.4, reversing any dependency-supplied migration applied during the deploy or restoring the pre-deploy database dump instead, and the cache, config, route and view clearing commands to run after the revert.
2. WHEN the Production_Environment deploy begins, THE Upgrade_Process SHALL have a gzipped database dump and a `storage/` uploads tarball taken no more than 60 minutes earlier, stored in a directory outside the repository working tree, each verified readable by a decompression check, and retained for at least 14 days after production sign-off.
3. WHEN the Upgrade_Process produces commits, THE Upgrade_Process SHALL keep the Backup_Module upgrade, the LdapRecord upgrade and the `laravel/framework ^13` bump as three separate commits, each containing only its own `composer.json` and `composer.lock` change plus the code changes that one change requires, with no commit containing two of the three, so that each can be reverted independently.
4. WHEN the rollback procedure is executed on the Staging_Environment as a rehearsal, THE Helpdesk_Application SHALL return to the recorded Laravel 12 revision, report an installed `laravel/framework` version within 12.x, and pass the Test_Suite with zero failures and zero errors.
5. IF a rollback is performed after users have created sessions on Laravel 13, THEN THE Deployment_Runbook SHALL state that `SESSION_COOKIE`, `CACHE_PREFIX` and `REDIS_PREFIX` hold their pinned Laravel 12 values on both revisions and that session `serialization` stays `php`, so that no session-store or cache flush is required in either direction.
6. WHEN the rehearsal on the Staging_Environment completes, THE Upgrade_Process SHALL record the elapsed wall-clock time of the rollback, and IF that time exceeds 60 minutes, THEN THE Upgrade_Process SHALL record which steps exceeded the budget and SHALL shorten them before the Production_Environment deploy is scheduled.
7. IF any item of the post-deploy smoke check fails on the Production_Environment, covering login, aduan creation, status transition and the Wakil BP lookup, THEN THE Upgrade_Process SHALL execute the rollback procedure before the maintenance window closes and SHALL record which item failed.
8. WHEN a rollback completes on the Production_Environment, THE Helpdesk_Application SHALL allow login and SHALL list an aduan count equal to the count recorded immediately before the deploy.

### Requirement 11: Record the outcome

**User Story:** As a future maintainer, I want the upgrade documented, so that the next framework
upgrade starts from a known state.

#### Acceptance Criteria

1. WHEN the `laravel/framework` bump and all dependency upgrades have passed the Test_Suite, THE Upgrade_Process SHALL update `README.md` so that the Laravel, PHP, Livewire and PHPUnit versions it states match, to major and minor version, the versions recorded in `composer.lock`, and SHALL leave no reference to a superseded version (Laravel 12, PHPUnit 11, PHP 8.3) in any section of `README.md`, including the stack summary, the local development requirements, the staging section and the testing section.
2. WHEN the `php` constraint and the deploy procedure are final, THE Upgrade_Process SHALL update `DEPLOYMENT.md` so that it states the PHP requirement as the version recorded under Requirement 2 criterion 1, names the resulting Laravel and Livewire versions, retains the MySQL 8.0 requirement, and describes every deploy command whose text or ordering changed in this upgrade, with no remaining occurrence of "PHP 8.3+".
3. WHEN the Upgrade_Process completes, THE Upgrade_Process SHALL record in this spec folder, for every package whose version in `composer.lock` differs from the pre-upgrade `composer.lock`, the package name, the pre-upgrade version, the post-upgrade version, and the reason for the change stated as one of: required for Laravel 13 compatibility, pulled in transitively, or replaced package.
4. WHEN an investigated breaking change is found not to apply, THE Upgrade_Process SHALL record it in this spec folder as verified-not-applicable together with the reason it does not apply, and SHALL include all already-verified items — `upsert`, `JobAttempted`/`exceptionOccurred`, the `QueueBusy` `$connection` property, pagination view names, `Str` UUID/ULID/random factories in tests, custom `array_first`/`array_last` helpers, morph pivot and custom pivot classes, custom cache/queue/dispatcher/response-factory driver implementations, and `Container::call` nullable class defaults — marked as not requiring re-investigation at the next framework upgrade.
5. IF a version stated in `README.md` or `DEPLOYMENT.md` differs from the version recorded in `composer.lock` or from the recorded `php -v` output of the Production_Environment, THEN THE Upgrade_Process SHALL treat the documentation as incomplete and SHALL block the Production_Environment deploy until the stated versions match.
6. WHEN the dependency record in criterion 3 is written, THE Upgrade_Process SHALL also record every item left open or decided by exception during this upgrade — including the `laravel-lang/lang` compatibility outcome, any origin-verification mitigation, and any alternative backup arrangement — each with the decision taken and the condition under which it is to be revisited.

---

## Correctness properties

Most of this upgrade is a one-time migration, so most verification is example-based. The
following properties are worth expressing as property-based tests because their behavior varies
meaningfully with input and they exercise application code rather than infrastructure.

- **P1 — Wakil BP field mapping is an invariant (Requirement 7.5 and 7.9).** For any combination of
  `is_bp` and the `bp_*` complainant fields, the aduan stored by `ReportForm` and the complainant
  identity displayed on the aduan show screen and in the notification e-mail SHALL refer to the
  same person. This guards the known field-inversion trap in this area.
- **P2 — LDAP_Lookup error conditions (Requirement 7.3 and 7.4).** For any arbitrary
  `samaccountname` string, including empty, over-length, and strings containing LDAP filter
  metacharacters, the LDAP_Lookup against a stubbed directory SHALL return either a resolved
  attribute set or a null result, and SHALL NOT raise an unhandled exception.
- **P3 — Cache key prefixing is stable (Requirement 5.2).** For any cache key string, the fully
  qualified key produced under the pinned `CACHE_PREFIX` SHALL equal the key produced by the
  Laravel 12 configuration for the same input.

Verified by example or integration test instead, because the behavior does not vary with input or
depends on external systems: the backup-and-restore round trip (Requirement 6.3, 6.4), the
`Sec-Fetch-Site` proxy behavior (Requirement 4.6), the session-survival check (Requirement 5.4),
real Active Directory connectivity (Requirement 7.7), and the staging checklist (Requirement 9.2).

---

## Out of scope

- Migrating to the slim application skeleton; the classic Kernel/Handler layout is retained.
- Upgrading Livewire 3 to Livewire 4.
- Upgrading `spatie/laravel-permission` beyond the `^6` branch.
- Any feature, UI or data model change; this upgrade is behavior-preserving.
- Changing cache, session or queue drivers; `file`, `file` and `database` respectively are kept.
- Rebuilding the regression safety net, the Staging_Environment, the Vite build or the MySQL 8
  setup, all of which already exist from the `laravel-livewire-upgrade` spec and are reused here.
