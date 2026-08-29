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
 * Report (aduan) detail page + full flow actions. See flow baru.md.
 *
 * Status flow (IDs): 2 Baru -> 3 Aduan Disemak -> 4 Dalam Tindakan
 *   officer: 7 Selesai Tindakan (+lesson learn)  OR  8 Tindakan Lanjut Diperlukan
 *   helpdesk from 8: 6 KIV  or  reassign -> 4
 *   close from 7: 5 Selesai (+rating default 5)
 *
 * mount(): admin viewing a "Baru" (2) report auto-advances it to
 * "Aduan Disemak" (3).
 */
class ReportShow extends Component
{
    public Report $report;

    // Admin (helpdesk) task form
    public string $tindakan = '';
    public ?string $peranan = null;
    public ?int $assigned_to_id = null;
    public string $reassignment_remark = '';
    public string $kiv_start_at = '';
    public string $kiv_reason = '';
    public string $kiv_end_at = '';

    // Officer (peg. bertanggungjawab) outcome + lesson learn
    public string $outcome = '';            // 'selesai' | 'lanjut'
    public string $punca_masalah = '';
    public string $langkah_tindakan = '';
    public string $catatan_teknikal = '';
    public string $kenapa_lanjut = '';

    // Close (feedback) form
    public ?int $rating = 5;                // default 5 stars
    public string $feedback = '';

    // Category change
    public ?int $report_category_id = null;

    public function mount(Report $report): void
    {
        // Access control: only legitimate viewers (owner, wakil, assigned
        // officer, helpdesk, superadmin) may open this aduan. Prevents IDOR
        // via guessing/altering the id in the URL.
        $this->authorize('view', $report);

        // Admin viewing a "Baru" (2) report auto-advances it to "Aduan
        // Disemak" (3), once, with a trail.
        if ($report->report_status_id == 2 && Auth::user()->can('aduan.adminTask')) {
            $report->report_status_id = 3;
            $report->save();

            $trail = new ReportTrail();
            $trail->message = 'Aduan telah disemak oleh peg. helpdesk Sistem Meja Bantuan KKDW';
            $trail->updated_by_id = Auth::id();
            $trail->status_id = 3;
            $report->trails()->save($trail);
        }

        $this->report = $report;
        $this->report_category_id = $report->report_category_id;
    }

    // ---- Helpers to decide which panels show ----

    public function getIsAssignedOfficerProperty(): bool
    {
        // The currently-assigned officer = the latest trail that carries an
        // assigned_to_id (ordered by id for deterministic "latest").
        $assignedTo = $this->report->trails()
            ->whereNotNull('assigned_to_id')
            ->orderByDesc('id')
            ->value('assigned_to_id');

        return $assignedTo !== null && (int) $assignedTo === Auth::id();
    }

    // ================= Admin (helpdesk) task =================

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

        $this->validate($this->adminTaskRules(), [
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

    // ================= Officer outcome (lesson learn) =================

    public function saveOfficerAction(TransitionReportStatus $transition): void
    {
        // Only the currently-assigned officer, while report is In Action (4).
        abort_unless(
            Auth::user()->can('aduan.updateTask')
                && $this->report->report_status_id == 4
                && $this->isAssignedOfficer,
            403
        );

        $rules = [
            'outcome' => 'required|in:selesai,lanjut',
            'punca_masalah' => 'required|string',
        ];
        if ($this->outcome === 'selesai') {
            $rules['langkah_tindakan'] = 'required|string';
        } elseif ($this->outcome === 'lanjut') {
            $rules['kenapa_lanjut'] = 'required|string';
        }

        $this->validate($rules, [
            'outcome.required' => 'Sila pilih hasil tindakan.',
            'punca_masalah.required' => 'Punca masalah wajib diisi.',
            'langkah_tindakan.required' => 'Langkah penyelesaian wajib diisi.',
            'kenapa_lanjut.required' => 'Sebab perlu tindakan lanjut wajib diisi.',
        ]);

        $transition->officerAction($this->report, [
            'outcome' => $this->outcome,
            'punca_masalah' => $this->punca_masalah,
            'langkah_tindakan' => $this->langkah_tindakan,
            'catatan_teknikal' => $this->catatan_teknikal,
            'kenapa_lanjut' => $this->kenapa_lanjut,
        ]);

        $this->reset(['outcome', 'punca_masalah', 'langkah_tindakan', 'catatan_teknikal', 'kenapa_lanjut']);
        $this->report->refresh();
        session()->flash('report-message', 'Tindakan pegawai berjaya direkodkan.');
    }

    // ================= Close aduan (Selesai + rating) =================

    public function closeReport(SubmitFeedback $submitFeedback): void
    {
        // From "Selesai Tindakan" (7): reporter or helpdesk closes with rating.
        abort_unless(
            ($this->report->reportBy?->is(Auth::user()) || Auth::user()->hasAnyRole(['superadmin', 'helpdesk.admin']))
                && $this->report->report_status_id == 7,
            403
        );

        $this->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|max:255',
        ], [
            'rating.required' => 'Sila beri rating.',
        ]);

        $submitFeedback->handle($this->report, (int) $this->rating, $this->feedback);
        $this->report->refresh();
        session()->flash('report-message', 'Aduan telah ditutup sebagai Selesai.');
    }

    // ================= Category change =================

    public function setCategory(): void
    {
        $this->authorize('aduan.adminTask');
        $this->report->report_category_id = $this->report_category_id;
        $this->report->save();
        $this->report->refresh();
        session()->flash('report-message', 'Kategori berjaya dikemaskini.');
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
