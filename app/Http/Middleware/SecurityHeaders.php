<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds baseline security response headers to every web response:
 *  - Clickjacking protection (X-Frame-Options + CSP frame-ancestors)
 *  - MIME sniffing protection (X-Content-Type-Options)
 *  - Referrer privacy (Referrer-Policy)
 *  - HSTS (force HTTPS) — only sent over secure connections
 *  - A conservative Content-Security-Policy that still allows the CDN
 *    (Bootstrap), inline scripts/styles used by Livewire/echarts, and data:
 *    images (QR code / inline SVG).
 *
 * CSP note: the app currently relies on inline <script>/<style> (Livewire,
 * echarts, Alpine) so 'unsafe-inline'/'unsafe-eval' are permitted. This still
 * meaningfully restricts external origins. Tightening to nonces is a larger,
 * separate effort.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'X-XSS-Protection' => '0', // modern browsers rely on CSP; explicit off avoids legacy bugs
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
        ];

        // Only advertise HSTS over HTTPS (sending it over HTTP is ignored and
        // could be misleading). 1 year, include subdomains.
        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        $headers['Content-Security-Policy'] = $this->contentSecurityPolicy();

        foreach ($headers as $key => $value) {
            // Don't clobber a header a specific response already set.
            if (! $response->headers->has($key)) {
                $response->headers->set($key, $value);
            }
        }

        return $response;
    }

    protected function contentSecurityPolicy(): string
    {
        $cdn = 'https://cdn.jsdelivr.net';

        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' {$cdn}",
            "style-src 'self' 'unsafe-inline' {$cdn}",
            "font-src 'self' data: {$cdn}",
            "img-src 'self' data:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
    }
}
