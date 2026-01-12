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

        // Skip if not authenticated
        if (!$user) {
            return $next($request);
        }

        $currentPanel = Filament::getCurrentPanel();

        // Super admin can access both panels - no restriction
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        // Non-super admin: only allow accounting panel
        if ($currentPanel && $currentPanel->getId() === 'admin') {
            // Redirect non-super admin from admin panel to accounting panel
            return redirect()->to('/accounting');
        }

        return $next($request);
    }
}
