<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = auth()->user();

        // Super admin goes to admin panel (root)
        if ($user && $user->hasRole('super_admin')) {
            return redirect()->intended('/');
        }

        // All other users go to accounting panel
        return redirect()->intended('/accounting');
    }
}
