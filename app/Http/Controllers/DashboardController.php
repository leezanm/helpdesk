<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

/**
 * Dashboard — role-aware. Each user is shown the dashboard appropriate to their
 * role (all Livewire components mounted by their wrapper view):
 *
 *   - Management (aduan.adminTask OR dashboard.viewGraph OR view.report):
 *     the full "Dashboard Utama" — org-wide stats. This is helpdesk admin,
 *     penyelia, pengurusan and superadmin.
 *   - Officer (aduan.updateTask, e.g. jtk/pptm/ptm): "Dashboard Pegawai" —
 *     only the aduan assigned to them.
 *   - Everyone else: "Dashboard Pengguna" — only their own aduan.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($this->canSeeMainDashboard($user)) {
            return view('dashboard');
        }

        if ($user->can('aduan.updateTask')) {
            return view('dashboard-officer');
        }

        return view('dashboard-user');
    }

    /**
     * Main (org-wide) dashboard is limited to management roles: pengurusan,
     * helpdesk admin, penyelia and superadmin.
     */
    private function canSeeMainDashboard($user): bool
    {
        return $user->canAny(['aduan.adminTask', 'dashboard.viewGraph', 'view.report']);
    }
}
