<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordChangeController extends Controller
{
    public function show()
    {
        return view('auth.cambio-password');
    }

    public function update(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'password.confirmed' => 'La conferma password non coincide.',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'La password temporanea non è corretta.',
            ]);
        }

        $user->update([
            'password'             => $request->password, // hashata dal cast
            'must_change_password' => false,
        ]);

        $dest = $user->isDipendente() ? '/documenti' : '/admin';

        return redirect($dest)->with('status', 'Password aggiornata con successo.');
    }
}
