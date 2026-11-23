<!doctype html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Aduan {{ $report->ticket_no }} — KKDW Helpdesk</title>
    <link rel="shortcut icon" href="{{ asset('images/logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400..800&display=swap" rel="stylesheet">
    <style>
        :root { --c-primary:#5a68cf; --c-primary-700:#3b4690; --c-butter:#e0ad46; --c-border:#e4e8f6; }
        body { background:#f7f8fe; font-family:'Figtree',system-ui,-apple-system,"Segoe UI",Roboto,sans-serif; color:#1c2138; }
        .pv-wrap { max-width:760px; margin:0 auto; padding:1.5rem 1rem 3rem; }
        .pv-header { background:linear-gradient(135deg,var(--c-primary) 0%,#8b97e8 70%,#f2cd7e 140%); color:#fff;
            border-radius:18px 18px 0 0; padding:1.5rem 1.75rem; }
        .pv-header img { width:44px;height:44px;border-radius:12px;background:#fff;padding:6px;object-fit:contain; }
        .pv-card { background:#fff; border:1px solid var(--c-border); border-top:0; border-radius:0 0 18px 18px;
            padding:1.75rem; box-shadow:0 12px 30px rgba(90,104,207,0.10); }
        .pv-row { display:flex; gap:1rem; padding:0.7rem 0; border-bottom:1px solid var(--c-border); }
        .pv-row:last-child { border-bottom:0; }
        .pv-label { flex:0 0 150px; color:#6a7089; font-size:.82rem; text-transform:uppercase; letter-spacing:.4px; font-weight:600; }
        .pv-value { flex:1; font-weight:500; word-break:break-word; }
        .pv-badge { display:inline-block; padding:.3rem .7rem; border-radius:999px; font-size:.8rem; font-weight:600; }
        .pv-remark { background:#f3f5fd; border:1px solid var(--c-border); border-radius:10px; padding:1rem; margin-top:.35rem; }
        .pv-contact a { color:var(--c-primary-700); text-decoration:none; }
        .pv-note { font-size:.82rem; color:#6a7089; }
    </style>
</head>
<body>
<div class="pv-wrap">
    <div class="pv-header d-flex align-items-center gap-3">
        <img src="{{ asset('images/logo.png') }}" alt="KKDW">
        <div>
            <div class="fw-bold fs-5">Sistem Meja Bantuan KKDW</div>
            <div style="opacity:.9">Butiran Aduan (Pratonton)</div>
        </div>
    </div>

    <div class="pv-card">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
            <h1 class="h4 fw-bold mb-0">{{ $report->ticket_no }}</h1>
            <span class="pv-badge" style="background:{{ '#eaedfc' }};color:{{ '#4854a8' }}">
                {{ $report->status->desc ?? '-' }}
            </span>
        </div>

        <div class="pv-row">
            <div class="pv-label">Subjek</div>
            <div class="pv-value">{{ $report->subject }}</div>
        </div>
        <div class="pv-row">
            <div class="pv-label">Kategori</div>
            <div class="pv-value">{{ $report->category->desc ?? '-' }}</div>
        </div>
        <div class="pv-row">
            <div class="pv-label">Bahagian</div>
            <div class="pv-value">{{ $report->bahagian_pengadu ?: '-' }}</div>
        </div>
        <div class="pv-row">
            <div class="pv-label">Tarikh Aduan</div>
            <div class="pv-value">{{ convertDateFormat($report->submitted_at, 'LL, h:mm A') }}</div>
        </div>
        <div class="pv-row pv-contact">
            <div class="pv-label">Pengadu</div>
            <div class="pv-value">
                <div>{{ $report->reportBy->name ?? '-' }}</div>
                @if ($report->reportBy?->email)
                    <div><i class="bi bi-envelope me-1"></i><a href="mailto:{{ $report->reportBy->email }}">{{ $report->reportBy->email }}</a></div>
                @endif
                @if ($report->reportBy?->tel_no)
                    <div><i class="bi bi-telephone me-1"></i><a href="tel:{{ $report->reportBy->tel_no }}">{{ $report->reportBy->tel_no }}</a></div>
                @endif
            </div>
        </div>
        <div class="pv-row d-block">
            <div class="pv-label mb-1">Butiran / Ringkasan Aduan</div>
            <div class="pv-value">
                <div class="pv-remark">{!! nl2br(e(strip_tags($report->remark))) !!}</div>
            </div>
        </div>

        @if ($report->attachments->count())
            <div class="pv-row d-block">
                <div class="pv-label mb-1">Lampiran</div>
                <div class="pv-value">
                    @foreach ($report->attachments as $att)
                        <div><i class="bi bi-paperclip me-1"></i>{{ $att->name }}
                            <span class="text-muted small">({{ $att->type }})</span></div>
                    @endforeach
                    <div class="pv-note mt-1">Lampiran boleh dilihat selepas log masuk ke sistem.</div>
                </div>
            </div>
        @endif

        <div class="d-grid gap-2 d-sm-flex justify-content-sm-end mt-4">
            <a href="{{ route('login') }}" class="btn btn-primary" style="background:var(--c-primary);border-color:var(--c-primary)">
                <i class="bi bi-box-arrow-in-right me-1"></i> Log Masuk untuk Ambil Tindakan
            </a>
        </div>

        <p class="pv-note text-center mt-3 mb-0">
            Pratonton ini untuk rujukan pantas sahaja. Sila log masuk ke sistem untuk mengemaskini status atau mengambil tindakan.
        </p>
    </div>
</div>
</body>
</html>
