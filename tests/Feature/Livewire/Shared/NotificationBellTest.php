<?php

namespace Tests\Feature\Livewire\Shared;

use App\Livewire\Shared\NotificationBell;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\FeatureTestCase;

class NotificationBellTest extends FeatureTestCase
{
    private function addNotification(User $user, array $data): string
    {
        $id = (string) Str::uuid();
        $user->notifications()->create([
            'id' => $id,
            'type' => 'App\\Notifications\\ReportSendNotification',
            'data' => $data,
            'read_at' => null,
        ]);

        return $id;
    }

    public function test_shows_unread_notifications_and_count()
    {
        $user = User::factory()->create();
        $this->addNotification($user, [
            'title' => 'Aduan Berjaya Dihantar',
            'message' => 'No tiket aduan adalah 260828/HQ/001',
            'url' => 'http://localhost/aduan/show/1',
            'icon' => 'bx bxs-paper-plane',
            'color' => 'bg-success',
        ]);

        Livewire::actingAs($user)
            ->test(NotificationBell::class)
            ->assertOk()
            ->assertSee('Aduan Berjaya Dihantar')
            ->assertSee('1'); // count badge
    }

    public function test_empty_state_when_no_notifications()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationBell::class)
            ->assertSee('Tiada notifikasi baharu.');
    }

    public function test_mark_all_read_clears_unread()
    {
        $user = User::factory()->create();
        $this->addNotification($user, ['title' => 'A', 'message' => 'a', 'url' => null]);
        $this->addNotification($user, ['title' => 'B', 'message' => 'b', 'url' => null]);

        Livewire::actingAs($user)
            ->test(NotificationBell::class)
            ->call('markAllRead');

        $this->assertCount(0, $user->fresh()->unreadNotifications);
    }

    public function test_open_marks_read_and_redirects()
    {
        $user = User::factory()->create();
        $id = $this->addNotification($user, [
            'title' => 'X', 'message' => 'Y',
            'url' => 'http://localhost/aduan/show/1',
            'icon' => 'bx bx-bell', 'color' => 'bg-info',
        ]);

        Livewire::actingAs($user)
            ->test(NotificationBell::class)
            ->call('open', $id)
            ->assertRedirect('http://localhost/aduan/show/1');

        $this->assertNotNull($user->fresh()->notifications()->find($id)->read_at);
        $this->assertCount(0, $user->fresh()->unreadNotifications);
    }
}
