<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SjrPreviewController extends Controller
{
    public function index(): Response
    {
        $path = resource_path('sjr/prototype.html');
        abort_unless(is_file($path), 404);
        $html = str_replace('__SJR_CSRF_TOKEN__', e(csrf_token()), file_get_contents($path));

        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8')->header('Cache-Control', 'no-store, private')->header('X-Content-Type-Options', 'nosniff');
    }
}
