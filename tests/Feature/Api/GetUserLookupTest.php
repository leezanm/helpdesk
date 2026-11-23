<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Tests\FeatureTestCase;

/**
 * POST api/get-user is the legacy "bagi pihak" username lookup. It returns a
 * staff member's name, email and bahagian, so it is gated on
 * aduan.representAny — the permission held by IT support and helpdesk.
 */
class GetUserLookupTest extends FeatureTestCase
{
    public function test_warga_biasa_cannot_look_up_another_user()
    {
        $user = User::factory()->create();             // role "pengguna"
        $other = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/get-user', ['bp_username' => $other->username])
            ->assertForbidden();
    }

    public function test_guest_cannot_look_up_a_user()
    {
        $other = User::factory()->create();

        $this->postJson('/api/get-user', ['bp_username' => $other->username])
            ->assertUnauthorized();
    }

    public function test_it_support_can_look_up_a_user()
    {
        $jtk = User::factory()->create();
        $jtk->assignRole('jtk');
        $other = User::factory()->create(['ldap_bahagian' => 'Bahagian B']);

        $this->actingAs($jtk)
            ->postJson('/api/get-user', ['bp_username' => $other->username])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'bp_name' => $other->name,
                'bp_bahagian' => 'Bahagian B',
                'bp_user_id' => $other->id,
            ]);
    }
}
