<?php

namespace Tests\Feature\Aduan;

use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\User;
use Tests\FeatureTestCase;

/**
 * Baseline test for the critical "create aduan (report)" path.
 *
 * Locks in that an authenticated user can submit a report through
 * ReportController@store, that it persists, and that status 2 ("Dihantar")
 * stamps submitted_at. Requirement 1 (task 1.2).
 */
class CreateReportTest extends FeatureTestCase
{
    public function test_authenticated_user_can_create_a_report()
    {
        $user = User::factory()->create();
        $category = ReportCategory::create([
            'desc' => 'Rangkaian',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/aduan/create', [
            'subject' => 'Internet tidak berfungsi',
            'remark' => 'Sambungan internet di aras 3 terputus.',
            'report_category_id' => $category->id,
            'report_status_id' => 2, // Dihantar
            'is_bp' => 0,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('reports', [
            'subject' => 'Internet tidak berfungsi',
            'report_category_id' => $category->id,
            'report_status_id' => 2,
            'report_by_id' => $user->id,
        ]);

        $report = Report::first();
        $this->assertNotNull($report->submitted_at, 'submitted_at should be set for status Dihantar');
    }

    public function test_report_requires_subject_category_and_remark()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from('/aduan/create')
            ->post('/aduan/create', [
                'is_bp' => 0,
            ]);

        $response->assertSessionHasErrors(['subject', 'report_category_id', 'remark']);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_guest_cannot_create_a_report()
    {
        $category = ReportCategory::create(['desc' => 'Rangkaian', 'is_active' => true]);

        $response = $this->post('/aduan/create', [
            'subject' => 'x',
            'remark' => 'y',
            'report_category_id' => $category->id,
            'report_status_id' => 2,
            'is_bp' => 0,
        ]);

        $response->assertRedirect('/login');
        $this->assertDatabaseCount('reports', 0);
    }
}
