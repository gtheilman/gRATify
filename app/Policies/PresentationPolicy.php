<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\Presentation;
use App\Models\User;

class PresentationPolicy
{
    /**
     * Admins can view presentation aggregates.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Allow viewing if admin or the owner of the linked assessment.
     */
    public function view(User $user, Presentation $presentation): bool
    {
        return $user->isAdmin() || $presentation->assessment?->user_id === $user->id;
    }

    /**
     * Allow assessment owners (or admins) to view presentation data for their assessment.
     */
    public function viewForAssessment(User $user, Assessment $assessment): bool
    {
        return $user->isAdmin() || $assessment->user_id === $user->id;
    }
}
