<?php

namespace App\Support;

/**
 * Canonical list of configurable aduan email-notification events, and the
 * DEFAULT recipient configuration for each (which mirrors the previous
 * hardcoded behaviour, so seeding introduces no behavioural change).
 *
 * Each default: [label, enabled, roles[], include_reporter, include_assigned_officer, extra_emails[]]
 * "aduan.adminTask holders" is expressed via roles so a superadmin can edit
 * the recipients later from /tetapan/notifikasi.
 */
class NotificationEvents
{
    public const ADUAN_DIHANTAR_PENGADU = 'aduan_dihantar_pengadu';
    public const ADUAN_DITERIMA_ADMIN = 'aduan_diterima_admin';
    public const ADUAN_DITUGASKAN = 'aduan_ditugaskan';
    public const ADUAN_SELESAI_TINDAKAN = 'aduan_selesai_tindakan';
    public const ADUAN_SELESAI_ADMIN = 'aduan_selesai_admin';
    public const ADUAN_SELESAI_PENGADU = 'aduan_selesai_pengadu';

    /** @return array<string,array<string,mixed>> keyed by event_key */
    public static function defaults(): array
    {
        $adminRoles = ['helpdesk.admin', 'penyelia'];

        return [
            self::ADUAN_DIHANTAR_PENGADU => [
                'label' => 'Aduan Dihantar — makluman kepada pengadu',
                'enabled' => true,
                'roles' => [],
                'include_reporter' => true,
                'include_assigned_officer' => false,
                'extra_emails' => [],
            ],
            self::ADUAN_DITERIMA_ADMIN => [
                'label' => 'Aduan Baru Diterima — makluman kepada helpdesk',
                'enabled' => true,
                'roles' => $adminRoles,
                'include_reporter' => false,
                'include_assigned_officer' => false,
                'extra_emails' => [],
            ],
            self::ADUAN_DITUGASKAN => [
                'label' => 'Aduan Ditugaskan kepada Pegawai',
                'enabled' => true,
                // Assigned officer only. Add helpdesk.admin from
                // /tetapan/notifikasi if a supervisor copy is wanted.
                'roles' => [],
                'include_reporter' => false,
                'include_assigned_officer' => true,
                'extra_emails' => [],
            ],
            self::ADUAN_SELESAI_TINDAKAN => [
                'label' => 'Pegawai Selesai Tindakan / Perlu Tindakan Lanjut',
                'enabled' => true,
                'roles' => $adminRoles,
                'include_reporter' => false,
                'include_assigned_officer' => false,
                'extra_emails' => [],
            ],
            self::ADUAN_SELESAI_ADMIN => [
                'label' => 'Aduan Selesai (Ditutup) — makluman kepada helpdesk',
                'enabled' => true,
                'roles' => $adminRoles,
                'include_reporter' => false,
                'include_assigned_officer' => false,
                'extra_emails' => [],
            ],
            self::ADUAN_SELESAI_PENGADU => [
                'label' => 'Aduan Selesai (Ditutup) — makluman kepada pengadu',
                'enabled' => true,
                'roles' => [],
                'include_reporter' => true,
                'include_assigned_officer' => false,
                'extra_emails' => [],
            ],
        ];
    }
}
