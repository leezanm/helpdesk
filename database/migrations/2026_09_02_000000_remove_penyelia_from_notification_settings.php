<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The "penyelia" role was removed from the application: it was never part of
 * the new flow spec and never existed in the production role registry.
 *
 * Databases seeded BEFORE that change still carry "penyelia" inside
 * notification_settings.roles. That is not inert: User::role() throws
 * RoleDoesNotExist for an unknown name, so resolving recipients for the
 * affected event aborted the whole request — assigning an officer on an aduan
 * returned a 500.
 *
 * App\Support\NotificationRecipients now filters unknown role names at runtime,
 * so the crash cannot recur. This migration additionally cleans the stored
 * values so the Notification Settings screen does not keep showing a role that
 * no longer exists.
 *
 * Only the names this change removed are pruned — an unrecognised name is left
 * alone rather than silently deleting a recipient configuration.
 */
return new class extends Migration
{
    private const REMOVED_ROLES = ['penyelia'];

    public function up(): void
    {
        $rows = DB::table('notification_settings')->select('id', 'roles')->get();

        foreach ($rows as $row) {
            $roles = json_decode((string) $row->roles, true);

            if (! is_array($roles)) {
                continue;
            }

            $cleaned = array_values(array_diff($roles, self::REMOVED_ROLES));

            if ($cleaned === $roles) {
                continue;
            }

            DB::table('notification_settings')
                ->where('id', $row->id)
                ->update([
                    'roles' => json_encode($cleaned),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // The role no longer exists, so restoring the names would recreate the
        // failure this migration fixes.
    }
};
