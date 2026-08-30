<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\EnsureLoginCaptcha;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Support\LoginCaptcha;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Fortify;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::loginView(function (Request $request) {
            // If enough attempts have failed, issue a fresh CAPTCHA challenge.
            $captchaQuestion = LoginCaptcha::isRequired($request)
                ? LoginCaptcha::generate($request)
                : null;

            // Live headline stats for the hero panel (cached 10 min so the login
            // page stays fast and the DB isn't hit on every visit).
            $stats = \Illuminate\Support\Facades\Cache::remember('login_stats', 600, function () {
                $total = (int) \App\Models\Report::where('report_status_id', '<>', 1)->count();
                $resolved = (int) \App\Models\Report::where('report_status_id', 5)->count();

                return [
                    'total' => $total,
                    'resolved' => $resolved,
                    'rate' => $total > 0 ? (int) round($resolved / $total * 100) : 0,
                    'bahagian' => (int) \App\Models\Report::whereNotNull('bahagian_pengadu')
                        ->where('bahagian_pengadu', '<>', '')->distinct()->count('bahagian_pengadu'),
                ];
            });

            return view('auth.login', compact('captchaQuestion', 'stats'));
        });

        // Custom login pipeline: require a CAPTCHA (after N failed attempts)
        // BEFORE credentials are checked. Otherwise identical to Fortify's
        // default pipeline (throttle -> canonicalize -> authenticate ->
        // prepare session). 2FA is disabled in config so it is omitted.
        Fortify::authenticateThrough(fn (Request $request) => [
            EnsureLoginIsNotThrottled::class,
            EnsureLoginCaptcha::class,
            CanonicalizeUsername::class,
            AttemptToAuthenticate::class,
            PrepareAuthenticatedSession::class,
        ]);

        // Fortify::authenticateUsing(function (Request $request) {
        //     $user = User::where('username', $request->username)->first();

        //     if ($user &&
        //         Hash::check($request->password, $user->password)) {
        //         return $user;
        //     } else {
        //         $authenticate = Adldap::auth()->attempt($request->username,$request->password,$bindAsUser = false);

        //         if($authenticate){

        //         }
        //         // $ad->addProvider(config('ldap')['connections']['default']['settings']);

        //         try{
        //             $provider = $ad->connect();
        //             $connected = true;
        //         } catch(Exception $e){
        //             return response()->json(['success' => false, 'message' => 'Tidak dapat connect ke AD. Sila hubungi admin']);
        //         }

        //         if($connected){
        //             $ldap_user = $provider->search()->select(['cn', 'physicaldeliveryofficename', 'userprincipalname'])->findBy('samaccountname', $request->bp_username);
        //         }
        //     }
        // });

        // Lockout: 3 failed attempts, then blocked for 5 minutes. Keyed on the
        // username + IP so one attacker cannot lock out a whole office, and a
        // single IP hammering many usernames is still limited. (Previous code
        // keyed on $request->email, which is always empty here since the login
        // field is 'username' — so it effectively only throttled by IP.)
        // Lockout: 3 failed attempts from an IP -> that IP is blocked for 5
        // minutes, REGARDLESS of which username is tried. Keying on IP alone
        // means an attacker cannot bypass the block by rotating usernames.
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinutes(5, 3)->by('login|'.$request->ip())->response(function () {
                return redirect()->route('login')->withErrors([
                    'username' => 'Terlalu banyak percubaan log masuk dari peranti ini. Sila cuba semula selepas 5 minit.',
                ]);
            });
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
