<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Resets the password of named EXISTING accounts to a known demo password, for
 * walking through the system on staging with a copy of real data.
 *
 * This touches real people's accounts, so it is deliberately narrow:
 *   - refuses to run when APP_ENV=production;
 *   - never operates on "all users" — every username must be named;
 *   - prints exactly which accounts and which database, and asks before
 *     writing (skip with --force for scripted runs).
 *
 * It also fills tel_no when empty: no imported account has a phone number, and
 * EnsurePhoneFilled traps every such login on the dashboard behind a locked
 * profile modal, which makes a demo impossible. Pass --skip-phone to leave it.
 */
class ResetDemoPassword extends Command
{
    protected $signature = 'demo:reset-password
                            {username* : Existing usernames to reset}
                            {--password=test1234 : Password to set}
                            {--skip-phone : Do not fill an empty tel_no}
                            {--activate : Also set is_active = 1}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Set semula kata laluan akaun sedia ada untuk demo (bukan untuk produksi)';

    /** Placeholder phone so the "lengkapkan no. telefon" gate lets the demo through. */
    private const PLACEHOLDER_PHONE = '03-80913000';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Arahan ini tidak boleh dijalankan dalam produksi.');

            return self::FAILURE;
        }

        $usernames = array_values(array_unique($this->argument('username')));
        $password = (string) $this->option('password');

        if ($password === '') {
            $this->error('Kata laluan tidak boleh kosong.');

            return self::FAILURE;
        }

        $users = User::whereIn('username', $usernames)->orderBy('username')->get();
        $missing = array_diff($usernames, $users->pluck('username')->all());

        if ($missing) {
            $this->error('Username tidak dijumpai: '.implode(', ', $missing));

            return self::FAILURE;
        }

        $this->warn('Pangkalan data: '.DB::connection()->getDatabaseName()
            .'  (host: '.config('database.connections.'.config('database.default').'.host').')');

        $this->table(
            ['Username', 'Nama', 'Peranan', 'Aktif', 'Tel'],
            $users->map(fn (User $u) => [
                $u->username,
                $u->name,
                $u->getRoleNames()->implode(', ') ?: '-',
                $u->is_active ? 'ya' : 'TIDAK',
                blank($u->tel_no) ? 'kosong' : $u->tel_no,
            ])->all()
        );

        if (! $this->option('force')
            && ! $this->confirm('Set semula kata laluan '.$users->count().' akaun di atas kepada "'.$password.'"?')) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        $hash = Hash::make($password);

        foreach ($users as $user) {
            $user->password = $hash;

            if (! $this->option('skip-phone') && blank($user->tel_no)) {
                $user->tel_no = self::PLACEHOLDER_PHONE;
            }

            if ($this->option('activate')) {
                $user->is_active = true;
            }

            // Any existing session/remember cookie for this account stops working.
            $user->remember_token = null;
            $user->save();
        }

        $this->info($users->count().' akaun dikemaskini. Kata laluan: '.$password);

        return self::SUCCESS;
    }
}
