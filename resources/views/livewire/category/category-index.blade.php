<div>
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
                           placeholder="Cari kategori...">
                </div>
                <div class="col text-end">
                    <div class="button-items">
                        <a class="btn btn-primary btn waves-effect waves-light" wire:click="create" role="button">
                            <i class="bx bxs-plus-square font-size-16 align-middle me-2"></i> Tambah Baru
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <th width="5">Bil</th>
                        <th>Nama</th>
                        <th>Imej</th>
                        <th>Status</th>
                        <th class="text-center" width="10%">Tindakan</th>
                    </thead>
                    <tbody class="align-middle">
                        @forelse ($categories as $report_category)
                            <tr wire:key="category-{{ $report_category->id }}">
                                <td>{{ $loop->iteration + ($categories->currentPage() - 1) * $categories->perPage() }}</td>
                                <td>{{ $report_category->desc }}</td>
                                <td>
                                    <img src="{{ $report_category->image }}" alt="" class="avatar-sm h-auto d-block rounded">
                                </td>
                                <td>{!! \App\Support\StatusBadge::active((bool) $report_category->is_active) !!}</td>
                                <td class="text-center">
                                    <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-info"
                                       wire:click.prevent="show({{ $report_category->id }})"> <i class="mdi mdi-eye"></i></a>
                                    @if ($report_category->is_active)
                                        <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"
                                           wire:click.prevent="edit({{ $report_category->id }})"> <i class="bx bxs-pen"></i></a>
                                        <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-danger"
                                           wire:click.prevent="deactivate({{ $report_category->id }})"
                                           wire:confirm="Anda pasti untuk menyahaktifkan kategori ini?"> <i class="bx bx-lock"></i></a>
                                    @else
                                        <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-success"
                                           wire:click.prevent="restore({{ $report_category->id }})"
                                           wire:confirm="Anda pasti untuk mengaktifkan semula kategori ini?"> <i class="bx bx-lock-open"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Tiada kategori dijumpai.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $categories->links() }}
            </div>
        </div>
    </div>

    {{-- Show modal (Livewire-controlled via Alpine) --}}
    @if ($showCategory)
        <div class="modal fade show" style="display:block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Papar Maklumat Kategori Aduan</h5>
                        <button type="button" class="btn-close" wire:click="closeShow" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-2">
                            <div class="col">
                                <center><img src="{{ $showCategory->image }}" alt="" class="avatar-xl h-auto d-block rounded"></center>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-3"><label><b>Nama : </b></label></div>
                            <div class="col-md-9"><span>{{ $showCategory->desc }}</span></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-3"><label><b>Status : </b></label></div>
                            <div class="col-md-9">{!! \App\Support\StatusBadge::active((bool) $showCategory->is_active) !!}</div>
                        </div>
                    </div>
                    <div class="modal-footer text-end">
                        <button class="btn btn-secondary" wire:click="closeShow" type="button">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    {{-- Create/edit form component --}}
    @livewire('category.category-form')
</div>
