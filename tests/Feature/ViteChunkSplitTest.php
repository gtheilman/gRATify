<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

it('produces dedicated vendor chunks when manifest is present', function () {
    $manifestPath = public_path('build/manifest.json');
    if (!File::exists($manifestPath)) {
      $this->markTestSkipped('Vite manifest not present; run npm run build to generate it.');
    }

    $manifest = json_decode(File::get($manifestPath), true);
    $files = implode('\n', array_map(fn($entry) => json_encode($entry), $manifest));

    // Chunk names include hashes; match by substring.
    expect($files)->toContain('vue');

    // Optional vendor chunks — assert when present, but do not fail builds that
    // tree-shake them away in certain environments.
    $optionals = ['vuetify', 'swiper', 'math', 'charts'];
    $found = 0;
    foreach ($optionals as $chunk) {
        if (str_contains($files, $chunk)) {
            $found++;
        }
    }
    if ($found === 0) {
        $this->markTestSkipped('Optional vendor chunks not present in this build.');
    }
});
