<?php

namespace App\Livewire\Role;

use App\Actions\Roles\DeleteRole;
use App\Support\SystemRoles;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * Selenggara peranan: senarai + carian, dengan tambah/kemaskini (RoleForm) dan
 * padam. Semua tindakan memerlukan `manage.role`. Pautan "lihat" ke role.show.
 */
class RoleIndex extends Component
{
    public string $search = '';

    public ?string $message = null;
    public ?string $error = null;

    public function create(): void
    {
        $this->authorize('manage.role');
        $this->dispatch('role-create');
    }

    public function edit(int $id): void
    {
        $this->authorize('manage.role');
        $this->dispatch('role-edit', id: $id);
    }

    public function delete(int $id, DeleteRole $deleteRole): void
    {
        $this->message = $this->error = null;
        $role = Role::findOrFail($id);

        try {
            $deleteRole->handle(auth()->user(), $role);
            $this->message = 'Peranan berjaya dipadam.';
        } catch (ValidationException $e) {
            $this->error = $e->validator->errors()->first();
        }
    }

    #[On('role-saved')]
    public function onSaved(): void
    {
        $this->error = null;
        $this->message = 'Peranan berjaya disimpan.';
    }

    public function render()
    {
        $roles = Role::query()
            ->with('permissions')
            ->withCount('users')
            ->when($this->search !== '', function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('desc', 'like', '%'.$this->search.'%');
            })
            ->orderBy('name')
            ->get();

        return view('livewire.role.role-index', [
            'roles' => $roles,
            'systemRoles' => SystemRoles::NAMES,
        ]);
    }
}
