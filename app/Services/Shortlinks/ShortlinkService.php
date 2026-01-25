<?php

namespace App\Services\Shortlinks;

use Illuminate\Support\Facades\Http;
use Bitly;

/**
 * Encapsulates shortlink provider fallback so controllers stay thin.
 */
class ShortlinkService
{
    /**
     * Generate a short URL using Bitly first, then TinyURL, else fall back to the original.
     *
     * @return array{0:string,1:?string}
     */
    public function generateShortUrl(string $clientUrl, ?string $preferredProvider = null): array
    {
        $errors = [];
        $preferred = strtolower((string) $preferredProvider);
        $providerOrder = $preferred === 'tinyurl' ? ['tinyurl', 'bitly'] : ['bitly', 'tinyurl'];

        // Try preferred provider first, capture any errors for operator visibility.
        foreach ($providerOrder as $provider) {
            if ($provider === 'bitly' && config('bitly.accesstoken')) {
                try {
                    $bitly = Bitly::getURL($clientUrl);
                    if ($bitly) {
                        return [$bitly, $errors ? implode(' | ', $errors) : null];
                    }
                } catch (\Throwable $e) {
                    $errors[] = 'Bitly: ' . $e->getMessage();
                }
            }

            if ($provider === 'tinyurl' && config('services.tinyurl.token')) {
                try {
                    $response = Http::withToken(config('services.tinyurl.token'))
                        ->acceptJson()
                        ->post('https://api.tinyurl.com/create', [
                            'url' => $clientUrl,
                            'domain' => config('services.tinyurl.domain', 'tinyurl.com'),
                        ]);

                    if ($response->successful()) {
                        $tiny = data_get($response->json(), 'data.tiny_url');
                        if ($tiny) {
                            return [$tiny, $errors ? implode(' | ', $errors) : null];
                        }
                    }

                    $errors[] = 'TinyURL: ' . ($response->body() ?: 'unknown error');
                } catch (\Throwable $e) {
                    $errors[] = 'TinyURL: ' . $e->getMessage();
                }
            }
        }

        return [$clientUrl, $errors ? implode(' | ', $errors) : null];
    }
}
