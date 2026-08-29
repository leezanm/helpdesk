# Implementation Plan — UI/UX Refresh

Pilot-first: build tokens + dashboard, get sign-off, then roll out. Behavior-preserving; keep the
full test suite green throughout.

- [ ] 1. Design-system foundation (tokens)
  - [ ] 1.1 Define palette/typography/spacing/radius/shadow as CSS custom properties in `resources/css/app.css`, layered over Bootstrap 5 variables; wire via `@vite`
    - Light theme now; parallel `[data-theme="dark"]` token block reserved for later.
    - _Requirements: 1.1, 1.2, 1.3, 1.4_
  - [ ] 1.2 Add typography (web font) and base element styling (bg, surfaces, text, links, buttons)
    - _Requirements: 1.1, 5.2_

- [ ] 2. Redesigned dashboard (PILOT)
  - [ ] 2.1 Create `App\Livewire\Dashboard\Dashboard` reusing the current role-aware queries (behavior-preserving); trim DashboardController to a wrapper
    - _Requirements: 3.1, 3.4_
  - [ ] 2.2 Build modern, responsive stat cards + quick actions (role-aware: admin/technical/user)
    - _Requirements: 3.1, 3.5_
  - [ ] 2.3 Restyle charts (ApexCharts) to the new palette; make responsive
    - _Requirements: 3.2, 3.5_
  - [ ] 2.4 Add recent-activity list with status badges + quick links to show
    - _Requirements: 3.3_
  - [ ] 2.5 Add a Livewire feature test for the Dashboard; verify on staging + mobile breakpoints
    - _Requirements: 3.4, 5.1, 5.3_

- [ ] 3. REVIEW GATE — present the pilot, get go/no-go
  - [ ] 3.1 Show the dashboard + tokens on staging (desktop + phone); user approves direction before roll-out
    - _Requirements: 5.4_

- [ ] 4. Modern, mobile-first app shell (after sign-off)
  - [ ] 4.1 Restyle sidebar (tokens, active states) preserving all `@can/@canany` gating verbatim
    - _Requirements: 2.1, 2.4_
  - [ ] 4.2 Restyle topbar (notification bell + profile) with the new palette
    - _Requirements: 2.1_
  - [ ] 4.3 Mobile: off-canvas sidebar + hamburger; touch-friendly targets; no horizontal overflow
    - _Requirements: 2.2, 2.3_

- [ ] 5. Roll the theme across converted screens
  - [ ] 5.1 Shared styling for tables/forms/modals/badges/buttons via tokens (Kategori, Status, Pengguna, Peranan, Aduan)
    - _Requirements: 4.1, 4.3_
  - [ ] 5.2 Responsive tables: `.table-responsive` + stacked-card rendering for the busiest tables (Aduan index, Pengguna) on small screens
    - _Requirements: 4.2_
  - [ ] 5.3 Verify un-converted Blade screens (laporan, superadmin audit/backup) still look correct under the new tokens
    - _Requirements: 4.3, 5.3_

- [ ] 6. Polish & verify
  - [ ] 6.1 Contrast/accessibility pass (WCAG AA-oriented) for text/controls
    - _Requirements: 5.2_
  - [ ] 6.2 Full responsive verification (phone/tablet/desktop) on staging; full test suite green
    - _Requirements: 5.1, 5.3_
