<?php

namespace Tests\Unit\Support;

use App\Livewire\Laporan\ReportAll;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

/**
 * ReportAll::dateBucketExpr() emits the SQL that groups Carta_Tren rows into
 * daily or monthly buckets. SQLite has no DATE_FORMAT() and MySQL has no
 * strftime(), so the method branches on the driver name.
 *
 * phpunit.xml pins DB_CONNECTION=sqlite, so every query-running test in the
 * suite only ever reaches the strftime branch. This test is therefore the only
 * automated cover for the MySQL DATE_FORMAT branch that production actually
 * runs: it asserts the exact expression string for both drivers so a format
 * typo cannot reach a release unnoticed.
 *
 * The MySQL branch is reached by pointing the default connection at a
 * throwaway connection config whose driver is "mysql". Laravel resolves the
 * PDO lazily, so no MySQL server is needed and no query is executed. The
 * default connection name is restored and the throwaway connection purged
 * after every call, so nothing leaks into other tests.
 *
 * Daily and monthly bucket values must still be verified by hand against the
 * staging MySQL database before release.
 */
class DateBucketExprTest extends TestCase
{
    /** Invoke the protected builder with the default connection on $driver. */
    private function expr(string $driver, string $column, string $granularity): string
    {
        $connection = 'date_bucket_expr_'.$driver;
        $previousDefault = config('database.default');

        config([
            "database.connections.{$connection}" => [
                'driver' => $driver,
                'database' => ':memory:',
                'prefix' => '',
            ],
            'database.default' => $connection,
        ]);

        try {
            $this->assertSame(
                $driver,
                DB::connection()->getDriverName(),
                'The default connection must report the driver under test.'
            );

            $method = new ReflectionMethod(ReportAll::class, 'dateBucketExpr');
            $method->setAccessible(true);

            return $method->invoke(new ReportAll(), $column, $granularity);
        } finally {
            DB::purge($connection);
            config(['database.default' => $previousDefault]);
        }
    }

    public function test_sqlite_daily_bucket_uses_strftime_with_a_full_date_format()
    {
        $this->assertSame(
            "strftime('%Y-%m-%d', submitted_at)",
            $this->expr('sqlite', 'submitted_at', 'day')
        );
    }

    public function test_sqlite_monthly_bucket_uses_strftime_with_a_year_month_format()
    {
        $this->assertSame(
            "strftime('%Y-%m', submitted_at)",
            $this->expr('sqlite', 'submitted_at', 'month')
        );
    }

    public function test_mysql_daily_bucket_uses_date_format_with_a_full_date_format()
    {
        $this->assertSame(
            "DATE_FORMAT(submitted_at, '%Y-%m-%d')",
            $this->expr('mysql', 'submitted_at', 'day')
        );
    }

    public function test_mysql_monthly_bucket_uses_date_format_with_a_year_month_format()
    {
        $this->assertSame(
            "DATE_FORMAT(submitted_at, '%Y-%m')",
            $this->expr('mysql', 'submitted_at', 'month')
        );
    }

    /**
     * The column is interpolated verbatim on both drivers, so a qualified
     * column name stays usable in a joined query.
     */
    public function test_the_column_name_is_interpolated_verbatim_on_both_drivers()
    {
        $this->assertSame(
            "strftime('%Y-%m-%d', reports.submitted_at)",
            $this->expr('sqlite', 'reports.submitted_at', 'day')
        );

        $this->assertSame(
            "DATE_FORMAT(reports.submitted_at, '%Y-%m')",
            $this->expr('mysql', 'reports.submitted_at', 'month')
        );
    }

    /**
     * Only "month" selects the year-month format; trendGranularity() returns
     * "day" for everything else, and the expression must follow that so an
     * unexpected value degrades to daily buckets instead of a wrong grouping.
     */
    public function test_any_granularity_other_than_month_produces_daily_buckets()
    {
        $this->assertSame(
            "strftime('%Y-%m-%d', submitted_at)",
            $this->expr('sqlite', 'submitted_at', 'week')
        );

        $this->assertSame(
            "DATE_FORMAT(submitted_at, '%Y-%m-%d')",
            $this->expr('mysql', 'submitted_at', 'week')
        );
    }
}
