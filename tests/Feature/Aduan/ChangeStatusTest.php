<?php

namespace Tests\Feature\Aduan;

use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Tests\FeatureTestCase;

/**
 * Baseline test for the critical "change report status" path.
 *
 * Submitting reporter feedback (ReportController@updateFeedback) transitions a
 * report to status 5 (Selesai), records completed_at, and appends a trail
 * entry. Notifications are faked. Requirement 1 (task 1.2).
 */
class ChangeStatusTest extends FeatureTestCase
{
    public function test_submitting_feedback_marks_report_selesai()
    {
        Notification::fake();

        $user = User::factory()->create();
        $category = ReportCategory::create(['desc' => 'Rangkaian', 'is_active' => true]);

        // Create a submitted report (status 2) owned by the acting user.
        $this->actingAs($user);
        $report = $this->createReport($category, $user);

        $this->assertEquals(2, $report->fresh()->report_status_id);

        $response = $this->actingAs($user)->post('/aduan/feedback/'.$report->id, [
            'rating' => 5,
            'feedback' => 'Masalah telah diselesaikan dengan cepat.',
        ]);

        $response->assertRedirect();

        $report->refresh();
        $this->assertEquals(5, $report->report_status_id, 'Report should be Selesai');
        $this->assertNotNull($report->completed_at);
        $this->assertEquals(5, $report->rating);

        // A trail entry with the "Selesai" status (5) was appended.
        $this->assertDatabaseHas('report_trails', [
            'report_id' => $report->id,
            'status_id' => 5,
        ]);
    }

    /** Helper: create a submitted report as the currently acting user. */
    private function createReport(ReportCategory $category, User $user): Report
    {
        $report = new Report();
        $report->subject = 'Internet tidak berfungsi';
        $report->remark = 'Sambungan terputus.';
        $report->report_category_id = $category->id;
        $report->report_status_id = 2;
        $report->submitted_at = now();
        $report->save();

        return $report;
    }
}
