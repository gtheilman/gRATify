<?php

use Illuminate\Support\Facades\File;

it('shows appeals controls on scores page', function () {
    $path = resource_path('js/pages/assessments/scores.vue');
    $content = File::get($path);

    expect($content)->toContain('Show Appeals');
    expect($content)->toContain('Show Responses');
});

it('renders appeal toolbar on the client assessment view', function () {
    $path = resource_path('js/gratclient/components/Assessment.vue');
    $content = File::get($path);

    expect($content)->toContain('Appeal · Question');
    expect($content)->toContain('appeal-toolbar');
    expect($content)->toContain('v-if="appealsOpen"');
});
