<?php

namespace App\Livewire\Aduan;

use App\Actions\Reports\SubmitFeedback;
use App\Actions\Reports\TransitionReportStatus;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\ReportTrail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * Report (aduan) detail page: applicant info, complaint info, attachments,
 * admin-task transitions (tindakan 1-4), technical remark, feedback, category
 * change, and the trail timeline. Replaces aduan/show.blade.php + its jQuery.
 *
 * Replicates the UpdateReportStatus middleware side effect: viewing a Dihantar
 * (2) report as an admin auto-transitions it to AmbilMaklum (3) with a trail.
 */
class ReportShow extends Component
{
    public Report $report;

    // Admin task form
    public string $tindakan = '';
    public ?string $peranan = null;
    public ?int $assigned_to_id = null;
    public string $reassignment_remark = '';
    public string $kiv_start_at = '';
    public string $kiv_reason = '';
    public string $kiv_end_at = '';

    // Feedback form
    public ?int $rating = null;
    public string $feedback = '';

    // Technical remark
    public string $technical_remark = '';

    // Category change
    public ?int $report_category_id = null;

    public function mount(Report $report): void
    {
        // Replicate UpdateReportStatus middleware: admin viewing a Dihantar
        // report auto-acknowledges it (status 2 -> 3) once.
        if ($report->report_status_id == 2 && Auth::user()->can('aduan.adminTask')) {
            $report->report_status_id = 3;
            $report->save();

            $trail = new ReportTrail();
            $trail->message = 'Aduan telah di ambil maklum oleh admin Sistem Meja Bantuan KKDW';
            $trail->updated_by_id = Auth::id();
            $trail->status_id = 3;
            $report->trails()->save($trail);
        }

        $this->report = $report;
        $this->report_category_id = $report->report_category_id;
        $this->technical_remark = (string) optional($report->currentTrail)->technical_remark;
    }

    protected function adminTaskRules(): array
    {
        return [
            'tindakan' => 'required',
            'assigned_to_id' => 'bail|required_if:tindakan,1,2|nullable|exists:users,id',
            'peranan' => 'nullable|exists:roles,name',
            'kiv_start_at' => 'required_if:tindakan,3',
            'kiv_end_at' => 'required_if:tindakan,4',
        ];
    }

    public function saveAdminTask(TransitionReportStatus $transition): void
    {
        $this->authorize('aduan.adminTask');

        $data = $this->validate($this->adminTaskRules(), [
            'assigned_to_id.required_if' => 'Ruangan kepada diperlukan.',
            'kiv_start_at.required_if' => 'Ruangan tarikh mula KIV diperlukan.',
            'kiv_end_at.required_if' => 'Ruangan tarikh tamat KIV diperlukan.',
        ]);

        $transition->handle($this->report, [
            'tindakan' => (int) $this->tindakan,
            'assigned_to_id' => $this->assigned_to_id,
            'peranan' => $this->peranan,
            'reassignment_remark' => $this->reassignment_remark,
            'kiv_start_at' => $this->kiv_start_at,
            'kiv_reason' => $this->kiv_reason,
            'kiv_end_at' => $this->kiv_end_at,
        ]);

        $this->reset(['tindakan', 'peranan', 'assigned_to_id', 'reassignment_remark', 'kiv_start_at', 'kiv_reason', 'kiv_end_at']);
        $this->report->refresh();
        session()->flash('report-message', 'Tindakan berjaya disimpan.');
    }

    public function setCategory(): void
    {
        $this->authorize('aduan.adminTask');
        $this->report->report_category_id = $this->report_category_id;
        $this->report->save();
        $this->report->refresh();
        session()->flash('report-message', 'Kategori berjaya dikemaskini.');
    }

    public function saveTechnicalRemark(): void
    {
        $trail = $this->report->currentTrail;
        abort_unless($trail && $trail->assigned_to_id === Auth::id(), 403);

        $trail->technical_remark = $this->technical_remark;
        $trail->save();
        session()->flash('report-message', 'Catatan berjaya disimpan.');
    }

    public function saveFeedback(SubmitFeedback $submitFeedback): void
    {
        abort_unless(
            ($this->report->reportBy?->is(Auth::user()) || Auth::user()->hasAnyRole(['superadmin', 'helpdesk.admin']))
                && $this->report->report_status_id == 4,
            403
        );

        $data = $this->validate([
            'rating' => 'required',
            'feedback' => 'required|max:255',
        ]);

        $submitFeedback->handle($this->report, (int) $this->rating, $this->feedback);
        $this->report->refresh();
        session()->flash('report-message', 'Maklum balas berjaya dihantar.');
    }

    public function render()
    {
        $roles = Role::whereHas('permissions', fn ($q) => $q->where('name', 'aduan.updateTask'))
            ->where('name', '!=', 'superadmin')
            ->get();

        $officers = $this->peranan
            ? User::role($this->peranan)->orderBy('name')->get()
            : collect();

        return view('livewire.aduan.report-show', [
            'roles' => $roles,
            'officers' => $officers,
            'categories' => ReportCategory::active()->orderBy('desc')->get(),
            'trails' => $this->report->trails()->with('status')->get(),
            'attachments' => $this->report->attachments()->get(),
            'canAdmin' => Auth::user()->can('aduan.adminTask'),
            'canUpdateTask' => Auth::user()->can('aduan.updateTask'),
        ]);
    }
}
