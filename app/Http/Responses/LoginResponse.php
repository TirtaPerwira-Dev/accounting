<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Support\Facades\Auth;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = Auth::user();

        // Super admin goes to admin panel
        if ($user && $user->hasRole('super_admin')) {
            return redirect()->intended('/admin');
        }

        // All other users go to accounting panel
        return redirect()->intended('/accounting');
    }
}
