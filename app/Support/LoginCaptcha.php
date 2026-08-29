<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * A lightweight, dependency-free math CAPTCHA for the login form.
 *
 * It only kicks in AFTER a few failed attempts (tracked in the session), so
 * normal users are never bothered, but bots/brute-force scripts must solve a
 * fresh arithmetic challenge on each subsequent attempt. No external service
 * (e.g. Google reCAPTCHA) — suitable for an internal government system with
 * restricted outbound access.
 */
class LoginCaptcha
{
    /** Show the CAPTCHA once failed attempts reach this many. */
    public const THRESHOLD = 2;

    private const FAIL_KEY = 'login_fail_count';
    private const ANSWER_KEY = 'login_captcha_answer';

    /** Whether a usable session is bound to the request (false in some tests). */
    private static function hasSession(Request $request): bool
    {
        return $request->hasSession() && $request->getSession()->isStarted();
    }

    public static function failCount(Request $request): int
    {
        if (! self::hasSession($request)) {
            return 0;
        }

        return (int) $request->session()->get(self::FAIL_KEY, 0);
    }

    public static function incrementFailures(Request $request): void
    {
        if (! self::hasSession($request)) {
            return;
        }

        $request->session()->put(self::FAIL_KEY, self::failCount($request) + 1);
    }

    public static function reset(Request $request): void
    {
        if (! self::hasSession($request)) {
            return;
        }

        $request->session()->forget([self::FAIL_KEY, self::ANSWER_KEY]);
    }

    public static function isRequired(Request $request): bool
    {
        return self::failCount($request) >= self::THRESHOLD;
    }

    /**
     * Generate a fresh challenge and store the expected answer in the session.
     * Returns the human-readable question, e.g. "3 + 5".
     */
    public static function generate(Request $request): string
    {
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        if (self::hasSession($request)) {
            $request->session()->put(self::ANSWER_KEY, $a + $b);
        }

        return "{$a} + {$b}";
    }

    /** Validate the submitted answer against the stored one (single-use). */
    public static function check(Request $request, $answer): bool
    {
        if (! self::hasSession($request)) {
            return false;
        }

        $expected = $request->session()->pull(self::ANSWER_KEY);

        return $expected !== null && (int) $answer === (int) $expected;
    }
}
