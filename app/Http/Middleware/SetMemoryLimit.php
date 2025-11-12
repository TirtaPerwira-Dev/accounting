<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetMemoryLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Set memory limit from config
        $memoryLimit = config('memory.memory_limit', '512M');
        $maxExecutionTime = config('memory.max_execution_time', 300);

        // Apply memory settings
        ini_set('memory_limit', $memoryLimit);
        ini_set('max_execution_time', $maxExecutionTime);

        // For development debugging
        if (config('app.debug')) {
            $currentMemoryLimit = ini_get('memory_limit');
            logger()->info("Memory limit set to: {$currentMemoryLimit}");
        }

        return $next($request);
    }
}
