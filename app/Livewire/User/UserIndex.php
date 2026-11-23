<?php

namespace App\Livewire\User;

use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

/**
 * User listing with search + pagination (replaces the DataTables page + jQuery
 * AJAX). One component serves all four legacy index variants via $scope:
 *   'pengguna'   -> ordinary end-users (exclude staff/admin roles)
 *   'teknikal'   -> users with the aduan.updateTask permission
 *   'pentadbir'  -> users with the manage.user permission
 *   'pengurusan' -> users with the 'pengurusan' role
 *
 * Create/edit are delegated to UserForm via events; deactivate/restore here.
 */
class UserIndex extends Component
{
    use WithPagination;

    public string $scope = 'pengguna';
    public string $title = 'Pengguna';
    public string $search = '';
    public string $bahagian = '';

    public function mount(string $scope = 'pengguna'): void
    {
        $this->scope = $scope;
        $this->title = match ($scope) {
            'teknikal' => 'Pegawai Teknikal',
            'pentadbir' => 'Pentadbir Sistem',
            'pengurusan' => 'Pengurusan',
            default => 'Pengguna',
        };
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingBahagian(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'bahagian']);
        $this->resetPage();
    }

    public function create(): void
    {
        $this->dispatch('user-create');
    }

    public function edit(int $id): void
    {
        $this->dispatch('user-edit', id: $id);
    }

    #[On('user-saved')]
    public function onSaved(): void
    {
        $this->resetPage();
    }

    public function deactivate(int $id): void
    {
        $user = User::findOrFail($id);
        $user->is_active = false;
        $user->save();
    }

    public function restore(int $id): void
    {
        $user = User::findOrFail($id);
        $user->is_active = true;
        $user->save();
    }

    protected function baseQuery()
    {
        return match ($this->scope) {
            'teknikal' => User::permission('aduan.updateTask'),
            'pentadbir' => User::permission('manage.user'),
            'pengurusan' => User::role('pengurusan'),
            default => User::whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['jtk', 'pptm', 'ptm', 'helpdesk.admin', 'penyelia', 'superadmin', 'pengurusan']);
            }),
        };
    }

    public function render()
    {
        $users = $this->baseQuery()
            ->with('roles')
            ->when($this->search !== '', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('username', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->bahagian !== '', fn ($q) => $q->where('ldap_bahagian', $this->bahagian))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.user.user-index', [
            'users' => $users,
            'bahagianOptions' => User::query()
                ->whereNotNull('ldap_bahagian')->where('ldap_bahagian', '<>', '')
                ->distinct()->orderBy('ldap_bahagian')->pluck('ldap_bahagian'),
        ]);
    }
}
