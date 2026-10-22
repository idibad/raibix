<?php

namespace App\Policies;

use App\Models\ParentsProfile;
use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    /**
     * Check user role or user_type.
     */
    protected function hasRoleOrType(User $user, array $roles, array $types): bool
    {
        $userType = strtolower($user->user_type ?? '');
        if (in_array($userType, array_map('strtolower', $types), true)) {
            return true;
        }

        if (method_exists($user, 'hasAnyRole')) {
            try {
                if ($user->hasAnyRole($roles)) {
                    return true;
                }
            } catch (\Throwable $e) {}
        }

        return false;
    }

    /**
     * Determine whether the user can view any students.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasRoleOrType(
            $user,
            ['Super Admin', 'Principal', 'Admin', 'Accountant', 'Registrar', 'Staff', 'Teacher'],
            ['admin', 'principal', 'accountant', 'registrar', 'staff', 'teacher']
        );
    }

    /**
     * Determine whether the user can view the specific student.
     */
    public function view(User $user, Student $student): bool
    {
        if ($this->hasRoleOrType(
            $user,
            ['Super Admin', 'Principal', 'Admin', 'Accountant', 'Registrar', 'Staff', 'Teacher'],
            ['admin', 'principal', 'accountant', 'registrar', 'staff', 'teacher']
        )) {
            return true;
        }

        if ($this->hasRoleOrType($user, ['Student'], ['student'])) {
            $studentId = $user->student?->id;
            if (!$studentId) {
                $studentModel = Student::where('user_id', $user->id)->first();
                $studentId = $studentModel?->id;
            }

            return (int) $student->id === (int) $studentId;
        }

        if ($this->hasRoleOrType($user, ['Parent', 'Guardian'], ['parent', 'guardian'])) {
            $parentProfileId = $user->parentsProfile?->id;
            if (!$parentProfileId) {
                $profile = ParentsProfile::where('user_id', $user->id)->first();
                $parentProfileId = $profile?->id;
            }

            return (int) $student->parent_id === (int) $parentProfileId;
        }

        return false;
    }

    /**
     * Determine whether the user can update the student.
     */
    public function update(User $user, Student $student): bool
    {
        return $this->hasRoleOrType(
            $user,
            ['Super Admin', 'Principal', 'Admin', 'Registrar'],
            ['admin', 'principal', 'registrar']
        );
    }

    /**
     * Determine whether the user can delete the student.
     */
    public function delete(User $user, Student $student): bool
    {
        return $this->hasRoleOrType(
            $user,
            ['Super Admin', 'Principal', 'Admin'],
            ['admin', 'principal']
        );
    }
}
