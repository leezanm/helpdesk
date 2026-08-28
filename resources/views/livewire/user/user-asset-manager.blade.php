<div>
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col"><h5 class="mb-0">Aset Pengguna</h5></div>
                <div class="col text-end">
                    <a class="btn btn-primary btn-sm waves-effect waves-light" wire:click="create" role="button">
                        <i class="bx bxs-plus-square font-size-16 align-middle me-2"></i> Aset Baru
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <th>#</th>
                        <th>Jenis</th>
                        <th>Jenama</th>
                        <th>Model</th>
                        <th>No. Siri Pendaftaran</th>
                        <th>No. Siri Pembuat</th>
                        <th class="text-center">Tindakan</th>
                    </thead>
                    <tbody class="align-middle">
                        @forelse ($assets as $asset)
                            <tr wire:key="asset-{{ $asset->id }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $asset->type }}</td>
                                <td>{{ $asset->brand }}</td>
                                <td>{{ $asset->model }}</td>
                                <td>{{ $asset->registration_serial_no }}</td>
                                <td>{{ $asset->supplier_serial_no }}</td>
                                <td class="text-center">
                                    <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"
                                       wire:click.prevent="edit({{ $asset->id }})"> <i class="bx bxs-pen"></i></a>
                                    <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-danger"
                                       wire:click.prevent="delete({{ $asset->id }})"
                                       wire:confirm="Anda pasti untuk memadam aset ini?"> <i class="bx bx-trash"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">Tiada aset.</td></tr>
                        @endforelse
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
                            <h5 class="modal-title">{{ $assetId ? 'Kemaskini Aset' : 'Tambah Aset' }}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @foreach ([
                                'type' => 'Jenis',
                                'brand' => 'Jenama',
                                'model' => 'Model',
                                'registration_serial_no' => 'No. Siri Pendaftaran',
                                'supplier_serial_no' => 'No. Siri Pembuat',
                            ] as $field => $label)
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label class="col-form-label">{{ $label }} <span class="text-danger">*</span></label>
                                        <input type="text" wire:model="{{ $field }}" class="form-control @error($field) is-invalid @enderror">
                                        @error($field) <p class="text-danger">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            @endforeach
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
