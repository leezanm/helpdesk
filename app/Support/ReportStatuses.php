<?php

namespace App\Support;

/**
 * Canonical report status groupings used by dashboards, listings and laporan.
 *
 * Why this exists: the status ids were repeated as literal arrays in a dozen
 * queries. When the new flow added 7 (Selesai Tindakan) and 8 (Tindakan Lanjut
 * Diperlukan), most of those arrays were left at the old 1..6 range, so the
 * same aduan was counted in one place and dropped in another and the totals
 * disagreed with each other. Every grouping now lives here.
 *
 * Status ids (see ReportStatusSeeder):
 *   1 Draf, 2 Baru, 3 Aduan Disemak, 4 Dalam Tindakan, 5 Selesai,
 *   6 KIV, 7 Selesai Tindakan, 8 Tindakan Lanjut Diperlukan
 */
class ReportStatuses
{
    public const DRAF = 1;
    public const BARU = 2;
    public const DISEMAK = 3;
    public const DALAM_TINDAKAN = 4;
    public const SELESAI = 5;
    public const KIV = 6;
    public const SELESAI_TINDAKAN = 7;
    public const TINDAKAN_LANJUT = 8;

    /**
     * Every aduan that counts as submitted. Draf is excluded: it was never
     * sent, so it is not an aduan from the organisation's point of view.
     */
    public const COUNTED = [
        self::BARU,
        self::DISEMAK,
        self::DALAM_TINDAKAN,
        self::SELESAI,
        self::KIV,
        self::SELESAI_TINDAKAN,
        self::TINDAKAN_LANJUT,
    ];

    /**
     * Still open. Everything submitted except Selesai — including KIV, and
     * including 7/8, which are mid-flow states awaiting helpdesk closure.
     */
    public const IN_PROGRESS = [
        self::BARU,
        self::DISEMAK,
        self::DALAM_TINDAKAN,
        self::KIV,
        self::SELESAI_TINDAKAN,
        self::TINDAKAN_LANJUT,
    ];

    /** Closed. */
    public const DONE = [self::SELESAI];
}
