<div>
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
                           placeholder="Cari tiket / subjek...">
                </div>
                <div class="col text-end">
                    <a class="btn btn-primary btn waves-effect waves-light" href="{{ url('/aduan/create') }}">
                        <i class="bx bxs-plus-square font-size-16 align-middle me-2"></i> Tambah
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered nowrap w-100">
                    <thead class="text-center align-middle">
                        <th>No</th>
                        <th style="cursor:pointer" wire:click="sortBy('ticket_no')">Tiket</th>
                        <th style="cursor:pointer" wire:click="sortBy('subject')">Subjek</th>
                        <th>Kategori Aduan</th>
                        @if ($canAdmin)
                            <th>Pengadu</th>
                        @endif
                        <th style="cursor:pointer" wire:click="sortBy('submitted_at')">Tarikh Aduan</th>
                        <th>Durasi</th>
                        <th>Status</th>
                        <th class="text-center">Tindakan</th>
                    </thead>
                    <tbody class="text-center align-middle">
                        @forelse ($reports as $report)
                            <tr wire:key="report-{{ $report->id }}">
                                <td>{{ $loop->iteration + ($reports->currentPage() - 1) * $reports->perPage() }}</td>
                                <td>{{ $report->ticket_no }}</td>
                                <td class="text-start">{{ $report->subject }}</td>
                                <td>{{ $report->category->desc ?? '-' }}</td>
                                @if ($canAdmin)
                                    <td>
                                        <a data-userId="{{ $report->reportBy->id ?? '' }}" class="userProfile" role="button">{{ $report->reportBy->name ?? '-' }}</a>
                                        <br>
                                        {{ $report->reportBy->ldap_bahagian ?? '' }}
                                    </td>
                                @endif
                                <td>{{ convertDateFormat($report->submitted_at, 'LL') }}</td>
                                <td>{!! getDuration($report) !!}</td>
                                <td><span class="{{ $report->status->class }} font-size-11">{{ $report->status->desc }}</span></td>
                                <td>
                                    <a href="{{ route('aduan.show', $report->id) }}" class="btn btn-sm btn-rounded rounded-circle btn-outline-info"> <i class="bx bxs-show align-middle"></i></a>
                                    @if ($report->report_status_id == 1)
                                        <a href="{{ route('aduan.edit', $report->id) }}" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"> <i class="bx bxs-pen align-middle"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $canAdmin ? 9 : 8 }}" class="text-center text-muted">Tiada aduan dijumpai.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $reports->links() }}
            </div>
        </div>
    </div>
</div>
