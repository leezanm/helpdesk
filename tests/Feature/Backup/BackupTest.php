<?php

namespace Tests\Feature\Backup;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\FeatureTestCase;

/**
 * Baseline test for the critical "backup" path.
 *
 * The backup screens are gated by the `manage.backup` permission. This test
 * locks in the authorization behavior and that the backup index renders.
 * The actual backup:run Artisan command is faked so the test does not create
 * real archive files or dump a database. Requirement 1 (task 1.2).
 */
class BackupTest extends FeatureTestCase
{
    public function test_backup_index_requires_manage_backup_permission()
    {
        $user = User::factory()->create(); // default role "pengguna" lacks manage.backup

        $response = $this->actingAs($user)->get('/backup');

        $response->assertForbidden();
    }

    public function test_authorized_user_can_view_backup_index()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage.backup');

        $response = $this->actingAs($user)->get('/backup');

        $response->assertOk();
    }

    public function test_authorized_user_can_trigger_full_backup()
    {
        Artisan::spy();

        $user = User::factory()->create();
        $user->givePermissionTo('manage.backup');

        $response = $this->actingAs($user)->get('/backup/full');

        $response->assertRedirect(route('backup.index'));
        Artisan::shouldHaveReceived('call')->with('backup:run')->once();
    }
}
