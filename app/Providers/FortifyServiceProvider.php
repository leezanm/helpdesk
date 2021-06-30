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
use Adldap\Laravel\Facades\Adldap;
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

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->email . $request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
