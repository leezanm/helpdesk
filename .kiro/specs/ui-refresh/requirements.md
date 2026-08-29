# Requirements — UI/UX Refresh

## Introduction

The KKDW Helpdesk now runs on Laravel 12 + Livewire 3 with a Bootstrap 5 admin theme
("Skote"-style, dark sidebar). This spec covers a visual/UX refresh to make the system look
fresh, professional, and smart, with a new color palette, a modernized layout/arrangement, a
redesigned dashboard, and first-class mobile responsiveness.

Decisions already made (with the user):
- **Keep Bootstrap 5** (restyle in place; no Tailwind rewrite).
- **Fresh custom theme** via CSS design tokens (professional palette; government/ministry tone).
- **Light mode** now, with dark-ready tokens for a future toggle.
- **Mobile-first**: excellent on phones/tablets, not just desktop.
- **Pilot-first**: redesign the dashboard first, review, then roll out.

This refresh is **behavior-preserving**: it changes look/layout, not business logic. All Livewire
components, Actions, routes, and the 62-test suite continue to work.

## Requirements

### Requirement 1 — Design system foundation

**User Story:** As a maintainer, I want a token-based theme, so that colors/spacing/typography are
consistent and changeable in one place.

#### Acceptance Criteria
1. WHEN the theme is defined THEN a palette, typography scale, spacing, radius, and shadow SHALL be expressed as CSS custom properties (design tokens) in the Vite-built stylesheet.
2. WHEN tokens are applied THEN they SHALL layer over Bootstrap 5 (via CSS variables / overrides) without replacing the framework.
3. WHEN the theme loads THEN it SHALL be delivered through the existing `@vite` pipeline.
4. WHERE a future dark mode is wanted THEN tokens SHALL be structured to allow a theme switch without markup changes.

### Requirement 2 — Modern, mobile-first app shell

**User Story:** As a user on any device, I want the navigation and layout to look modern and work well on mobile, so that the system is pleasant to use on a phone.

#### Acceptance Criteria
1. WHEN viewed on desktop THEN the sidebar + topbar SHALL present a refreshed, professional look (clear active states, modern spacing, the new palette).
2. WHEN viewed on a phone THEN the sidebar SHALL collapse into an off-canvas/toggle menu with touch-friendly targets.
3. WHEN content is displayed on small screens THEN layout SHALL reflow (cards stack, spacing adapts) without horizontal overflow.
4. WHEN role-gated menu items render THEN the existing permission gating SHALL be preserved exactly.

### Requirement 3 — Redesigned dashboard (pilot)

**User Story:** As a user, I want an at-a-glance dashboard that surfaces what matters for my role, so that I can act quickly.

#### Acceptance Criteria
1. WHEN the dashboard loads THEN it SHALL show modern stat cards relevant to the viewer's role (admin / technical officer / end user).
2. WHEN the dashboard renders charts THEN they SHALL be responsive and use the new palette.
3. WHEN there is recent activity THEN the dashboard SHALL show a recent-reports/actions list with quick links.
4. WHEN the dashboard is built THEN it SHALL be a Livewire component (behavior-preserving of the current data), verified by a feature test.
5. WHEN viewed on mobile THEN dashboard cards/charts SHALL reflow to 1–2 columns cleanly.

### Requirement 4 — Roll the theme across converted screens

**User Story:** As a user, I want a consistent look across all screens, so that the system feels cohesive.

#### Acceptance Criteria
1. WHEN the theme is applied THEN the Livewire screens (Kategori, Status, Pengguna, Peranan, Aduan) SHALL adopt the new styling for tables, forms, modals, badges, and buttons.
2. WHEN tables are shown on mobile THEN they SHALL degrade gracefully (responsive/stacked), avoiding cramped columns.
3. WHEN a screen is restyled THEN its behavior and passing tests SHALL be unchanged.

### Requirement 5 — Quality, accessibility, and verification

**User Story:** As a maintainer, I want the refresh verified and accessible, so that it ships safely.

#### Acceptance Criteria
1. WHEN the refresh is complete THEN the full test suite SHALL remain green.
2. WHEN the UI is reviewed THEN color contrast SHALL meet reasonable accessibility (WCAG AA-oriented) for text/controls.
3. WHEN the refresh is verified THEN it SHALL be checked on staging at phone/tablet/desktop breakpoints.
4. WHEN the pilot dashboard is done THEN the user SHALL review and approve the direction before the full roll-out proceeds.

## Out of scope

- Switching CSS framework to Tailwind.
- Changing business logic, data, routes, or permissions.
- Redesigning the dashboard/laporan chart *data* pipeline (charts restyle only).
- Full UX flow redesign beyond the dashboard (arrangement/visual refresh only).
