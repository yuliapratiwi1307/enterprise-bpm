<?php

namespace App\Http\Middleware;

use App\Services\BrowserTestingRuntimeGuard;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class BrowserTestingSafetyGuard
{
    public function __construct(private readonly BrowserTestingRuntimeGuard $guard) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->guard->isExplicitlyEnabled()) {
            return $next($request);
        }

        try {
            $this->guard->verify();
        } catch (Throwable $exception) {
            Log::critical('Browser testing runtime guard rejected the request.', [
                'exception' => $exception::class,
            ]);

            return response('Browser testing safety verification failed.', 503);
        }

        return $next($request);
    }
}
