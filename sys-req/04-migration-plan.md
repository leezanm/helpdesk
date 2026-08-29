# 04 — Migration Plan (phased)

A safe migration is **incremental**: upgrade the framework first on the *existing* Blade UI, get it
green, then convert screens to Livewire one feature at a time. Do **not** combine the framework
upgrade and the Livewire rewrite into one big-bang change.

> Effort estimates assume one experienced Laravel developer. Adjust for your team.

---

## Phase 0 — Safety net (before touching anything)

- [ ] Ensure the repo is committed and create an `upgrade/laravel-12` branch.
- [ ] Stand up a **staging environment** mirroring production (PHP + DB versions).
- [ ] Take a **full database backup** and snapshot of `storage/` uploads.
- [ ] Write **smoke/feature tests** for the critical paths first: login, create aduan, upload attachment, change status, view laporan, backup. These tests are your regression guard for every later phase.
- [ ] Document the current PHP and MySQL versions in production.

**Effort:** 3–5 days (mostly writing the safety tests).

---

## Phase 1 — Environment & tooling prep

- [ ] Pin PHP to **8.3** in `composer.json` (`"php": "^8.3"`).
- [ ] Set `"minimum-stability": "stable"`.
- [ ] Upgrade the DB in staging to **MySQL 8.0** (or MariaDB 10.11+). Run the app against it *before* the framework bump to isolate DB-related failures (esp. `ONLY_FULL_GROUP_BY` on the `laporan` aggregate queries).
- [ ] Remove `laravel-mix`; add **Vite** (`vite`, `laravel-vite-plugin`), create `vite.config.js`, move `resources/js/app.js` entry, replace `mix()` calls with `@vite()` in `layouts/app.blade.php`.

**Effort:** 2–4 days.

---

## Phase 2 — Framework upgrade 8 → 12 (still Blade)

Do it **one major version at a time**, running tests between each:

- [ ] **8 → 9:** replace `fruitcake/laravel-cors` (built-in CORS) and `fideloper/proxy` (built-in TrustProxies); swap `facade/ignition` → `spatie/laravel-ignition`; adopt Symfony Mailer changes.
- [ ] **9 → 10:** PHP 8.1+ enforced; type hints added across skeleton files; bump `spatie/laravel-permission` to 6, `spatie/laravel-backup`.
- [ ] **10 → 11:** new slimmed application structure is *optional* — you may keep the classic `app/Http/Kernel.php` structure to reduce churn. Bump remaining packages.
- [ ] **11 → 12:** mostly a maintenance release; smaller step.
- [ ] **Replace LDAP:** migrate `adldap2` → `directorytree/ldaprecord-laravel`; re-implement the AD auth that is currently commented out in `FortifyServiceProvider`.
- [ ] Use **Laravel Shift** or the official upgrade guides per step to catch config/skeleton diffs.
- [ ] Run the Phase 0 test suite after **each** step; fix before proceeding.

**Deliverable:** app runs on Laravel 12 with the *existing* Blade + jQuery UI, tests green.

**Effort:** 2–4 weeks (LDAP re-implementation and package breakage are the variables).

---

## Phase 3 — Introduce Livewire (no rewrites yet)

- [ ] `composer require livewire/livewire` (^3).
- [ ] Add `@livewireStyles` / `@livewireScripts` to `layouts/app.blade.php` (Vite-wired).
- [ ] Confirm Livewire, Alpine, and the existing Bootstrap theme coexist (build one throwaway "hello" component).
- [ ] Verify the **audit-trail** and **auth** middleware still fire on Livewire's internal requests.

**Effort:** 2–3 days.

---

## Phase 4 — Convert screens to Livewire (incremental, by feature)

Convert in this order (low-risk/high-repetition first, so patterns get reused):

1. **Reference CRUD (best first targets):** `Category`, `Status`, `Division`, `Type`
   - These use the classic modal + AJAX + JSON pattern (`_modal/create|edit|show`). Rebuild each as an `Index` + `Form` Livewire component. Establish your reusable modal/table/form patterns here.
2. **User management** (`user/*`, `superadmin/pengguna|pentadbir`, `UserAsset`).
3. **Roles/permissions** (`role/*`).
4. **Aduan (core, highest value + highest risk):** index (search/filter/pagination), create/edit form with attachments (`WithFileUploads`), show + trail panel, status transitions, feedback.
5. **Notifications bell** → `NotificationBell` component with `wire:poll`.
6. **Laporan / dashboard:** convert last, or leave charts on the existing API + ApexCharts if time-boxed.

For each screen:
- [ ] Extract business logic from the controller into an **Action/Service** class.
- [ ] Build the Livewire component + Blade view; mount it inside the existing `@extends('layouts.app')` wrapper.
- [ ] Move server-side validation into the component (`rules()`); **delete** the corresponding hand-written AJAX + `errorValidate` JS.
- [ ] Replace controller HTML-string builders (badges) with `App\Support` helpers / Blade partials.
- [ ] Add a feature test for the component; verify against staging.
- [ ] Remove the now-dead JSON controller methods and jQuery once the screen is fully migrated.

**Effort:** the bulk of the project. ~1–3 days per simple CRUD screen, ~1–2 weeks for the full Aduan flow. Rough total **6–12 weeks** depending on how much of Laporan is converted vs. left on the API.

---

## Phase 5 — Cleanup & hardening

- [ ] Remove jQuery/DataTables/dropzone libs that are no longer referenced (keep chart libs if still used).
- [ ] Delete dead modal partials and JSON endpoints.
- [ ] Fix `BackupContoller` → `BackupController` typo and `use Str;` → `use Illuminate\Support\Str;`.
- [ ] Consolidate the duplicated `laporan` view trees.
- [ ] Bump to **Laravel 13** (optional follow-up) once stable on 12.
- [ ] Update `README.md` with the new stack and setup instructions.
- [ ] Load test the Livewire screens (Livewire adds server round-trips vs. static pages — confirm acceptable).

**Effort:** 1–2 weeks.

---

## Suggested overall timeline

| Phase | Effort (1 dev) |
|-------|----------------|
| 0 Safety net | 3–5 days |
| 1 Tooling/DB | 2–4 days |
| 2 Laravel 8→12 | 2–4 weeks |
| 3 Add Livewire | 2–3 days |
| 4 Convert screens | 6–12 weeks |
| 5 Cleanup | 1–2 weeks |
| **Total** | **~3–5 months** |

Parallelizing Phase 4 across 2 developers (after patterns are set in the reference-CRUD screens) can cut this materially.
