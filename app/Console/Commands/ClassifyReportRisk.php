<?php

namespace App\Console\Commands;

use App\Models\Ref\RiskLevel;
use App\Models\Report;
use App\Support\RiskClassifier;
use Illuminate\Console\Command;

/**
 * Backfills a suggested risk level on aduan that were never classified by a
 * helpdesk officer, using App\Support\RiskClassifier (category + keywords).
 *
 * Scope rules:
 *   - default: only aduan with NO risk level at all.
 *   - --reclassify: ALSO aduan still sitting on Tahap 1, which is what the
 *     schema migration assigns to every pre-existing aduan. This is the option
 *     to use on a freshly loaded production copy / demo database.
 *   - Aduan already on Tahap 2-5 are never touched: a level above the default
 *     means a human chose it.
 *
 * No trail rows are written — this is a bulk data backfill, not an officer
 * decision, and 4k trail entries would bury the real timeline. Officer changes
 * made through the UI are still logged as usual.
 */
class ClassifyReportRisk extends Command
{
    protected $signature = 'aduan:classify-risk
                            {--dry-run : Show what would change without writing}
                            {--reclassify : Also re-evaluate aduan still on the default Tahap 1}
                            {--chunk=500 : Rows per batch}';

    protected $description = 'Cadangkan tahap risiko bagi aduan berdasarkan kategori dan isi aduan';

    public function handle(RiskClassifier $classifier): int
    {
        $levelsByNumber = RiskLevel::pluck('id', 'level');

        if ($levelsByNumber->isEmpty()) {
            $this->error('Tiada tahap risiko dalam _risk_levels. Jalankan `php artisan migrate` dahulu.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $reclassify = (bool) $this->option('reclassify');
        $defaultLevelId = $levelsByNumber[RiskClassifier::DEFAULT_LEVEL] ?? null;

        $query = Report::query()
            ->select(['id', 'subject', 'remark', 'report_category_id', 'risk_level_id'])
            ->where(function ($q) use ($reclassify, $defaultLevelId) {
                $q->whereNull('risk_level_id');

                if ($reclassify && $defaultLevelId) {
                    $q->orWhere('risk_level_id', $defaultLevelId);
                }
            });

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('Tiada aduan yang perlu dikelaskan.');

            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '').'Mengelaskan '.number_format($total).' aduan...');

        $counts = array_fill(1, 5, 0);
        $changed = 0;
        $bar = $this->output->createProgressBar($total);

        $query->chunkById((int) $this->option('chunk'), function ($reports) use ($classifier, $levelsByNumber, $dryRun, &$counts, &$changed, $bar) {
            foreach ($reports as $report) {
                $level = $classifier->classify(
                    $report->report_category_id,
                    $report->subject,
                    $report->remark
                );

                $counts[$level]++;
                $targetId = $levelsByNumber[$level] ?? null;

                if ($targetId && $targetId !== $report->risk_level_id) {
                    $changed++;

                    if (! $dryRun) {
                        // Direct update: no model events, no trail rows.
                        Report::whereKey($report->id)->update(['risk_level_id' => $targetId]);
                    }
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $names = RiskLevel::orderBy('level')->pluck('desc', 'level');

        $this->table(
            ['Tahap', 'Nama', 'Bilangan', 'Peratus'],
            collect($counts)->map(fn ($count, $level) => [
                $level,
                $names[$level] ?? '-',
                number_format($count),
                number_format($count / $total * 100, 1).'%',
            ])->all()
        );

        $this->info($dryRun
            ? number_format($changed).' aduan akan dikemaskini. Jalankan tanpa --dry-run untuk menyimpan.'
            : number_format($changed).' aduan dikemaskini.');

        return self::SUCCESS;
    }
}
