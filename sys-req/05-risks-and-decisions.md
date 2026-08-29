# 05 — Risks, Blockers & Decisions Needed

## A. Hard blockers (must resolve to upgrade at all)

| # | Blocker | Impact | Resolution |
|---|---------|--------|------------|
| B1 | **`adldap2/adldap2` is abandoned** | Will not run on Laravel 10+. AD auth code (currently commented) depends on it. | Migrate to `directorytree/ldaprecord-laravel`. Decide whether LDAP/AD login is actually still needed. |
| B2 | `fruitcake/laravel-cors`, `fideloper/proxy` | Removed/absorbed into framework; break composer resolution on newer Laravel. | Remove; use built-in CORS + TrustProxies. |
| B3 | `facade/ignition` | Not compatible with Laravel 10+. | Replace with `spatie/laravel-ignition`. |
| B4 | `"minimum-stability": "dev"` | Pulls unstable deps; unpredictable upgrades. | Set to `stable`. |

## B. High risks

| # | Risk | Mitigation |
|---|------|------------|
| R1 | **No/low test coverage** on a large 4-major-version jump. | Write smoke/feature tests in Phase 0 *before* upgrading (login, aduan lifecycle, upload, laporan, backup). |
| R2 | **MySQL 8 `ONLY_FULL_GROUP_BY`** breaks aggregate `laporan` queries. | Audit all report/aggregate queries against MySQL 8 in staging in Phase 1. |
| R3 | **Business logic embedded in controllers** (file storage, status transitions, notifications). | Extract to Action/Service classes during conversion so Livewire and controllers share it. |
| R4 | **HTML built inside controllers** returned as JSON. | Not portable; rebuild as Blade/`App\Support` helpers. |
| R5 | **Custom `username`/2FA/LDAP auth flow** via Fortify. | Re-verify after each Laravel step; cover with a login feature test. |
| R6 | **Audit-trail middleware** behavior under Livewire's XHR requests. | Explicitly test that audit logging still fires (or is intentionally skipped) for Livewire updates. |
| R7 | **Performance** — Livewire adds server round-trips vs. static Blade. | Use `wire:model.blur`/`.debounce`, `wire:poll` sparingly, lazy loading; load test in Phase 5. |
| R8 | **Large data tables** currently on DataTables (client-side). | Livewire pagination is server-side — confirm UX (sorting/search) meets expectations before removing DataTables. |

## C. Decisions you need to make

1. **Laravel 12 vs 13 as the initial target.**
   - Recommendation: **12 first**, then 13 as a small follow-up. Pick 13 immediately only if no package blockers and you want max support window.
2. **MySQL 8 vs MariaDB 10.11+.** Follow your DBA/hosting standard; both are supported. Recommendation: **MySQL 8.0** unless you already run MariaDB.
3. **Is LDAP/Active Directory login still required?**
   - It is currently commented out. If it's dead, dropping it removes blocker B1 entirely. If it's needed, budget time for the LdapRecord re-implementation.
4. **Bootstrap vs Tailwind.** Recommendation: **keep Bootstrap 5** to avoid a full re-theme.
5. **Livewire 3 vs 4.** Recommendation: **3** during migration; consider 4 later.
6. **How much of Laporan/dashboard to convert.** Charts can stay on the existing API + ApexCharts to time-box the project; convert only if you want the interactivity.
7. **Big-bang vs incremental.** Recommendation: **incremental/hybrid** (Blade + Livewire coexist). Big-bang is not advised for an app this size without strong test coverage.

## D. Things that carry over unchanged (low risk)

- Domain models and relationships.
- `Models/Ref` reference tables.
- spatie roles/permissions data and `can:` route gates.
- Database notifications (only rendering moves to Livewire).
- Feature-based folder organization and route naming conventions.
- Bahasa Malaysia UI copy and `laravel-lang` translations.

## E. Recommended first concrete step

Before any framework change: **write the Phase 0 feature tests and stand up staging on the new PHP + MySQL versions.** Everything else depends on having that regression safety net and a place to validate each step.
