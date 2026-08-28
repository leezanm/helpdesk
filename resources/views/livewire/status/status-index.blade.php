<div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <th width="5">Bil</th>
                        <th>Keterangan</th>
                        <th>Nama Kegunaan Sistem</th>
                        <th>Badge Colour</th>
                        <th class="text-center" width="10%">Tindakan</th>
                    </thead>
                    <tbody class="align-middle">
                        @foreach ($report_statuses as $report_status)
                            <tr wire:key="status-{{ $report_status->id }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $report_status->reference }}</td>
                                <td>{{ $report_status->desc }}</td>
                                <td><span class="{{ $report_status->class }} font-size-11">{{ $report_status->desc }}</span></td>
                                <td class="text-center">
                                    <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"
                                       wire:click.prevent="edit({{ $report_status->id }})"> <i class="bx bxs-pen"></i></a>
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
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="col-form-label">Nama untuk kegunaan Sistem</label>
                                    <input type="text" wire:model="reference" class="form-control" disabled>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="col-form-label">Nama untuk kegunaan umum <span class="text-danger">*</span></label>
                                    <input type="text" wire:model="desc" class="form-control @error('desc') is-invalid @enderror">
                                    @error('desc') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer text-end">
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
