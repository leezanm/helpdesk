<?php

namespace App\Livewire\User;

use App\Actions\Users\SaveUser;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\Permission\Models\Role;

/**
 * Create / edit form for a user (replaces the create/edit AJAX modals + the
 * hand-written validation-injection JS). Roles are multi-select checkboxes
 * (spatie role names). Profile picture is uploadable on create.
 */
class UserForm extends Component
{
    use WithFileUploads;

    public ?int $userId = null;
    public bool $showModal = false;

    public string $name = '';
    public string $username = '';
    public string $email = '';
    public string $designation = '';
    public string $tel_no = '';
    public string $ic_no = '';
    public string $address = '';
    /** @var array<int,string> */
    public array $roles = [];
    public $profile_path = null;   // upload (create only)

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'username' => [
                'required', 'string',
                Rule::unique('users', 'username')->ignore($this->userId),
            ],
            'email' => 'required|email',
            'designation' => 'nullable|string',
            'tel_no' => 'nullable|numeric|digits_between:10,11',
            'ic_no' => [
                'nullable', 'numeric', 'digits:12',
                Rule::unique('users', 'ic_no')->ignore($this->userId),
            ],
            'address' => 'nullable|string',
            'roles' => 'required|array|min:1',
            'roles.*' => 'required|exists:roles,name',
            'profile_path' => 'nullable|image|max:256',
        ];
    }

    protected array $messages = [
        'name.required' => 'Nama wajib diisi.',
        'username.required' => 'Nama pengguna wajib diisi.',
        'username.unique' => 'Nama pengguna telah wujud.',
        'email.required' => 'Emel wajib diisi.',
        'email.email' => 'Format emel tidak sah.',
        'roles.required' => 'Sekurang-kurangnya satu peranan diperlukan.',
        'roles.min' => 'Sekurang-kurangnya satu peranan diperlukan.',
        'ic_no.digits' => 'No. KP mestilah 12 digit.',
        'tel_no.digits_between' => 'No. telefon tidak sah.',
    ];

    #[On('user-create')]
    public function openCreate(): void
    {
        $this->resetForm();
        $this->roles = ['pengguna']; // default role, matches prior UX
        $this->showModal = true;
    }

    #[On('user-edit')]
    public function openEdit(int $id): void
    {
        $user = User::with('roles')->findOrFail($id);
        $this->userId = $user->id;
        $this->name = (string) $user->name;
        $this->username = (string) $user->username;
        $this->email = (string) $user->email;
        $this->designation = (string) $user->designation;
        $this->tel_no = (string) $user->tel_no;
        $this->ic_no = (string) $user->ic_no;
        $this->address = (string) $user->address;
        $this->roles = $user->roles->pluck('name')->all();
        $this->profile_path = null;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function save(SaveUser $saveUser): void
    {
        $data = $this->validate();

        $user = $this->userId ? User::findOrFail($this->userId) : new User();

        $saveUser->handle(
            user: $user,
            attributes: [
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'designation' => $data['designation'] ?? null,
                'tel_no' => $data['tel_no'] ?? null,
                'ic_no' => $data['ic_no'] ?? null,
                'address' => $data['address'] ?? null,
            ],
            roles: $data['roles'],
            profilePicture: $this->userId ? null : $this->profile_path,
            defaultPassword: $this->userId ? null : 'pass1234',
        );

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('user-saved');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->reset(['userId', 'name', 'username', 'email', 'designation', 'tel_no', 'ic_no', 'address', 'roles', 'profile_path']);
        $this->resetErrorBag();
    }

    public function render()
    {
        $allRoles = auth()->user()?->hasRole('superadmin')
            ? Role::orderBy('desc')->get()
            : Role::where('name', '!=', 'superadmin')->orderBy('desc')->get();

        return view('livewire.user.user-form', [
            'allRoles' => $allRoles,
        ]);
    }
}
