<?php

namespace App\Policies;

use App\Models\FeeChallan;
use App\Models\ParentsProfile;
use App\Models\Student;
use App\Models\User;

class FeeChallanPolicy
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
            } catch (\Throwable $e) {
                // Table or permission package fallback
            }
        }

        return false;
    }

    /**
     * Determine whether the user can view any fee challans.
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
     * Determine whether the user can view the specific fee challan.
     */
    public function view(User $user, FeeChallan $challan): bool
    {
        // Admin and financial staff can view any challan
        if ($this->hasRoleOrType(
            $user,
            ['Super Admin', 'Principal', 'Admin', 'Accountant', 'Registrar'],
            ['admin', 'principal', 'accountant', 'registrar']
        )) {
            return true;
        }

        // Student can view their own challan
        if ($this->hasRoleOrType($user, ['Student'], ['student'])) {
            $studentId = $user->student?->id;
            if (!$studentId) {
                $student = Student::where('user_id', $user->id)->first();
                $studentId = $student?->id;
            }

            return $studentId !== null && (int) $challan->student_id === (int) $studentId;
        }

        // Parent can view their child's challan
        if ($this->hasRoleOrType($user, ['Parent', 'Guardian'], ['parent', 'guardian'])) {
            $parentProfileId = $user->parentsProfile?->id;
            if (!$parentProfileId) {
                $profile = ParentsProfile::where('user_id', $user->id)->first();
                $parentProfileId = $profile?->id;
            }

            if (!$parentProfileId) {
                return false;
            }

            $student = $challan->student ?: Student::find($challan->student_id);
            if (!$student) {
                return false;
            }

            return (int) $student->parent_id === (int) $parentProfileId;
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
        return $this->hasRoleOrType(
            $user,
            ['Super Admin', 'Principal', 'Admin', 'Accountant'],
            ['admin', 'principal', 'accountant']
        );
    }

    /**
     * Determine whether the user can delete the fee challan.
     */
    public function delete(User $user, FeeChallan $challan): bool
    {
        return $this->hasRoleOrType(
            $user,
            ['Super Admin', 'Principal', 'Admin'],
            ['admin', 'principal']
        );
    }
}
