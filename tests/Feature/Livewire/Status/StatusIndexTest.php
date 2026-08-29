<?php

namespace Tests\Feature\Livewire\Status;

use App\Livewire\Status\StatusIndex;
use App\Models\Ref\ReportStatus;
use Livewire\Livewire;
use Tests\FeatureTestCase;

class StatusIndexTest extends FeatureTestCase
{
    public function test_lists_statuses()
    {
        // The base test case seeds ReportStatusSeeder (new flow statuses).
        Livewire::test(StatusIndex::class)
            ->assertOk()
            ->assertSee('Baru')
            ->assertSee('Aduan Disemak')
            ->assertSee('Selesai Tindakan');
    }

    public function test_edit_loads_status()
    {
        $status = ReportStatus::first();

        Livewire::test(StatusIndex::class)
            ->call('edit', $status->id)
            ->assertSet('editId', $status->id)
            ->assertSet('desc', $status->desc)
            ->assertSet('showModal', true);
    }

    public function test_update_requires_desc()
    {
        $status = ReportStatus::first();

        Livewire::test(StatusIndex::class)
            ->call('edit', $status->id)
            ->set('desc', '')
            ->call('save')
            ->assertHasErrors(['desc' => 'required']);
    }

    public function test_updates_status_desc()
    {
        $status = ReportStatus::first();

        Livewire::test(StatusIndex::class)
            ->call('edit', $status->id)
            ->set('desc', 'Status Dikemaskini')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('Status Dikemaskini', $status->fresh()->desc);
    }
}
