<div>
    @if ($showModal)
        <div class="modal fade show" style="display:block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content">
                    <form wire:submit="save" enctype="multipart/form-data">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                {{ $categoryId ? 'Kemaskini Maklumat Kategori Aduan' : 'Tambah Kategori Aduan' }}
                            </h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="col-form-label">Nama <span class="text-danger">*</span></label>
                                    <input type="text" wire:model="desc" class="form-control @error('desc') is-invalid @enderror">
                                    @error('desc') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="col-form-label">
                                        Imej @unless($categoryId) <span class="text-danger">*</span> @endunless
                                    </label>
                                    <input type="file" wire:model="file" class="form-control @error('file') is-invalid @enderror">
                                    @error('file') <p class="text-danger">{{ $message }}</p> @enderror
                                    <div wire:loading wire:target="file" class="text-muted small mt-1">Memuat naik...</div>
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
