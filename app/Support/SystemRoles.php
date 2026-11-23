<?php

namespace App\Support;

/**
 * Peranan yang dirujuk oleh nama dalam kod (gate, polisi, notifikasi, senarai
 * pengguna). Nama dan kewujudannya tidak boleh diubah atau dipadam melalui
 * skrin Selenggara Peranan. Hanya label dan kebenaran (kecuali superadmin)
 * boleh disunting.
 */
class SystemRoles
{
    public const NAMES = [
        'superadmin',
        'penyelia',
        'helpdesk.admin',
        'pengurusan',
        'jtk',
        'ptm',
        'pptm',
        'pengguna',
    ];

    public static function is(string $name): bool
    {
        return in_array($name, self::NAMES, true);
    }
}
