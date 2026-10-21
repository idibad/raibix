<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;

class AssignmentPolicy
{
    /**
     * Determine whether the user can view the assignment.
     */
    public function view(User $user, Assignment $assignment): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create an assignment.
     */
    public function create(User $user): bool
    {
        return in_array($user->user_type, ['teacher', 'staff', 'admin', 'principal']);
    }

    /**
     * Determine whether the user can update the assignment.
     */
    public function update(User $user, Assignment $assignment): bool
    {
        if (in_array($user->user_type, ['admin', 'principal'])) {
            return true;
        }

        return (int) $assignment->teacher_id === (int) $user->id;
    }

    /**
     * Determine whether the user can grade submissions for this assignment.
     */
    public function grade(User $user, Assignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }
}
