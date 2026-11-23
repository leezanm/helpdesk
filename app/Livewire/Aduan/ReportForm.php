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

    /**
     * Phone number of the represented person. Editable by the wakil so an
     * aduan filed on someone's behalf always carries a contact number, and
     * the represented person's profile gets updated at the same time.
     */
    public string $bp_tel_no = '';

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
            'bp_tel_no' => 'required_if:is_bp,true|string|max:30',
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
        'bp_tel_no.required_if' => 'No. Telefon diperlukan.',
        'bp_tel_no.max' => 'No. Telefon tidak boleh melebihi 30 aksara.',
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
            $this->report_category_id = $report->report_category_id;
            $this->subject = (string) $report->subject;
            $this->remark = (string) $report->remark;

            if ($this->is_bp) {
                $this->fillRepresentedFromReport($report);
            }
        }
    }

    /**
     * Load the represented person into the bp_* form fields.
     *
     * Note the asymmetry with the stored columns: once saved, the bp_* columns
     * hold the ACTING WAKIL whenever the represented person has a local user
     * record, because SaveReport makes that person the aduan owner
     * (report_by_id). The form always edits the represented person, so read
     * them from the owner in that case and fall back to the bp_* columns for an
     * AD-only represented person (no local record to own the aduan).
     */
    protected function fillRepresentedFromReport(Report $report): void
    {
        $represented = $report->bp_user_id && $report->bp_user_id !== $report->report_by_id
            ? $report->reportBy
            : null;

        if ($represented) {
            $this->bp_username = (string) $represented->username;
            $this->bp_user_id = $represented->id;
            $this->bp_name = (string) $represented->name;
            $this->bp_email = (string) $represented->email;
            $this->bp_bahagian = (string) $represented->ldap_bahagian;
            $this->bp_tel_no = (string) $represented->tel_no;

            return;
        }

        $this->bp_username = (string) $report->bp_username;
        $this->bp_user_id = $report->bp_user_id;
        $this->bp_name = (string) $report->bp_name;
        $this->bp_email = (string) $report->bp_email;
        $this->bp_bahagian = (string) $report->bp_bahagian;
        $this->bp_tel_no = (string) $report->bp_tel_no;
    }

    /**
     * Look up a "bagi pihak" user by AD username (replaces the create-view
     * bp_username AJAX to api.get-user). Checks local users first, then AD.
     */
    /**
     * IT support staff — juruteknik (jtk), Pegawai Teknologi Maklumat (ptm),
     * Penolong Pegawai Teknologi Maklumat (pptm) and helpdesk officers — serve
     * the whole ministry, so they may represent a user from ANY bahagian. They
     * carry the aduan.representAny permission. Everyone else may only represent
     * someone from their OWN bahagian.
     */
    public function canRepresentAnyBahagian(): bool
    {
        return Auth::user()->can('aduan.representAny');
    }

    public function lookupBpUser(): void
    {
        $this->bpLookupError = null;
        $this->reset(['bp_name', 'bp_email', 'bp_bahagian', 'bp_tel_no', 'bp_user_id']);

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
            // Prefill their current number (often empty) — the wakil may fill
            // or correct it, and SaveReport writes it back to their profile.
            $this->bp_tel_no = (string) $user->tel_no;
            $this->bp_user_id = $user->id;
            return;
        }

        try {
            $ldapUser = LdapUser::query()
                ->select(['cn', 'physicaldeliveryofficename', 'userprincipalname', 'telephonenumber', 'mobile'])
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
            $this->bp_tel_no = (string) ($ldapUser->getFirstAttribute('telephonenumber')
                ?: $ldapUser->getFirstAttribute('mobile'));
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
            'bp_tel_no' => $this->bp_tel_no,
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
