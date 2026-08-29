<?php

namespace App\Actions\Fortify;

use App\Support\LoginCaptcha;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Fortify login-pipeline step: when the session has accumulated enough failed
 * attempts, require a correct CAPTCHA answer before credentials are even
 * checked. Runs before AttemptToAuthenticate.
 */
class EnsureLoginCaptcha
{
    public function __invoke(Request $request, $next)
    {
        if (LoginCaptcha::isRequired($request)) {
            $answer = $request->input('captcha');

            if ($answer === null || ! LoginCaptcha::check($request, $answer)) {
                // Count this as another failure so a fresh challenge is issued
                // and the throttle/lockout keeps advancing.
                LoginCaptcha::incrementFailures($request);

                throw ValidationException::withMessages([
                    'captcha' => __('Jawapan pengesahan (CAPTCHA) tidak betul.'),
                ]);
            }
        }

        return $next($request);
    }
}
