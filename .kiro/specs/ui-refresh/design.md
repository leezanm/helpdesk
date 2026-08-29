# Design — UI/UX Refresh

## Overview

A token-driven visual refresh of the KKDW Helpdesk on **Bootstrap 5** (kept), delivering a fresh,
professional, mobile-first look with a redesigned role-aware dashboard. Behavior-preserving: only
markup styling and layout change; Livewire components, Actions, routes, and permissions are untouched.

Strategy: **pilot-first.** Build the design tokens + redesigned dashboard, get user sign-off, then
roll the theme across the already-converted Livewire screens.

## Design tokens (Requirement 1)

Defined as CSS custom properties in `resources/css/app.css` (Vite entry), layered over Bootstrap by
overriding its CSS variables and adding a thin theme layer.

Proposed professional palette (government/ministry tone — trust + clarity):
- **Primary:** deep teal-blue (e.g. `#0f6e8c` / hsl around 195°) — headers, primary actions
- **Accent:** warm amber for highlights/CTAs sparingly
- **Neutrals:** cool grays for surfaces/text; near-white app background (`#f6f8fb`), white cards
- **Status colors:** success/green, warning/amber, danger/red, info/teal — mapped to the existing
  report status classes so badges stay meaningful
- Tokens: `--app-bg`, `--surface`, `--text`, `--text-muted`, `--primary`, `--primary-contrast`,
  `--radius`, `--shadow-sm/md`, `--sidebar-bg`, `--sidebar-fg`, spacing scale.

Dark-ready: tokens grouped under a `:root` (light) block with a parallel `[data-theme="dark"]`
block reserved for later — no markup changes needed to switch.

Typography: a clean system/Google font (e.g. Inter/Figtree) via the token stylesheet; consistent
heading scale and comfortable line-height.

## App shell (Requirement 2)

Restyle `resources/views/layouts/app.blade.php` + `navbar.blade.php`:
- **Sidebar:** lighter, modern treatment using tokens; clearer active/hover states; keep the
  existing metismenu structure and all `@can/@canany` gating verbatim.
- **Topbar:** refined spacing, the existing Livewire notification bell + profile dropdown restyled.
- **Mobile:** sidebar becomes off-canvas (Bootstrap offcanvas or the theme's existing toggle),
  hamburger in the topbar; touch-friendly tap targets; content container padding adapts.
- No structural route/permission changes — purely presentational.

## Redesigned dashboard (Requirement 3) — the pilot

Convert the current `DashboardController@index` (stat counts + a reports list, plus chart API
endpoints) into a Livewire component `App\Livewire\Dashboard\Dashboard`:
- **Data:** reuse the exact queries from the controller (role-aware: admin sees submitted/received,
  technical sees assigned, user sees own open reports). Behavior-preserving.
- **Stat cards:** modern cards (icon, value, label, subtle trend/context) — role-relevant metrics
  (e.g. open, in-progress, resolved, my pending actions).
- **Charts:** keep ApexCharts (already loaded), restyled to the new palette, responsive.
- **Recent activity:** a compact list of recent reports with status badges + quick links to show.
- **Quick actions:** e.g. "Cipta Aduan", "Untuk Tindakan" shortcuts by role.
- **Responsive:** cards 4→2→1 columns across desktop/tablet/phone.
- The `dashboard.blade.php` becomes a thin wrapper mounting the component (consistent with the
  rest of the app). Controller trims to render the wrapper.

## Roll-out to converted screens (Requirement 4)

Because these are Livewire components already, roll-out is styling only:
- Shared partials/classes for tables, form controls, modals, badges, buttons using the tokens.
- Responsive table strategy: wrap in `.table-responsive`; on very small screens, prefer stacked
  card rendering for the busiest tables (Aduan index, Pengguna) where it improves UX.
- Reuse `App\Support\StatusBadge` styling with the new status token colors.

## Testing & verification (Requirement 5)

- Add a Livewire feature test for the Dashboard component (renders, role-aware data present).
- Keep the full suite green after each change.
- Manual/staging verification at phone/tablet/desktop breakpoints.
- Contrast check for text/controls against the new palette (WCAG AA-oriented). Note: full WCAG
  validation needs manual assistive-tech testing and expert review; this spec targets reasonable
  AA contrast, not a formal audit.

## Risks / notes

- The theme must not break the un-converted Blade screens (dashboard-adjacent laporan, superadmin
  audit/backup) that still use DataTables/charts — token overrides are additive, so this is low risk,
  but those screens will be visually verified too.
- Chart restyling is cosmetic; the aggregate query pipeline is untouched.
- Rollback: the refresh is on a feature branch; any single screen's styling can be reverted
  independently since logic is separate from presentation.

## Rollout

Work on a branch off the current `upgrade/laravel-12` line (or a fresh `ui-refresh` branch after
that merges). Pilot (tokens + dashboard) is a reviewable checkpoint before the app-wide roll-out.
