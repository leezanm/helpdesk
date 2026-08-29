<div>
    <div class="card data-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:48px">#</th>
                            <th>Keterangan</th>
                            <th>Nama Kegunaan Sistem</th>
                            <th>Paparan Badge</th>
                            <th class="text-end">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report_statuses as $report_status)
                            <tr wire:key="status-{{ $report_status->id }}">
                                <td class="text-muted">{{ $loop->iteration }}</td>
                                <td>{{ $report_status->reference }}</td>
                                <td class="fw-medium">{{ $report_status->desc }}</td>
                                <td><span class="{{ $report_status->class }}">{{ $report_status->desc }}</span></td>
                                <td class="text-end">
                                    <a href="#" class="btn btn-sm btn-outline-secondary btn-icon" title="Kemaskini" wire:click.prevent="edit({{ $report_status->id }})"><i class="bi bi-pencil"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($showModal)
        <div class="modal fade show" style="display:block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content">
                    <form wire:submit="save">
                        <div class="modal-header">
                            <h5 class="modal-title">Kemaskini Status Aduan</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Nama untuk kegunaan Sistem</label>
                                <input type="text" wire:model="reference" class="form-control" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nama untuk kegunaan umum <span class="text-danger">*</span></label>
                                <input type="text" wire:model="desc" class="form-control @error('desc') is-invalid @enderror">
                                @error('desc') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" type="button" wire:click="closeModal">Batal</button>
                            <button class="btn btn-success" type="submit" wire:loading.attr="disabled">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
