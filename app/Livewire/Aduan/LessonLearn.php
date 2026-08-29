<?php

namespace App\Livewire\Aduan;

use App\Models\ReportCategory;
use App\Models\ReportTrail;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Lesson Learn — pangkalan pengetahuan.
 *
 * Senarai boleh cari bagi rekod tindakan yang telah selesai (trail status 9
 * "Selesai Tindakan") yang mengandungi punca masalah + langkah penyelesaian.
 * Membantu pegawai bertanggungjawab merujuk penyelesaian bagi masalah serupa.
 *
 * Carian merentas: punca masalah, langkah penyelesaian, catatan teknikal, dan
 * subjek/tiket aduan. Boleh ditapis mengikut kategori aduan.
 */
class LessonLearn extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $category = '';   // report_categories.id

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'category']);
        $this->resetPage();
    }

    public function render()
    {
        $entries = ReportTrail::query()
            // Only "Selesai Tindakan" trails carry a resolution worth learning.
            ->where('report_trails.status_id', 9)
            ->whereNotNull('punca_masalah')
            ->join('reports', 'reports.id', '=', 'report_trails.report_id')
            ->join('report_categories', 'report_categories.id', '=', 'reports.report_category_id')
            ->with(['report.category', 'updatedBy'])
            ->when($this->category !== '', fn ($q) => $q->where('reports.report_category_id', (int) $this->category))
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('report_trails.punca_masalah', 'like', $term)
                        ->orWhere('report_trails.langkah_tindakan', 'like', $term)
                        ->orWhere('report_trails.catatan_teknikal', 'like', $term)
                        ->orWhere('reports.subject', 'like', $term)
                        ->orWhere('reports.ticket_no', 'like', $term);
                });
            })
            ->select('report_trails.*')
            ->orderByDesc('report_trails.created_at')
            ->paginate(10);

        return view('livewire.aduan.lesson-learn', [
            'entries' => $entries,
            'categories' => ReportCategory::orderBy('desc')->get(),
        ]);
    }
}
