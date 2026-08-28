<?php

namespace App\Livewire\User;

use App\Models\User;
use App\Models\UserAsset;
use Livewire\Component;

/**
 * Manage a user's assets (replaces the asset DataTable + create/edit AJAX
 * modals on the full-user page). Create/update are validated server-side;
 * delete is a soft delete (UserAsset uses SoftDeletes).
 */
class UserAssetManager extends Component
{
    public User $user;

    public ?int $assetId = null;
    public bool $showModal = false;

    public string $type = '';
    public string $brand = '';
    public string $model = '';
    public string $registration_serial_no = '';
    public string $supplier_serial_no = '';

    protected function rules(): array
    {
        return [
            'type' => 'required|string',
            'brand' => 'required|string',
            'model' => 'required|string',
            'registration_serial_no' => 'required|string',
            'supplier_serial_no' => 'required|string',
        ];
    }

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $asset = UserAsset::where('user_id', $this->user->id)->findOrFail($id);
        $this->assetId = $asset->id;
        $this->type = (string) $asset->type;
        $this->brand = (string) $asset->brand;
        $this->model = (string) $asset->model;
        $this->registration_serial_no = (string) $asset->registration_serial_no;
        $this->supplier_serial_no = (string) $asset->supplier_serial_no;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        $asset = $this->assetId
            ? UserAsset::where('user_id', $this->user->id)->findOrFail($this->assetId)
            : new UserAsset(['user_id' => $this->user->id]);

        $asset->fill($data);
        $asset->user_id = $this->user->id;
        $asset->save();

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        UserAsset::where('user_id', $this->user->id)->findOrFail($id)->delete();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->reset(['assetId', 'type', 'brand', 'model', 'registration_serial_no', 'supplier_serial_no']);
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.user.user-asset-manager', [
            'assets' => $this->user->assets()->get(),
        ]);
    }
}
