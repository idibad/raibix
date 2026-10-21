<?php

namespace App\Policies;

use App\Models\FeeChallan;
use App\Models\User;

class FeeChallanPolicy
{
    /**
     * Determine whether the user can view any fee challans.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->user_type, ['admin', 'principal', 'accountant', 'registrar', 'staff', 'teacher']);
    }

    /**
     * Determine whether the user can view the specific fee challan.
     */
    public function view(User $user, FeeChallan $challan): bool
    {
        // Admin and financial staff can view any challan
        if (in_array($user->user_type, ['admin', 'principal', 'accountant', 'registrar'])) {
            return true;
        }

        // Student can view their own challan
        if ($user->user_type === 'student') {
            return (int) $challan->student_id === (int) $user->student?->id;
        }

        // Parent can view their child's challan
        if (in_array($user->user_type, ['parent', 'guardian'])) {
            $student = $challan->student;
            if (!$student) {
                return false;
            }
            return (int) $student->parent_id === (int) $user->parentsProfile?->id;
        }

        return false;
    }

    /**
     * Determine whether the user can pay the specific fee challan.
     */
    public function pay(User $user, FeeChallan $challan): bool
    {
        return $this->view($user, $challan);
    }

    /**
     * Determine whether the user can update/waive/manage the fee challan.
     */
    public function update(User $user, FeeChallan $challan): bool
    {
        return in_array($user->user_type, ['admin', 'principal', 'accountant']);
    }

    /**
     * Determine whether the user can delete the fee challan.
     */
    public function delete(User $user, FeeChallan $challan): bool
    {
        return in_array($user->user_type, ['admin', 'principal']);
    }
}
