<?php

namespace Tests\Feature\Livewire\User;

use App\Livewire\User\UserAssetManager;
use App\Models\User;
use App\Models\UserAsset;
use Livewire\Livewire;
use Tests\FeatureTestCase;

class UserAssetManagerTest extends FeatureTestCase
{
    public function test_lists_user_assets()
    {
        $user = User::factory()->create();
        UserAsset::create([
            'user_id' => $user->id,
            'type' => 'Laptop', 'brand' => 'Dell', 'model' => 'Latitude',
            'registration_serial_no' => 'REG1', 'supplier_serial_no' => 'SUP1',
        ]);

        Livewire::test(UserAssetManager::class, ['user' => $user])
            ->assertOk()
            ->assertSee('Laptop')
            ->assertSee('Dell');
    }

    public function test_create_validates_required_fields()
    {
        $user = User::factory()->create();

        Livewire::test(UserAssetManager::class, ['user' => $user])
            ->call('create')
            ->call('save')
            ->assertHasErrors(['type', 'brand', 'model', 'registration_serial_no', 'supplier_serial_no']);
    }

    public function test_creates_asset_for_user()
    {
        $user = User::factory()->create();

        Livewire::test(UserAssetManager::class, ['user' => $user])
            ->call('create')
            ->set('type', 'Printer')
            ->set('brand', 'HP')
            ->set('model', 'LaserJet')
            ->set('registration_serial_no', 'REG2')
            ->set('supplier_serial_no', 'SUP2')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('user_assets', [
            'user_id' => $user->id,
            'type' => 'Printer',
            'brand' => 'HP',
        ]);
    }

    public function test_deletes_asset()
    {
        $user = User::factory()->create();
        $asset = UserAsset::create([
            'user_id' => $user->id,
            'type' => 'Laptop', 'brand' => 'Dell', 'model' => 'X',
            'registration_serial_no' => 'R', 'supplier_serial_no' => 'S',
        ]);

        Livewire::test(UserAssetManager::class, ['user' => $user])
            ->call('delete', $asset->id);

        $this->assertSoftDeleted('user_assets', ['id' => $asset->id]);
    }
}
