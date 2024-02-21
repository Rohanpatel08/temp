<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();
        $user_role = explode(' ',$user->role);
        if ($user && count(array_intersect($roles, $user_role)) > 0) {
            return $next($request);
        }
        return back()->with('error', 'Only Admin can Access User Details.');
    }
}
