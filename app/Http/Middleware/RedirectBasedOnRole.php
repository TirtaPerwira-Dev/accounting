<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Filament\Facades\Filament;

class RedirectBasedOnRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->hasRole('super_admin')) {
            // Super admin can access both panels
            // If on admin panel, stay there
            // If on accounting panel, allow access
            return $next($request);
        }

        if ($user && !$user->hasRole('super_admin')) {
            // Non-super admin users should only access accounting panel
            $currentPanel = Filament::getCurrentPanel();

            if ($currentPanel && $currentPanel->getId() === 'admin') {
                // Redirect to accounting panel
                return redirect()->to('/accounting');
            }
        }

        return $next($request);
    }
}
