<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FilesRedirectIfNoAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $userTeamId = Auth::user()->team->id ?? null;
        $requestTeam = explode('/', $request->getRequestUri())[2];

        if ((Auth::check() && $userTeamId) && ($requestTeam == 'team-'.$userTeamId || auth()->user()->hasRole(['super_admin']))) {
            return $next($request);
        }

        return redirect('/admin');
    }
}
