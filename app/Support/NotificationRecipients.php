<?php

namespace App\Support;

use App\Models\NotificationSetting;
use App\Models\Report;
use App\Models\User;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

/**
 * Resolves WHO should receive a given aduan notification event, based on the
 * superadmin-editable NotificationSetting rows (roles + reporter + assigned
 * officer + extra static emails). Replaces the hardcoded recipient logic.
 *
 * Returns a collection of notifiables (User models and AnonymousNotifiable for
 * extra emails), de-duplicated by email. If the event is disabled or unknown,
 * returns an empty collection so the caller simply sends nothing.
 */
class NotificationRecipients
{
    /**
     * @return Collection<int,mixed> notifiables (User | AnonymousNotifiable)
     */
    public static function for(string $eventKey, ?Report $report = null): Collection
    {
        $setting = NotificationSetting::where('event_key', $eventKey)->first();

        // No config row yet (e.g. before seeding): fall back to defaults so
        // behaviour is preserved.
        if (! $setting) {
            $defaults = NotificationEvents::defaults()[$eventKey] ?? null;
            if (! $defaults) {
                return collect();
            }
            $setting = new NotificationSetting($defaults + ['event_key' => $eventKey]);
        }

        if (! $setting->enabled) {
            return collect();
        }

        /** @var Collection<int,User> $users */
        $users = collect();

        // By role. The configured names are editable data and a role can be
        // deleted after it was chosen, so only names that still exist are
        // queried: User::role() throws RoleDoesNotExist on an unknown name,
        // which would turn a stale recipient setting into a 500 on the aduan
        // action that triggers the notification.
        $roles = self::existingRoles($setting->roles ?: []);
        if (! empty($roles)) {
            $users = $users->merge(
                User::role($roles)->where('is_active', '!=', false)->get()
            );
        }

        // Dynamic parties tied to the aduan.
        if ($report) {
            if ($setting->include_reporter && $report->reportBy) {
                $users->push($report->reportBy);
            }

            if ($setting->include_assigned_officer) {
                $officerId = $report->trails()
                    ->whereNotNull('assigned_to_id')
                    ->orderByDesc('id')
                    ->value('assigned_to_id');
                if ($officerId && ($officer = User::find($officerId))) {
                    $users->push($officer);
                }
            }
        }

        // De-duplicate users by id, drop those without an email.
        $recipients = $users
            ->filter(fn ($u) => filled($u?->email))
            ->unique('id')
            ->values();

        // Extra static addresses as anonymous notifiables (skip ones that
        // duplicate a user we already have).
        $existingEmails = $recipients->pluck('email')->map(fn ($e) => strtolower($e))->all();
        foreach (($setting->extra_emails ?: []) as $email) {
            $email = trim((string) $email);
            if ($email === ''
                || ! filter_var($email, FILTER_VALIDATE_EMAIL)
                || in_array(strtolower($email), $existingEmails, true)) {
                continue;
            }
            $recipients->push((new AnonymousNotifiable)->route('mail', $email));
            $existingEmails[] = strtolower($email);
        }

        return $recipients;
    }

    /**
     * Keep only role names that are registered for the application guard.
     * A name that no longer exists simply matches nobody.
     *
     * @param  array<int,string>  $names
     * @return array<int,string>
     */
    private static function existingRoles(array $names): array
    {
        if (empty($names)) {
            return [];
        }

        // A name registered only for another guard would still throw, so match
        // the guard the User model authenticates with.
        $guard = config('auth.defaults.guard', 'web');

        $known = Role::where('guard_name', $guard)
            ->whereIn('name', $names)
            ->pluck('name')
            ->all();

        return array_values(array_intersect($names, $known));
    }
}
