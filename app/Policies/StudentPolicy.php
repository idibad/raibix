<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    /**
     * Determine whether the user can view any students.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->user_type, ['admin', 'principal', 'accountant', 'registrar', 'staff', 'teacher']);
    }

    /**
     * Determine whether the user can view the specific student.
     */
    public function view(User $user, Student $student): bool
    {
        if (in_array($user->user_type, ['admin', 'principal', 'staff', 'teacher', 'accountant', 'registrar'])) {
            return true;
        }

        if ($user->user_type === 'student') {
            return (int) $student->id === (int) $user->student?->id;
        }

        if (in_array($user->user_type, ['parent', 'guardian'])) {
            return (int) $student->parent_id === (int) $user->parentsProfile?->id;
        }

        return false;
    }

    /**
     * Determine whether the user can update the student.
     */
    public function update(User $user, Student $student): bool
    {
        return in_array($user->user_type, ['admin', 'principal', 'registrar']);
    }

    /**
     * Determine whether the user can delete the student.
     */
    public function delete(User $user, Student $student): bool
    {
        return in_array($user->user_type, ['admin', 'principal']);
    }
}
