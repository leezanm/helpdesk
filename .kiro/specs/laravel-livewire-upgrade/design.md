# Design — Laravel + Livewire Upgrade

## Overview

This design translates the analysis in `sys-req/` into an executable technical plan. The strategy
is an **incremental, hybrid migration**: upgrade the framework first on the existing Blade UI, then
convert interactive screens to Livewire 3 one feature at a time while both approaches coexist. No
big-bang rewrite.

**Target stack:** PHP 8.3, Laravel 12, Livewire 3, Bootstrap 5 (kept), Vite (replacing Mix),
MySQL 8.0, `directorytree/ldaprecord-laravel` (replacing abandoned adldap2).

## Guiding principles

1. **Behavior-preserving** — each step keeps the app functionally identical to users.
2. **One change at a time** — never combine the framework upgrade with the Livewire rewrite.
3. **Test-gated** — the Phase 0 test suite must be green before and after each step.
4. **Coexistence** — Livewire components mount inside the existing `layouts/app.blade.php`.
5. **Logic extraction** — move business logic out of controllers into Actions/Services so both controllers and Livewire components share it.

## Target architecture

```
Browser (Bootstrap 5 theme + Alpine.js)
   │  Livewire AJAX (wire:model / wire:click)  ← replaces hand-written jQuery/AJAX
   ▼
Livewire Components ──uses──► Actions / Services (business logic)
   │ render()                       │
   ▼                                ▼
Blade component views          Eloquent Models ──► MySQL 8.0
```

### Proposed folder layout (post-migration)

```
app/
├── Livewire/            # NEW: interactive components (Aduan/, Category/, Status/, User/, Role/, Laporan/, Shared/)
├── Actions/             # NEW: extracted business logic (Reports/, Users/)
├── Support/             # NEW: view helpers (e.g. StatusBadge) replacing inline-HTML-in-controllers
├── Http/Controllers/    # SHRINKS: only non-interactive endpoints (backup downloads, chart APIs, redirects)
└── Models/              # UNCHANGED domain models

resources/
├── views/layouts/app.blade.php   # @vite + @livewireStyles/@livewireScripts
├── views/livewire/               # NEW component Blade views mirroring app/Livewire
└── views/components/             # Blade UI components (modal shell, table, form controls)

vite.config.js                    # replaces webpack.mix.js
```

### Pattern mapping (current → Livewire)

| Current | Livewire target |
|---|---|
| DataTables page + AJAX search | `Index` component: `WithPagination` + `wire:model.live.debounce` search + sortable headers |
| Bootstrap modal + AJAX store/update returning JSON | `Form` component with `wire:submit` and automatic `$errors` |
| Hand-written `errorValidate()` JS | Livewire `rules()` real-time validation (JS deleted) |
| HTML badge string built in controller | `App\Support\StatusBadge` + Blade partial / computed property |
| `response()->json(['success'=>true])` + `location.reload()` | `$this->dispatch(...)` events + component re-render |
| Notification bell `@php` block in layout | `NotificationBell` component with `wire:poll` |
| Multipart AJAX file upload | `WithFileUploads` + `wire:model` on file input |

### Coexistence mechanism

Existing Blade pages become thin wrappers that mount a component, so unmigrated screens keep working:

```blade
@extends('layouts.app')
@section('content')
    @livewire('aduan.report-index')
@endsection
```

## Component design (representative)

- **`Aduan\ReportIndex`** — properties: `search`, `statusFilter`, `categoryFilter`, `sortField`, `sortDirection`; uses `WithPagination`; computed `reports` query; authorizes `aduan.all`.
- **`Aduan\ReportForm`** — create/edit; `WithFileUploads` for attachments; `rules()`; delegates to `Actions\Reports\CreateReport` / status transition actions; dispatches a saved event.
- **`Shared\NotificationBell`** — `wire:poll.30s`; renders `Auth::user()->unreadNotifications`; mark-as-read action.
- **`Category\CategoryIndex` + `Category\CategoryForm`** — the first/reference implementation that establishes reusable modal + table + form patterns for later screens.

## Framework upgrade path (8 → 12)

Executed one major version at a time, tests gated between each:

- **8 → 9:** remove `fruitcake/laravel-cors` (built-in CORS) and `fideloper/proxy` (built-in TrustProxies); `facade/ignition` → `spatie/laravel-ignition`; Symfony Mailer migration.
- **9 → 10:** enforce PHP 8.1+; bump `spatie/laravel-permission` → 6 and `spatie/laravel-backup`.
- **10 → 11:** keep classic `app/Http/Kernel.php` structure (skip optional slim skeleton to reduce churn); bump remaining packages.
- **11 → 12:** maintenance-level step.
- **LDAP:** replace adldap2 with LdapRecord; re-implement (currently commented) AD auth, or remove if unneeded.
- Use official upgrade guides / Laravel Shift per step.

## Data / database design

- Upgrade to **MySQL 8.0** (or MariaDB 10.11+). No schema redesign; migrations carry over.
- Enforce `utf8mb4` / `utf8mb4_unicode_ci`.
- **`ONLY_FULL_GROUP_BY`** is on by default in MySQL 8 — audit all aggregate queries in `LaporanController` and dashboard API controllers; rewrite non-conforming `GROUP BY` clauses.

## Cross-cutting concerns

- **Auth (Fortify):** custom `username` login + 2FA columns carry over; re-verify after each step; LDAP re-wired via LdapRecord.
- **Authorization (spatie):** `can:` route middleware unchanged; inside components use `$this->authorize()` / `@can`.
- **Audit trail middleware:** keep; explicitly verify behavior on Livewire's XHR requests.
- **Notifications:** database channel unchanged; only rendering moves to a component.
- **Backup:** controller stays (non-interactive); fix class-name typo; bump package.

## Testing strategy

- **Phase 0 baseline:** feature tests for login, aduan create, attachment upload, status change, laporan view, backup — must pass on Laravel 8 first.
- **Per framework step:** re-run the full suite; fix before advancing.
- **Per converted screen:** add a Livewire feature test (`Livewire::test(...)`) asserting validation, list search/pagination, and save behavior; verify on staging.
- **Phase 5:** load-test Livewire screens with large datasets to confirm round-trip performance.

## Risks (see `sys-req/05-risks-and-decisions.md` for the full list)

- Abandoned adldap2 (hard blocker), MySQL 8 `ONLY_FULL_GROUP_BY` on laporan queries, low starting test coverage, business logic embedded in controllers, HTML-in-controllers, custom auth/LDAP flow, audit-trail under Livewire, and Livewire round-trip performance on large tables.

## Rollout / rollback

- Work on an `upgrade/laravel-12` branch; each phase is a reviewable checkpoint.
- Because the migration is incremental, any single converted screen can be reverted to its Blade wrapper without affecting others.
- Keep the DB backup + uploads snapshot until staging sign-off.
