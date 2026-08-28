<?php

namespace App\Http\Controllers;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Role management.
 *
 * The listing is handled by the Livewire component App\Livewire\Role\RoleIndex.
 * This controller renders the list page wrapper and the read-only show page.
 * (The previous store/update methods were non-functional stubs and were removed
 * along with their routes.)
 */
class RoleManagementController extends Controller
{
    /**
     * Display the role listing page (mounts the Livewire component).
     */
    public function index()
    {
        return view('role.index');
    }

    /**
     * Show one role: its permissions and the users holding it.
     */
    public function show(Role $role)
    {
        $users = User::role($role->name)->get();
        $permissions = $role->permissions()->pluck('desc');

        return view('role.show', compact('users', 'role', 'permissions'));
    }
}
