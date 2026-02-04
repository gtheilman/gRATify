<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackOperationalSignals
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $status = $response->getStatusCode();
        $path = $request->path();

        if ($status === 401 && $path === 'api/auth/me') {
            $this->incrementMinuteBucket('auth_me_401');
        }

        if (
            $status === 429
            && $request->isMethod('POST')
            && in_array($path, ['api/attempts', 'api/attempts/bulk'], true)
        ) {
            $this->incrementMinuteBucket('attempts_429');
        }

        return $response;
    }

    protected function incrementMinuteBucket(string $metric): void
    {
        $minute = now()->format('YmdHi');
        $key = "opsig.{$metric}.{$minute}";
        $ttl = now()->addHours(6);

        if (! cache()->has($key)) {
            cache()->put($key, 0, $ttl);
        }

        try {
            cache()->increment($key);
        } catch (\Throwable $e) {
            $value = (int) cache()->get($key, 0);
            cache()->put($key, $value + 1, $ttl);
        }
    }
}
