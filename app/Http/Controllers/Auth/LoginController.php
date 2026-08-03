<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(Request $request): View
    {
        $panel = str_starts_with($request->path(), 'accounting') ? 'accounting' : 'admin';

        return view('auth.login', [
            'panel' => $panel,
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
            'panel' => ['nullable', 'in:admin,accounting'],
        ]);

        $credentials = [
            filter_var($validated['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username' => $validated['login'],
            'password' => $validated['password'],
        ];

        $remember = (bool) ($validated['remember'] ?? false);

        if (! Auth::guard('web')->attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'login' => 'Username/email atau password salah.',
            ]);
        }

        $request->session()->regenerate();

        $fallback = ($validated['panel'] ?? 'admin') === 'accounting' ? '/accounting' : '/';

        return redirect()->intended($fallback);
    }
}
