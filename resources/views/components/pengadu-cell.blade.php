@props(['report'])

{{-- Complainant name + bahagian, with an email/phone contact card on hover.
     Shared by the aduan list and the dashboards so they stay consistent. --}}
<div class="pengadu-cell">
    <div class="fw-medium">
        {{ $report->reportBy->name ?? '-' }}
        @if ($report->reportBy && ($report->reportBy->email || $report->reportBy->tel_no))
            <i class="bi bi-info-circle text-muted ms-1 pengadu-cell__hint"></i>
        @endif
    </div>
    <small class="text-muted">{{ $report->reportBy->ldap_bahagian ?? '' }}</small>

    @if ($report->reportBy && ($report->reportBy->email || $report->reportBy->tel_no))
        <div class="pengadu-contact shadow-sm">
            <div class="pengadu-contact__name">{{ $report->reportBy->name }}</div>
            @if ($report->reportBy->email)
                <a href="mailto:{{ $report->reportBy->email }}" class="pengadu-contact__row">
                    <i class="bi bi-envelope"></i><span>{{ $report->reportBy->email }}</span>
                </a>
            @endif
            @if ($report->reportBy->tel_no)
                <a href="tel:{{ $report->reportBy->tel_no }}" class="pengadu-contact__row">
                    <i class="bi bi-telephone"></i><span>{{ $report->reportBy->tel_no }}</span>
                </a>
            @endif
        </div>
    @endif
</div>
