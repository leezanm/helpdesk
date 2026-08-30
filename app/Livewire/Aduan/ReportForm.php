<?php

namespace App\Livewire\Aduan;

use App\Actions\Reports\SaveReport;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\ReportCategory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use LdapRecord\Models\ActiveDirectory\User as LdapUser;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Create / edit an aduan (report). Replaces the create/edit Blade + Dropzone +
 * hidden-JSON attachment mechanism with WithFileUploads, and the bp_username
 * AJAX lookup with a wire:click action.
 *
 * The two submit buttons map to a status: Draf (1) or Hantar (2).
 */
class ReportForm extends Component
{
    use WithFileUploads;

    public ?Report $report = null;

    public bool $is_bp = false;
    public string $bp_username = '';
    public ?int $bp_user_id = null;
    public string $bp_name = '';
    public string $bp_email = '';
    public string $bp_bahagian = '';

    public ?int $report_category_id = null;
    public string $subject = '';
    public string $remark = '';

    /** @var array newly uploaded temporary files */
    public array $attachments = [];

    public ?string $bpLookupError = null;

    protected function rules(): array
    {
        return [
            'subject' => 'required|string',
            'report_category_id' => 'required|exists:report_categories,id',
            'remark' => 'required|string',
            'bp_username' => 'required_if:is_bp,true',
            'bp_name' => 'required_if:is_bp,true',
            'bp_email' => 'required_if:is_bp,true',
            'bp_bahagian' => 'required_if:is_bp,true',
            'attachments.*' => 'file|max:2048|mimes:pdf,jpeg,jpg,png',
        ];
    }

    protected array $messages = [
        'subject.required' => 'Subjek wajib diisi.',
        'report_category_id.required' => 'Kategori aduan wajib dipilih.',
        'remark.required' => 'Ringkasan aduan wajib diisi.',
        'bp_username.required_if' => 'ID pengguna diperlukan.',
        'bp_name.required_if' => 'Nama penuh diperlukan.',
        'bp_email.required_if' => 'Emel diperlukan.',
        'bp_bahagian.required_if' => 'Bahagian diperlukan.',
    ];

    public function mount(?Report $report = null): void
    {
        if ($report && $report->exists) {
            // Editing an existing aduan — only the owner/wakil (draft) may edit
            // (ReportPolicy@update). Prevents tampering with another user's
            // aduan (e.g. deleting its attachments) via a crafted request.
            $this->authorize('update', $report);

            $this->report = $report;
            $this->is_bp = (bool) $report->is_bp;
            $this->bp_username = (string) $report->bp_username;
            $this->bp_user_id = $report->bp_user_id;
            $this->bp_name = (string) $report->bp_name;
            $this->bp_email = (string) $report->bp_email;
            $this->bp_bahagian = (string) $report->bp_bahagian;
            $this->report_category_id = $report->report_category_id;
            $this->subject = (string) $report->subject;
            $this->remark = (string) $report->remark;
        }
    }

    /**
     * Look up a "bagi pihak" user by AD username (replaces the create-view
     * bp_username AJAX to api.get-user). Checks local users first, then AD.
     */
    /**
     * Helpdesk officers may represent a user from ANY bahagian; a normal user
     * may only represent someone from their OWN bahagian.
     */
    public function canRepresentAnyBahagian(): bool
    {
        return Auth::user()->can('aduan.adminTask');
    }

    public function lookupBpUser(): void
    {
        $this->bpLookupError = null;
        $this->reset(['bp_name', 'bp_email', 'bp_bahagian', 'bp_user_id']);

        if ($this->bp_username === Auth::user()->username) {
            $this->bpLookupError = 'Username mestilah dari pengguna lain.';
            return;
        }

        $user = User::firstWhere('username', $this->bp_username);
        if ($user) {
            if (! $this->bahagianAllowed((string) $user->ldap_bahagian)) {
                $this->bpLookupError = 'Anda hanya boleh mewakili pengguna dari bahagian anda sahaja.';
                return;
            }
            $this->bp_name = (string) $user->name;
            $this->bp_bahagian = (string) $user->ldap_bahagian;
            $this->bp_email = (string) $user->email;
            $this->bp_user_id = $user->id;
            return;
        }

        try {
            $ldapUser = LdapUser::query()
                ->select(['cn', 'physicaldeliveryofficename', 'userprincipalname'])
                ->findBy('samaccountname', $this->bp_username);
        } catch (\Throwable $e) {
            $this->bpLookupError = 'Tidak dapat connect ke AD. Sila hubungi admin.';
            return;
        }

        if ($ldapUser) {
            $bahagian = (string) $ldapUser->getFirstAttribute('physicaldeliveryofficename');
            if (! $this->bahagianAllowed($bahagian)) {
                $this->bpLookupError = 'Anda hanya boleh mewakili pengguna dari bahagian anda sahaja.';
                return;
            }
            $this->bp_name = (string) $ldapUser->getFirstAttribute('cn');
            $this->bp_bahagian = $bahagian;
            $this->bp_email = (string) $ldapUser->getFirstAttribute('userprincipalname');
        } else {
            $this->bpLookupError = 'Pengguna tidak dijumpai.';
        }
    }

    /** Whether the current user is allowed to represent someone in $bahagian. */
    protected function bahagianAllowed(string $bahagian): bool
    {
        if ($this->canRepresentAnyBahagian()) {
            return true;
        }

        // Normal user: represented person must share the user's own bahagian.
        return $bahagian !== '' && $bahagian === (string) Auth::user()->ldap_bahagian;
    }

    public function removeAttachment(int $index): void
    {
        unset($this->attachments[$index]);
        $this->attachments = array_values($this->attachments);
    }

    public function deleteExistingAttachment(int $id): void
    {
        if (! $this->report) {
            return;
        }
        ReportAttachment::where('report_id', $this->report->id)->where('id', $id)->delete();
        $this->report->refresh();
    }

    public function save(int $status, SaveReport $saveReport)
    {
        $this->validate();

        // Re-enforce the bahagian rule server-side (defence in depth): a normal
        // user may only represent someone from their own bahagian; helpdesk may
        // represent any bahagian. The aduan is recorded under the represented
        // person, so this must be validated even if the UI was bypassed.
        if ($this->is_bp && ! $this->bahagianAllowed($this->bp_bahagian)) {
            $this->addError('bp_bahagian', 'Anda hanya boleh mewakili pengguna dari bahagian anda sahaja.');
            return;
        }

        $report = $this->report ?: new Report();

        $saveReport->handle($report, [
            'is_bp' => $this->is_bp,
            'bp_username' => $this->bp_username,
            'bp_name' => $this->bp_name,
            'bp_email' => $this->bp_email,
            'bp_bahagian' => $this->bp_bahagian,
            'bp_user_id' => $this->bp_user_id,
            'report_category_id' => $this->report_category_id,
            'subject' => $this->subject,
            'remark' => $this->remark,
            'report_status_id' => $status,
        ], $this->attachments);

        return redirect()->route('aduan.index-user', Auth::user()->username);
    }

    public function render()
    {
        return view('livewire.aduan.report-form', [
            'categories' => ReportCategory::active()->orderBy('desc')->get(),
            'existingAttachments' => $this->report ? $this->report->attachments()->get() : collect(),
        ]);
    }
}
