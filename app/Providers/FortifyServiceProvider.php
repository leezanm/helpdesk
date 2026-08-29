<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
        Fortify::loginView(fn () => view('auth.login'));

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
        RateLimiter::for('login', function (Request $request) {
            $key = Str::lower((string) $request->input('username')).'|'.$request->ip();

            return Limit::perMinutes(5, 3)->by($key)->response(function () {
                return redirect()->route('login')->withErrors([
                    'username' => 'Terlalu banyak percubaan log masuk. Sila cuba semula selepas 5 minit.',
                ]);
            });
        });

        // Secondary throttle per-IP so an attacker rotating usernames from one
        // IP is still capped (defence against credential stuffing).
        RateLimiter::for('login-ip', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
