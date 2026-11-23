<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

/**
 * Dashboards.
 *
 * After login everyone lands on their PERSONAL dashboard (/dashboard):
 *   - Officer (aduan.updateTask, e.g. jtk/ptm/pptm) -> Dashboard Pegawai
 *     (only aduan assigned to them).
 *   - Everyone else -> Dashboard Pengguna (only their own aduan).
 *
 * The org-wide "Dashboard Utama" is a SEPARATE page (/dashboard/utama),
 * reachable only by management roles (superadmin, helpdesk.admin,
 * pengurusan) via the `access-main-dashboard` gate. Management still land on
 * their personal dashboard first and open the main one from the menu.
 */
class DashboardController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('aduan.updateTask')) {
            return view('dashboard-officer');
        }

        return view('dashboard-user');
    }

    public function main()
    {
        $this->authorize('access-main-dashboard');

        return view('dashboard');
    }
}
