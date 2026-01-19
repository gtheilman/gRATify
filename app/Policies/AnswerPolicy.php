<?php

namespace App\Policies;

use App\Models\Answer;
use App\Models\User;

class AnswerPolicy
{
    protected function isAdmin(User $user): bool
    {
        return in_array($user->role ?? null, ['admin', 'poobah']);
    }

    public function viewAny(User $user): bool
    {
        return (bool) $user;
    }

    public function view(User $user, Answer $answer): bool
    {
        return $this->isAdmin($user) || $answer->question?->assessment?->user_id === $user->id;
    }

    public function create(User $user, Answer $answer = null): bool
    {
        $ownerId = $answer?->question?->assessment?->user_id;
        return $this->isAdmin($user) || ($ownerId && $ownerId === $user->id);
    }

    public function update(User $user, Answer $answer): bool
    {
        return $this->isAdmin($user) || $answer->question?->assessment?->user_id === $user->id;
    }

    public function delete(User $user, Answer $answer): bool
    {
        return $this->isAdmin($user) || $answer->question?->assessment?->user_id === $user->id;
    }
}
