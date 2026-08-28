<?php

namespace App\Livewire\Role;

use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * Role listing with search (replaces the DataTables role page).
 *
 * Roles are seeded reference data whose permissions are managed via the
 * seeders; this screen is read-only (matches the prior UI, whose create/edit
 * modals were non-functional stubs). The "lihat" link goes to role.show.
 */
class RoleIndex extends Component
{
    public string $search = '';

    public function render()
    {
        $roles = Role::query()
            ->with('permissions')
            ->when($this->search !== '', function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('desc', 'like', '%'.$this->search.'%');
            })
            ->orderBy('name')
            ->get();

        return view('livewire.role.role-index', [
            'roles' => $roles,
        ]);
    }
}
