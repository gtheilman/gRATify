<?php

namespace App\Policies;

use App\Models\Attempt;
use App\Models\User;

class AttemptPolicy
{
    protected function isAdmin(User $user): bool
    {
        return in_array($user->role ?? null, ['admin', 'poobah']);
    }

    public function viewAny(User $user): bool
    {
        return (bool) $user;
    }

    public function view(User $user, Attempt $attempt): bool
    {
        return $this->isAdmin($user) || $attempt->presentation?->assessment?->user_id === $user->id;
    }

    public function create(User $user, Attempt $attempt = null): bool
    {
        $ownerId = $attempt?->presentation?->assessment?->user_id;
        return $this->isAdmin($user) || ($ownerId && $ownerId === $user->id);
    }

    public function update(User $user, Attempt $attempt): bool
    {
        return $this->isAdmin($user) || $attempt->presentation?->assessment?->user_id === $user->id;
    }

    public function delete(User $user, Attempt $attempt): bool
    {
        return $this->isAdmin($user) || $attempt->presentation?->assessment?->user_id === $user->id;
    }
}
