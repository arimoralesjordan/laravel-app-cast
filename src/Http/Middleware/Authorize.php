<?php

namespace Laravel\Telescope\Http\Middleware;

use Laravel\Telescope\LaravelAppCast;

class Authorize
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response
     */
    public function handle($request, $next)
    {
        return LaravelAppCast::check($request) ? $next($request) : abort(403);
    }
}
