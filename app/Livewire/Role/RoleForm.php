<?php

namespace App\Livewire\Role;

use App\Actions\Roles\SaveRole;
use App\Support\SystemRoles;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Borang tambah / kemaskini peranan (modal). `name` hanya ditetapkan semasa
 * cipta. Peranan sistem boleh disunting label dan kebenarannya sahaja.
 */
class RoleForm extends Component
{
    public ?int $roleId = null;
    public string $name = '';
    public string $desc = '';
    /** @var array<int,string> */
    public array $permissions = [];
    public bool $showModal = false;

    protected function rules(): array
    {
        return [
            'name' => [
                Rule::requiredIf(! $this->roleId),
                'nullable', 'string', 'max:50', 'regex:/^[a-z0-9]+([._-][a-z0-9]+)*$/',
                Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($this->roleId),
            ],
            'desc' => 'required|string|max:100',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }

    protected array $messages = [
        'name.required' => 'Nama kod wajib diisi.',
        'name.regex' => 'Nama kod hanya huruf kecil, nombor, titik, sengkang atau garis bawah.',
        'name.unique' => 'Nama kod ini sudah digunakan.',
        'desc.required' => 'Nama peranan wajib diisi.',
    ];

    #[On('role-create')]
    public function openCreate(): void
    {
        $this->authorize('manage.role');
        $this->reset(['roleId', 'name', 'desc', 'permissions']);
        $this->resetErrorBag();
        $this->showModal = true;
    }

    #[On('role-edit')]
    public function openEdit(int $id): void
    {
        $this->authorize('manage.role');
        $role = Role::findOrFail($id);
        abort_if($role->name === 'superadmin' && ! auth()->user()->hasRole('superadmin'), 403);

        $this->roleId = $role->id;
        $this->name = $role->name;
        $this->desc = (string) $role->desc;
        $this->permissions = $role->permissions->pluck('name')->all();
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function save(SaveRole $saveRole): void
    {
        $this->authorize('manage.role');
        $this->validate();

        $role = $this->roleId ? Role::findOrFail($this->roleId) : null;

        $saveRole->handle(auth()->user(), $role, [
            'name' => $this->name,
            'desc' => $this->desc,
            'permissions' => $this->permissions,
        ]);

        $this->closeModal();
        $this->dispatch('role-saved');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['roleId', 'name', 'desc', 'permissions']);
        $this->resetErrorBag();
    }

    public function render()
    {
        $actor = auth()->user();
        $editing = $this->roleId ? Role::find($this->roleId) : null;

        return view('livewire.role.role-form', [
            'allPermissions' => Permission::orderBy('name')->get(),
            // Kebenaran yang tidak dipegang pengguna ini tidak boleh diubah olehnya.
            'ownPermissions' => $actor->hasRole('superadmin')
                ? Permission::pluck('name')->all()
                : $actor->getAllPermissions()->pluck('name')->all(),
            'permissionsLocked' => $editing?->name === 'superadmin',
            'isSystemRole' => $editing ? SystemRoles::is($editing->name) : false,
        ]);
    }
}
