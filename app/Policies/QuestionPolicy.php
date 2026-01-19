<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\Assessment;
use App\Models\User;

class QuestionPolicy
{
    protected function isAdmin(User $user): bool
    {
        return in_array($user->role ?? null, ['admin', 'poobah']);
    }

    public function viewAny(User $user): bool
    {
        return (bool) $user;
    }

    public function view(User $user, Question $question): bool
    {
        return $this->isAdmin($user) || $question->assessment?->user_id === $user->id;
    }

    public function create(User $user, Assessment $assessment): bool
    {
        return $this->isAdmin($user) || $assessment->user_id === $user->id;
    }

    public function update(User $user, Question $question): bool
    {
        return $this->isAdmin($user) || $question->assessment?->user_id === $user->id;
    }

    public function delete(User $user, Question $question): bool
    {
        return $this->isAdmin($user) || $question->assessment?->user_id === $user->id;
    }
}
