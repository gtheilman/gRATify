<?php

namespace App\Providers;

use App\Models\Assessment;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Presentation;
use App\Models\User;
use App\Policies\AssessmentPolicy;
use App\Policies\QuestionPolicy;
use App\Policies\AnswerPolicy;
use App\Policies\AttemptPolicy;
use App\Policies\PresentationPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Assessment::class => AssessmentPolicy::class,
        Question::class => QuestionPolicy::class,
        Answer::class => AnswerPolicy::class,
        Attempt::class => AttemptPolicy::class,
        Presentation::class => PresentationPolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
