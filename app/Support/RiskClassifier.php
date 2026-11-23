<?php

namespace App\Support;

/**
 * Suggests a risk level (1..5) for an aduan from its category and the words
 * used in the subject / description.
 *
 * This is a deterministic keyword heuristic, NOT a judgement call: it exists to
 * give historic aduan a plausible starting classification (demo data, and the
 * backlog that predates the risk field). A helpdesk officer can always change
 * the level afterwards on the aduan page, and that decision is never
 * overwritten by this classifier.
 *
 * How a level is reached:
 *   1. Start from the aduan's category baseline (CATEGORY_BASELINE).
 *   2. Raise it if the SUBJECT matches a higher-risk pattern.
 *   3. Raise it if the subject OR the description matches an unambiguous
 *      incident pattern (TEXT_PATTERNS).
 * The level is only ever raised, never lowered — a routine-sounding word must
 * not pull a server or security case down.
 *
 * Why the subject is treated differently from the description: meeting and VC
 * requests carry long descriptions (dates, venues, "secara dalam talian",
 * justifications such as "mengelakkan kebocoran maklumat") whose incidental
 * wording otherwise drags routine requests up several levels. The subject is
 * the reliable signal; only words that are hard to misread — virus, ransomware,
 * server down — are worth hunting for in the description as well.
 */
class RiskClassifier
{
    public const DEFAULT_LEVEL = 1;

    /**
     * Baseline level per report_categories.id.
     *
     *  1 Sangat Rendah  — rutin, impak pada seorang pengguna, tiada isu keselamatan
     *  2 Rendah         — gangguan kecil pada peralatan/perisian pengguna
     *  3 Sederhana      — menjejaskan produktiviti, ketersediaan sistem atau akaun
     *  4 Tinggi         — perkhidmatan penting, keselamatan maklumat, banyak pengguna
     */
    public const CATEGORY_BASELINE = [
        1 => 2,   // Aksesori Komputer
        2 => 2,   // Perisian Komputer
        3 => 2,   // Komputer Meja (Desktop)
        4 => 2,   // Pencetak
        5 => 1,   // Sidang Video (VC)
        6 => 3,   // Rangkaian
        7 => 3,   // Emel
        8 => 4,   // Antivirus
        9 => 1,   // Acara/Mesyuarat
        10 => 3,  // Sistem Aplikasi / Laman Web
        11 => 4,  // Pelayan (Server)
        12 => 1,  // Audio Visual
        13 => 1,  // Projektor
        14 => 1,  // TV
        15 => 2,  // Komputer Riba
        16 => 2,  // SPOT-Me
    ];

    /**
     * Matched against the SUBJECT only, highest level first. Keys are plain
     * lowercase substrings; Malay and English spellings are both listed
     * because both appear in the data.
     *
     * @var array<int, string[]>
     */
    public const SUBJECT_PATTERNS = [
        5 => [
            'ransomware', 'digodam', 'godam', 'hacked', 'dihack', 'kena hack',
            'phishing', 'pancingan data', 'kebocoran', 'data bocor', 'maklumat bocor',
            'serangan siber', 'cyber attack', 'akaun pentadbir', 'admin account',
            'data dicuri', 'fail disulitkan',
        ],
        4 => [
            'virus', 'malware', 'trojan', 'spyware',
            'server down', 'pelayan down', 'server tidak', 'pelayan tidak',
            'sistem lumpuh', 'semua pengguna', 'seluruh bahagian', 'seluruh jabatan',
            'semua pc', 'semua komputer', 'data hilang', 'fail hilang', 'kehilangan data',
            'akaun dicuri', 'capaian tanpa kebenaran', 'akses tanpa kebenaran',
            'emel palsu', 'dokumen sulit', 'maklumat sulit',
        ],
        3 => [
            'tidak boleh log masuk', 'tak boleh log masuk', 'tidak boleh login', 'tak boleh login',
            'lupa password', 'reset password', 'tukar password', 'password',
            'id pegawai lama', 'akaun', 'account',
            'sharefolder', 'share folder', 'shared folder', 'akses folder', 'access folder',
            'internet', 'rangkaian', 'network', 'wifi', 'wi-fi', 'talian terputus',
            'tidak boleh hantar emel', 'emel penuh', 'mailbox',
            'sistem tidak boleh', 'sistem tak boleh', 'tidak boleh daftar',
            'perlahan', 'slow', 'hang', 'terputus',
        ],
        2 => [
            'tidak berfungsi', 'rosak', 'tidak boleh print', 'tak boleh print',
            'install', 'pasang', 'kemaskini', 'update', 'toner', 'dakwat',
        ],
    ];

    /**
     * Matched against the subject AND the description. Deliberately short:
     * only wording that cannot plausibly be incidental prose.
     *
     * @var array<int, string[]>
     */
    public const TEXT_PATTERNS = [
        5 => [
            'ransomware', 'digodam', 'serangan siber', 'akaun pentadbir telah',
        ],
        4 => [
            'virus', 'malware', 'trojan', 'spyware', 'crowdstrike',
            'server down', 'pelayan down', 'sistem lumpuh',
            'semua pengguna', 'semua komputer', 'semua pc',
            'seluruh bahagian', 'seluruh jabatan',
        ],
    ];

    /**
     * @param  int|null  $categoryId  report_categories.id
     * @return int risk level 1..5
     */
    public function classify(?int $categoryId, ?string $subject, ?string $remark = null): int
    {
        $level = self::CATEGORY_BASELINE[$categoryId] ?? self::DEFAULT_LEVEL;

        $subjectText = mb_strtolower(trim((string) $subject));
        $fullText = mb_strtolower(trim((string) $subject.' '.(string) $remark));

        if ($fullText === '') {
            return $level;
        }

        $level = max(
            $level,
            $this->matchHighest(self::SUBJECT_PATTERNS, $subjectText, $level),
            $this->matchHighest(self::TEXT_PATTERNS, $fullText, $level)
        );

        return $level;
    }

    /**
     * Highest pattern level matched in $haystack, or $floor when nothing above
     * $floor matches.
     *
     * @param  array<int, string[]>  $patterns
     */
    private function matchHighest(array $patterns, string $haystack, int $floor): int
    {
        foreach ($patterns as $patternLevel => $needles) {
            if ($patternLevel <= $floor) {
                continue;   // cannot raise it any further
            }

            foreach ($needles as $needle) {
                if (str_contains($haystack, $needle)) {
                    return $patternLevel;
                }
            }
        }

        return $floor;
    }
}
