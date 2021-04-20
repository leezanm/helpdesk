<?php

namespace App\Http\Middleware;

use App\Models\AuditTrail as ModelsAuditTrail;
use Closure;
use Illuminate\Http\Request;

class AuditTrail
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $audit_trail = new ModelsAuditTrail();
        $audit_trail->ip = request()->ip();
        $audit_trail->url = request()->fullUrl();
        $audit_trail->method = request()->method();
        $audit_trail->user_id = optional(request()->user())->id;
        $audit_trail->save();
        return $next($request);
    }
}
