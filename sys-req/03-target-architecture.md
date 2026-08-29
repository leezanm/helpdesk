# 03 — Target Architecture (post-migration)

This describes the proposed structure after upgrading to Laravel 12 + Livewire 3.
It is a **suggested design**, not a prescription — adjust naming to your team's conventions.

## 1. High-level shape

```
Browser (Bootstrap 5 theme + Alpine.js)
   │  Livewire AJAX (wire:model, wire:click)  ← replaces hand-written jQuery/AJAX
   ▼
Livewire Components  ──uses──►  Form Objects / Actions / Services (business logic)
   │                                   │
   │ render()                          ▼
   ▼                              Eloquent Models  ──►  MySQL 8.0
Blade component views (feature-scoped)
```

- **Thin controllers** stay only for non-interactive endpoints (file downloads, backups, redirects, webhooks).
- **Interactive CRUD screens become Livewire components.** No more JSON-returning controllers + jQuery.
- **Charts/reports** can stay as API endpoints feeding ApexCharts, OR become Livewire components that pass data to Alpine — either is fine; keep the API approach initially to limit scope.

## 2. Proposed folder layout

```
app/
├── Livewire/                         ← NEW: all Livewire components
│   ├── Aduan/
│   │   ├── ReportIndex.php           (list + search + pagination; replaces DataTables page)
│   │   ├── ReportForm.php            (create/edit; replaces AJAX form + modal)
│   │   ├── ReportShow.php
│   │   └── ReportTrailPanel.php
│   ├── Category/
│   │   ├── CategoryIndex.php
│   │   └── CategoryForm.php          (replaces category/_modal/*)
│   ├── Status/…
│   ├── Division/…
│   ├── Role/…
│   ├── User/
│   │   ├── UserIndex.php
│   │   ├── UserForm.php
│   │   └── UserAssetManager.php
│   ├── Laporan/…                     (report/analytics screens)
│   └── Shared/
│       ├── ConfirmModal.php
│       └── NotificationBell.php      (replaces the @php notifications block in layout)
│
├── Actions/                          ← business logic extracted from controllers
│   ├── Reports/CreateReport.php
│   ├── Reports/TransitionReportStatus.php
│   ├── Reports/StoreAttachment.php
│   └── Users/CreateUser.php
│
├── Http/Controllers/                 ← SHRINKS: only non-interactive endpoints remain
│   ├── BackupController.php          (fix typo from BackupContoller)
│   ├── DashboardController.php       (or convert to Livewire)
│   └── Api/…                         (chart data endpoints, if kept)
│
├── Models/                           ← unchanged domain models
└── Support/                          ← view-model helpers (e.g. status → badge) that used to be inline HTML
    └── StatusBadge.php

resources/
├── views/
│   ├── layouts/app.blade.php         ← updated: @livewireStyles / @livewireScripts, @vite
│   ├── livewire/                     ← NEW: component Blade views mirroring app/Livewire
│   │   ├── aduan/report-index.blade.php
│   │   └── …
│   └── components/                   ← Blade UI components (buttons, cards, modal shell)
├── css/app.css
└── js/app.js                          ← Alpine + Livewire entry (via Vite)

vite.config.js                         ← replaces webpack.mix.js
```

## 3. How current patterns map to Livewire

| Today | Becomes |
|-------|---------|
| DataTables list page + AJAX search | Livewire `Index` component with `wire:model.live.debounce` search, `WithPagination`, sortable headers |
| Bootstrap modal + AJAX `store`/`update` returning JSON | Livewire `Form` component, `wire:submit`, server-side validation surfaced via `$errors` automatically |
| Hand-written `errorValidate()` JS | Livewire real-time validation (`rules()` / `#[Validate]`) — delete the custom JS |
| HTML badge strings built in controller (`ReportCategoryController::show`) | `App\Support\StatusBadge` + a Blade partial, or a computed property in the component |
| `response()->json(['success'=>true])` + `window.location.reload()` | `$this->dispatch(...)` events + Livewire re-render; no full reload |
| Notification bell `@php {...} @endphp` block in layout | `NotificationBell` Livewire component with `wire:poll` |
| File upload via multipart AJAX | Livewire `WithFileUploads` + `wire:model` on `<input type=file>` |

## 4. Coexistence strategy (important)

You do **not** rewrite everything at once. Livewire components mount inside existing Blade layouts:

```blade
{{-- resources/views/aduan/index.blade.php (kept as a thin wrapper) --}}
@extends('layouts.app')
@section('content')
    @livewire('aduan.report-index')
@endsection
```

This lets you migrate **one screen at a time** while the rest of the app keeps working on Blade/jQuery.
Migrate in order of value/risk (see doc 04).

## 5. Cross-cutting concerns (unchanged or lightly touched)

- **Auth (Fortify):** login stays; the custom `username` login and 2FA columns carry over. Re-wire LDAP with LdapRecord.
- **Authorization (spatie):** `can:` middleware on routes still works; inside Livewire use `$this->authorize(...)` or `@can` in views.
- **Audit trail middleware:** keep as-is; it operates at the HTTP layer and still fires for Livewire's own requests (validate this during migration).
- **Notifications:** database channel unchanged; only the *rendering* moves into a Livewire component.
- **Backup:** controller stays (non-interactive downloads); just fix the class name typo and bump the package.
