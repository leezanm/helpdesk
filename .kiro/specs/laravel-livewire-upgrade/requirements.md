# Requirements — Laravel + Livewire Upgrade

## Introduction

The KKDW Helpdesk ("Sistem Aduan") is a complaint/report management application currently
running on **Laravel 8.83**, PHP 7.3/8.0, MySQL, with a Blade + jQuery/AJAX/DataTables UI and an
abandoned LDAP dependency. This spec covers upgrading the framework, migrating the interactive UI
to **Livewire 3**, and modernizing the runtime (PHP, MySQL, build tooling) while preserving all
existing functionality and Bahasa Malaysia UI.

The migration is **incremental and behavior-preserving**: the framework is upgraded first on the
existing UI, then screens are converted to Livewire one feature at a time, with the two approaches
coexisting throughout.

Detailed background is in `sys-req/` at the project root.

## Glossary

- **Aduan** — a complaint/report (core entity `Report`).
- **Trail** — the audit/action history attached to a report.
- **Laporan** — reporting/analytics screens.
- **Behavior-preserving** — the user-visible functionality and data are unchanged after a step.

---

## Requirements

### Requirement 1 — Establish a regression safety net

**User Story:** As a maintainer, I want automated tests and a staging environment before any
upgrade, so that every change can be validated against known-good behavior.

#### Acceptance Criteria
1. WHEN the upgrade work begins THEN the system SHALL have feature tests covering login (including custom `username` auth), creating an aduan, uploading an attachment, changing report status, viewing a laporan, and running a backup.
2. WHEN tests are run on the current Laravel 8 codebase THEN they SHALL pass, establishing the baseline.
3. WHEN the upgrade is performed THEN a staging environment mirroring the target PHP and MySQL versions SHALL be available.
4. WHEN starting THEN a full database backup and a `storage/` uploads snapshot SHALL exist.

### Requirement 2 — Modernize runtime and build tooling

**User Story:** As a developer, I want current PHP, database, and asset tooling, so that the app runs on supported, secure infrastructure.

#### Acceptance Criteria
1. WHEN the runtime is upgraded THEN PHP SHALL be pinned to `^8.3`.
2. WHEN the database is upgraded THEN it SHALL run on MySQL 8.0 (or MariaDB 10.11+ if that is the chosen standard) with `utf8mb4` charset/collation.
3. WHEN aggregate "laporan" queries run under MySQL 8 THEN they SHALL be compatible with `ONLY_FULL_GROUP_BY`.
4. WHEN assets are built THEN the project SHALL use Vite instead of Laravel Mix, and `composer.json` SHALL set `minimum-stability` to `stable`.

### Requirement 3 — Upgrade Laravel 8 → 12 (UI unchanged)

**User Story:** As a maintainer, I want the framework upgraded one major version at a time on the existing Blade UI, so that breakage is isolated and reversible.

#### Acceptance Criteria
1. WHEN each major version step (8→9→10→11→12) completes THEN the safety-net tests SHALL pass before proceeding to the next step.
2. WHEN upgrading THEN abandoned/absorbed packages (`adldap2`, `fruitcake/laravel-cors`, `fideloper/proxy`, `facade/ignition`) SHALL be replaced or removed.
3. WHEN LDAP/AD login is required THEN it SHALL be re-implemented using `directorytree/ldaprecord-laravel`; IF it is not required THEN the dependency SHALL be removed and the decision documented.
4. WHEN the upgrade completes THEN the app SHALL run on Laravel 12 with the existing Blade + jQuery UI and green tests.
5. WHEN authorization is exercised THEN existing spatie roles/permissions and `can:` route gates SHALL continue to function unchanged.

### Requirement 4 — Introduce Livewire alongside Blade

**User Story:** As a developer, I want Livewire 3 installed and coexisting with the current UI, so that screens can be migrated incrementally.

#### Acceptance Criteria
1. WHEN Livewire is installed THEN `@livewireStyles`/`@livewireScripts` SHALL be wired into the main layout via Vite.
2. WHEN a Livewire component is mounted inside an existing Blade layout THEN it SHALL render and interact correctly alongside the Bootstrap 5 theme and Alpine.js.
3. WHEN a Livewire request is processed THEN the audit-trail and auth middleware behavior SHALL be verified (fires or is intentionally skipped, as decided).

### Requirement 5 — Migrate interactive screens to Livewire

**User Story:** As a user, I want the CRUD and interactive screens to behave the same as before, so that the migration is invisible to me.

#### Acceptance Criteria
1. WHEN a screen is converted THEN its business logic SHALL be extracted into Action/Service classes shared by any remaining controllers.
2. WHEN a converted screen handles forms THEN validation SHALL be server-side via Livewire (`rules()`), and the corresponding hand-written AJAX/`errorValidate` JS SHALL be removed.
3. WHEN a converted list screen is used THEN search, sorting, and pagination SHALL work server-side and match prior behavior.
4. WHEN a converted screen previously returned HTML built in a controller (e.g. status badges) THEN that markup SHALL be produced by Blade/`App\Support` helpers instead.
5. WHEN the Aduan flow is converted THEN create/edit (with attachments via `WithFileUploads`), show + trail, status transitions, and feedback SHALL preserve existing behavior.
6. WHEN the notification bell is converted THEN it SHALL display unread database notifications and update without a full page reload.
7. WHEN a screen is fully migrated THEN its now-dead JSON controller methods and jQuery SHALL be removed.

### Requirement 6 — Cleanup and hardening

**User Story:** As a maintainer, I want dead code removed and the codebase hardened, so that the result is maintainable.

#### Acceptance Criteria
1. WHEN migration completes THEN unused frontend libraries (jQuery/DataTables/dropzone, etc. no longer referenced) SHALL be removed while still-used chart libraries are kept.
2. WHEN cleaning up THEN the `BackupContoller` typo SHALL be corrected to `BackupController` and global `use Str;` replaced with `use Illuminate\Support\Str;`.
3. WHEN cleaning up THEN the duplicated `laporan` view trees SHALL be consolidated.
4. WHEN converted screens are load-tested THEN Livewire round-trip performance SHALL be within acceptable limits for large tables.
5. WHEN the project is stable on Laravel 12 THEN an optional upgrade to Laravel 13 MAY be performed as a follow-up, and `README.md` SHALL document the new stack.

---

## Out of scope

- Switching CSS framework from Bootstrap to Tailwind.
- Adopting Livewire 4 during the initial migration (Livewire 3 is the target).
- Functional/feature changes beyond preserving existing behavior.
- Full conversion of Laporan/dashboard charts is optional; they may remain on the existing API + ApexCharts.
