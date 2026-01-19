<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ApplicationController extends Controller
{
    public function __invoke(Request $request)
    {
        $path = $request->path();

        // Block legacy prefixes from the old multi-SPA deployment.
        if (str_starts_with($path, 'gratserver') || str_starts_with($path, 'gratclient')) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $response = response()->view('application');

        // Student client pages can be cached briefly for faster initial load.
        if (str_starts_with($path, 'client')) {
            $response->headers->set('Cache-Control', 'public, max-age=600, stale-while-revalidate=60');
        }

        // Hint that static assets can be cached aggressively when proxied directly.
        if ($request->is('build/*') || $request->is('assets/*')) {
            $response->headers->set('Cache-Control', 'public, max-age=' . (60 * 60 * 24 * 365) . ', immutable');
        }

        return $response;
    }
}
