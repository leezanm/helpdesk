# Helpdesk Upgrade Analysis — sys-req

This folder contains an analysis-only assessment of the current **KKDW Helpdesk** application
and a recommended plan to:

1. Upgrade Laravel (from 8 to a current supported version)
2. Migrate the UI from Blade + jQuery/AJAX to **Livewire**
3. Upgrade the database (MySQL) version

> **Nothing in the application has been changed.** These are documents and recommendations only.

## Documents

| File | Purpose |
|------|---------|
| `01-current-state-analysis.md` | What the project is today: stack, versions, architecture, risks |
| `02-upgrade-recommendation.md` | Recommended target versions and *why* (Laravel, PHP, MySQL, Livewire) |
| `03-target-architecture.md` | Proposed structure after migration (Livewire components, folders) |
| `04-migration-plan.md` | Step-by-step phased migration plan with effort estimates |
| `05-risks-and-decisions.md` | Risks, blockers, and decisions you need to make |

## TL;DR Recommendation

- **PHP:** 8.1 → **8.3+**
- **Laravel:** 8.83 → **12.x** (recommended pragmatic target) — see doc 02 for the 12-vs-13 discussion
- **UI:** Blade + jQuery/DataTables/AJAX → **Livewire 3** (+ Alpine.js, keep Bootstrap 5 theme)
- **MySQL:** → **8.0 LTS** (or MariaDB 10.11+ if that is your DBA standard)
- **Frontend build:** Laravel Mix (webpack) → **Vite**
- **Replace abandoned `adldap2/adldap2`** with **`directorytree/ldaprecord-laravel`**

Do this as an **incremental, hybrid migration** (Blade and Livewire coexisting), not a big-bang rewrite.
