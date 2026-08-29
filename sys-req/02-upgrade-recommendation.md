# 02 — Upgrade Recommendation (target versions + rationale)

## Summary of recommended targets

| Component | From | **Recommended target** | Alternative |
|-----------|------|------------------------|-------------|
| PHP | 7.3/8.0 | **8.3** | 8.4 |
| Laravel | 8.83 | **12.x** | 13.x (newest) |
| UI layer | Blade + jQuery/AJAX | **Livewire 3** | Livewire 4 |
| Frontend build | Laravel Mix (webpack) | **Vite** | — |
| CSS framework | Bootstrap 5 | **Keep Bootstrap 5** | Tailwind (bigger rewrite) |
| MySQL | (unpinned) | **MySQL 8.0** | MariaDB 10.11+ |
| LDAP | adldap2 (abandoned) | **directorytree/ldaprecord-laravel** | — |
| Auth | Fortify 1.x | **Fortify (current)** | keep |
| Permissions | spatie 4.x | **spatie 6.x** | keep |
| Backup | spatie/laravel-backup 6.x | **spatie/laravel-backup 8.x/9.x** | keep |

> Versions verified against public release info in mid-2026: Laravel 13 was released
> March 2026 and requires PHP 8.3+; Laravel 12 is the prior stable line; Livewire 3 (and
> the newer Livewire 4) both require PHP 8.1+.
> *Content was rephrased for compliance with licensing restrictions.*
> Sources: [Laravel 13 release notes](https://laravel.com/docs/13.x/releases),
> [Laravel 12 docs](https://laravel.com/docs/12.x),
> [Livewire upgrade guide](https://livewire.laravel.com/docs/3.x/upgrading).

## Why Laravel 12 (not straight to 13)

**Recommended: land on Laravel 12 first.**

- The jump is **8 → 9 → 10 → 11 → 12** (four major versions). Each has breaking changes; going one step further to 13 adds risk on top of an already large migration.
- Laravel 12 has broad ecosystem/package support today; some third‑party packages may still be catching up to 13.
- Livewire 3 is fully supported on Laravel 12.
- Once you are stable on 12 with tests green, the **12 → 13** step is small and can be a follow-up.

**Choose 13 immediately only if** you have no package blockers and want the longest support window from day one. Laravel 13 requires PHP 8.3 minimum.

Either way, **target PHP 8.3** (your dev box already runs 8.5, so tooling is fine — just pin production to 8.3+).

## Why Livewire 3 (not 4) for the migration

- Livewire 3 is the mature, best-documented, most-tutorial-covered version — ideal while your team is *learning* the paradigm during a migration.
- It bundles Alpine.js and has first-class support for exactly the patterns you need (tables, modals, forms, file uploads, real-time validation).
- Livewire 4 is newer; adopt it later once the app is fully on Livewire and stable.

## Why keep Bootstrap 5 (don't switch to Tailwind)

- Your existing admin theme, layouts, modals, and 40+ vendor UI libs are Bootstrap-based.
- Livewire is CSS-framework agnostic — it works perfectly with Bootstrap.
- Switching to Tailwind would multiply the effort (re-theming every screen) for no functional gain. Keep Bootstrap; you can adopt Tailwind later if desired.

## Frontend build: Mix → Vite

- Laravel 9.19+ ships with Vite as the default; Mix is legacy.
- Vite gives faster builds and HMR, and is what Livewire/Laravel docs assume.
- This is a required-ish change because staying on Mix fights the framework defaults on Laravel 10+.

## Database upgrade

- **MySQL 8.0** is recommended (well supported by Laravel 12, long support window, better JSON/CTE/window-function support).
- If your infrastructure standardizes on **MariaDB**, target **10.11 LTS+** — Laravel supports it, but confirm any MySQL‑8‑specific SQL you add later.
- Action items regardless of engine:
  - Pin `DB_` charset to `utf8mb4` / `utf8mb4_unicode_ci` (Laravel default) to avoid emoji/collation issues.
  - Review any raw queries for MySQL‑8 reserved-word or `ONLY_FULL_GROUP_BY` strictness (MySQL 8 enables it by default — your `laporan` aggregate reports are the place to check).

## Packages that must be swapped or bumped

| Package | Action |
|---------|--------|
| `adldap2/adldap2-laravel` | **Replace** with `directorytree/ldaprecord-laravel` (successor by same author). Re-implement the (currently commented) AD auth in the new API. |
| `fruitcake/laravel-cors` | **Remove** — CORS is built into Laravel framework since 9. |
| `fideloper/proxy` | **Remove** — TrustProxies is built into the framework now. |
| `facade/ignition` | **Replace** with `spatie/laravel-ignition`. |
| `laravel/framework` | 8 → **12** |
| `laravel/fortify` | bump to current |
| `spatie/laravel-permission` | 4 → **6** |
| `spatie/laravel-backup` | 6 → **8/9** |
| `laravel/sail`, `nunomaduro/collision`, `phpunit/phpunit`, `fakerphp/faker` | bump to Laravel‑12‑compatible versions |
| `laravel-mix` | **Remove**, replace with `vite` + `laravel-vite-plugin` |
| `livewire/livewire` | **Add** (^3) |

Also set `"minimum-stability": "stable"` in `composer.json`.
