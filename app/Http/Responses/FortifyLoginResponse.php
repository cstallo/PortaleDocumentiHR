<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class FortifyLoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $user = $request->user();

        if ($user?->isDipendente()) {
            $request->session()->forget('url.intended');

            return redirect()->route('documenti.index');
        }

        return redirect()->intended('/admin');
    }
}
