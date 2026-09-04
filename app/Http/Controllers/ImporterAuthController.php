<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Sesión web para el importador. La API es stateless con Sanctum, pero estas vistas
 * necesitan sesión: se reutilizan los mismos usuarios y contraseñas de la API.
 */
class ImporterAuthController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('imports.padron.index');
        }

        return view('imports.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Se acepta correo o usuario: se resuelve primero cuál de los dos es, porque
        // los correos internos ("alguien@local") no pasan FILTER_VALIDATE_EMAIL.
        $usuario = User::where('email', $datos['email'])
            ->orWhere('username', $datos['email'])
            ->first();

        $credenciales = ['email' => $usuario?->email, 'password' => $datos['password']];

        if ($usuario === null || ! Auth::attempt($credenciales, $request->boolean('recordar'))) {
            throw ValidationException::withMessages([
                'email' => 'Esas credenciales no coinciden con ningún usuario.',
            ]);
        }

        if (! Auth::user()->is_active) {
            Auth::logout();

            throw ValidationException::withMessages(['email' => 'Este usuario está desactivado.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('imports.padron.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('imports.login');
    }
}
