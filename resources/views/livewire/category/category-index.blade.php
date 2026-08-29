<div>
    <div class="list-toolbar">
        <div class="list-search">
            <i class="bi bi-search"></i>
            <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Cari kategori...">
        </div>
        <a class="btn btn-primary" wire:click="create" role="button">
            <i class="bi bi-plus-lg me-1"></i> Kategori Baru
        </a>
    </div>

    <div class="card data-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:48px">#</th>
                            <th>Nama</th>
                            <th>Imej</th>
                            <th>Status</th>
                            <th class="text-end">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $report_category)
                            <tr wire:key="category-{{ $report_category->id }}">
                                <td class="text-muted">{{ $loop->iteration + ($categories->currentPage() - 1) * $categories->perPage() }}</td>
                                <td class="fw-medium">{{ $report_category->desc }}</td>
                                <td><img src="{{ $report_category->image }}" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:10px;"></td>
                                <td>{!! \App\Support\StatusBadge::active((bool) $report_category->is_active) !!}</td>
                                <td class="text-end text-nowrap">
                                    <a href="#" class="btn btn-sm btn-outline-primary btn-icon" title="Lihat" wire:click.prevent="show({{ $report_category->id }})"><i class="bi bi-eye"></i></a>
                                    @if ($report_category->is_active)
                                        <a href="#" class="btn btn-sm btn-outline-secondary btn-icon" title="Kemaskini" wire:click.prevent="edit({{ $report_category->id }})"><i class="bi bi-pencil"></i></a>
                                        <a href="#" class="btn btn-sm btn-outline-danger btn-icon" title="Nyahaktif" wire:click.prevent="deactivate({{ $report_category->id }})" wire:confirm="Anda pasti untuk menyahaktifkan kategori ini?"><i class="bi bi-lock"></i></a>
                                    @else
                                        <a href="#" class="btn btn-sm btn-outline-success btn-icon" title="Aktifkan" wire:click.prevent="restore({{ $report_category->id }})" wire:confirm="Anda pasti untuk mengaktifkan semula kategori ini?"><i class="bi bi-unlock"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-state"><i class="bi bi-inbox"></i> Tiada kategori dijumpai.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($categories->hasPages())
                <div class="px-2 pt-3">{{ $categories->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Show modal --}}
    @if ($showCategory)
        <div class="modal fade show" style="display:block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Papar Maklumat Kategori Aduan</h5>
                        <button type="button" class="btn-close" wire:click="closeShow" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="{{ $showCategory->image }}" alt="" style="width:96px;height:96px;object-fit:cover;border-radius:16px;" class="mb-3">
                        <h5 class="mb-1">{{ $showCategory->desc }}</h5>
                        <div>{!! \App\Support\StatusBadge::active((bool) $showCategory->is_active) !!}</div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" wire:click="closeShow" type="button">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    @livewire('category.category-form')
</div>
