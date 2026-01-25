<?php

use Illuminate\Support\Facades\File;

it('includes the answer checking overlay and stable hover styles', function () {
    $path = base_path('resources/js/gratclient/components/Answer.vue');
    $content = File::get($path);

    expect($content)->toContain('Checking…');
    expect($content)->toContain('pending-overlay');
    expect($content)->toContain('.answer-wrapper.correct-wrapper');
    expect($content)->toContain('.answer-wrapper.incorrect-wrapper');
    expect($content)->toContain('.answer-wrapper:not(.correct-wrapper):not(.incorrect-wrapper):not(.checking):hover');
});

it('only shows assessment loading state before data is loaded', function () {
    $path = base_path('resources/js/pages/assessments/index.vue');
    $content = File::get($path);

    expect($content)->toContain('v-if="!loaded && !assessments.length"');
    expect($content)->toContain('Loading assessments…');
});

it('only shows user loading state before data is loaded', function () {
    $path = base_path('resources/js/pages/users/index.vue');
    $content = File::get($path);

    expect($content)->toContain('v-if="!loaded && !(usersStore.users || []).length"');
    expect($content)->toContain('Loading users…');
});

it('shows loading placeholders on the assessment password projector view', function () {
    $path = base_path('resources/js/pages/assessments/password.vue');
    $content = File::get($path);

    expect($content)->toContain('Loading link…');
    expect($content)->toContain('Generating QR code…');
});

it('positions the change password card lower on the page', function () {
    $path = base_path('resources/js/pages/change-password.vue');
    $content = File::get($path);

    expect($content)->toContain('margin-top: 100px');
});

it('uses indexedDB-based presentation caching for the student client', function () {
    $path = base_path('resources/js/gratclient/views/Home.vue');
    $content = File::get($path);

    expect($content)->toContain('readPresentationCache');
    expect($content)->toContain('writePresentationCache');
    expect($content)->not->toContain('const getCacheStorage');
});

it('gates the login reset hint on mail configuration', function () {
    $path = base_path('resources/js/pages/login.vue');
    $content = File::get($path);

    expect($content)->toContain('mailCheckLoaded && !mailConfigured');
    expect($content)->toContain('Password reset requires a configured mail server.');
});

it('includes mailConfigured in the demo-warning payload', function () {
    $path = base_path('routes/api.php');
    $content = File::get($path);

    expect($content)->toContain('mailConfigured');
    expect($content)->toContain('showMailpit');
});

it('returns not implemented for the legacy questions index', function () {
    $path = base_path('app/Http/Controllers/QuestionController.php');
    $content = File::get($path);

    expect($content)->toContain('Not implemented.');
    expect($content)->toContain('return response()->json([');
});

it('shows an offline banner in the top nav', function () {
    $path = base_path('resources/js/components/TopNav.vue');
    $content = File::get($path);

    expect($content)->toContain('offlineBannerMessage');
    expect($content)->toContain('Retry');
});

it('includes stale-cache notices on progress, feedback, and scores', function () {
    $progress = File::get(base_path('resources/js/pages/assessments/progress.vue'));
    $feedback = File::get(base_path('resources/js/pages/assessments/feedback.vue'));
    $scores = File::get(base_path('resources/js/pages/assessments/scores.vue'));

    expect($progress)->toContain('applyCachedFallback');
    expect($feedback)->toContain('applyCachedFallback');
    expect($scores)->toContain('applyCachedFallback');
    expect($progress)->toContain('Stale');
    expect($feedback)->toContain('Stale');
    expect($scores)->toContain('Stale');
});

it('provides CSV fallback copy dialog on scores', function () {
    $path = base_path('resources/js/pages/assessments/scores.vue');
    $content = File::get($path);

    expect($content)->toContain('Manual CSV Copy');
    expect($content)->toContain('Automatic download failed');
});

it('adds backup slow notice', function () {
    $path = base_path('resources/js/pages/assessments/index.vue');
    $content = File::get($path);

    expect($content)->toContain('This backup is taking longer than usual.');
});

it('shows a long-running hint during Aiken upload', function () {
    $path = base_path('resources/js/pages/assessments/[id].vue');
    $content = File::get($path);

    expect($content)->toContain('This is taking longer than usual.');
    expect($content)->toContain('Download errors');
});

it('uses the shared error notice component for assessment detail pages', function () {
    $progress = File::get(base_path('resources/js/pages/assessments/progress.vue'));
    $feedback = File::get(base_path('resources/js/pages/assessments/feedback.vue'));
    $scores = File::get(base_path('resources/js/pages/assessments/scores.vue'));

    expect($progress)->toContain('ErrorNotice');
    expect($feedback)->toContain('ErrorNotice');
    expect($scores)->toContain('ErrorNotice');
});
