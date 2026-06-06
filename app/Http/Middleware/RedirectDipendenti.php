<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectDipendenti
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isDipendente() && $request->is('admin', 'admin/*')) {
            Auth::guard('web')->logout();

            $request->session()->forget('url.intended');
            $request->session()->flush();
            $request->session()->regenerateToken();
            $request->session()->flash('error', 'Non puoi entrare qui. Usa la login utenti.');

            return redirect()->route('login');
        }

        return $next($request);
    }
}
