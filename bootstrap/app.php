<?php

use App\Console\Commands\BrowserTestingPreflight;
use App\Http\Middleware\BrowserTestingSafetyGuard;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([BrowserTestingPreflight::class])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(BrowserTestingSafetyGuard::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            if (
                ! $request->isMethod('POST')
                || ! preg_match('#\Aproduction/briefs/(\d+)/attachments\z#', $request->path(), $matches)
            ) {
                return null;
            }

            return redirect()
                ->route('prod02.edit', ['brief' => $matches[1], 'step' => 4])
                ->withInput($request->only(['category', 'notes']))
                ->withErrors([
                    'attachment' => 'Permintaan lampiran terlalu besar untuk diterima PHP. File tidak dikirim; pilih file yang lebih kecil. Batas bisnis aplikasi adalah 200 MiB per file.',
                ]);
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
