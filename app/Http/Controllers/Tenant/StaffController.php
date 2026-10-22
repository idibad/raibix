<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Subject;
use App\Models\SchoolClass;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StaffController extends Controller
{
    /**
     * Store a newly created user (by role with dynamic fields).
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'cnic' => 'nullable|string|max:50',
            'user_type' => 'required|string|in:teacher,admin,registrar,accountant,front_desk,librarian,staff,student,parent',
            'qualification' => 'nullable|string|max:255',
            'password' => 'required|string|min:8',
        ]);

        $password = $request->password;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'cnic' => $request->cnic,
            'user_type' => $request->user_type,
            'qualification' => $request->qualification,
            'status' => 'active',
            'password' => Hash::make($password),
        ]);

        // If teacher, handle homeroom or subject assignment if passed
        if ($request->user_type === 'teacher' && $request->filled('homeroom_section_id')) {
            Section::where('id', $request->homeroom_section_id)->update(['class_teacher_id' => $user->id]);
        }

        try {
            $loginUrl = route('tenant.login');
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\WelcomeStaffMail($user, $password, $loginUrl));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send staff welcome email: " . $e->getMessage());
        }

        return redirect()->back()->with('success', "Account for {$user->name} ({$user->user_type}) created successfully! Temporary credentials sent via invitation email.");
    }

    /**
     * Update an existing user.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:50',
            'cnic' => 'nullable|string|max:50',
            'user_type' => 'required|string',
            'qualification' => 'nullable|string|max:255',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'cnic' => $request->cnic,
            'user_type' => $request->user_type,
            'qualification' => $request->qualification,
        ]);

        if ($request->user_type === 'teacher' && $request->filled('homeroom_section_id')) {
            Section::where('class_teacher_id', $user->id)->update(['class_teacher_id' => null]);
            Section::where('id', $request->homeroom_section_id)->update(['class_teacher_id' => $user->id]);
        }

        return redirect()->back()->with('success', "User details updated successfully.");
    }

    /**
     * Toggle status (Deactivate / Reactivate).
     */
    public function toggleStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        // Don't allow toggling self
        if (auth()->id() == $user->id) {
            return redirect()->back()->with('error', 'You cannot deactivate your own administrative account.');
        }

        $newStatus = ($user->status === 'active') ? 'deactivated' : 'active';
        $user->update(['status' => $newStatus]);

        $actionWord = ($newStatus === 'active') ? 'reactivated' : 'deactivated';
        return redirect()->back()->with('success', "Account for {$user->name} has been {$actionWord}. Historical records remain preserved.");
    }

    /**
     * Admin-triggered password reset link.
     */
    public function sendResetLink(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $rawToken = Str::random(64);
        $email = strtolower(trim($user->email));

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($rawToken),
                'created_at' => now(),
            ]
        );

        $resetUrl = route('tenant.password.reset', ['token' => $rawToken, 'email' => $email]);

        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\ResetPasswordMail($user, $resetUrl));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send password reset email: " . $e->getMessage());
        }
        
        return redirect()->back()->with('success', "Secure password reset link generated and dispatched to {$user->email} via Email notification.");
    }

    /**
     * Hard delete user (gated behind confirmation & check for no historical data).
     */
    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'reason' => 'required|string|min:3',
        ]);

        if (auth()->id() == $user->id) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        if (!$user->hasNoHistoricalData()) {
            return redirect()->back()->with('error', "Cannot hard-delete {$user->name} because historical records (attendance, fee challans, or grades) are attached. Please use 'Deactivate' instead to preserve integrity.");
        }

        $user->forceDelete(); // Hard delete only if clean

        return redirect()->back()->with('success', "User account permanently deleted. Reason recorded: {$request->reason}");
    }

    /**
     * Bulk import users via CSV.
     */
    public function bulkImport(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getPathname(), 'r');
        $header = fgetcsv($handle);
        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 3) {
                $name = trim($row[0]);
                $email = trim($row[1]);
                $role = strtolower(trim($row[2]));
                if (!in_array($role, ['teacher', 'admin', 'registrar', 'accountant', 'front_desk', 'librarian'])) {
                    $role = 'teacher';
                }

                if (!User::where('email', $email)->exists()) {
                    $password = Str::random(10);
                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'phone' => isset($row[3]) ? trim($row[3]) : null,
                        'user_type' => $role,
                        'status' => 'active',
                        'password' => Hash::make($password),
                    ]);
                    try {
                        $loginUrl = route('tenant.login');
                        \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\WelcomeStaffMail($user, $password, $loginUrl));
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error("Failed to send staff welcome email: " . $e->getMessage());
                    }
                    $count++;
                }
            }
        }
        fclose($handle);

        return redirect()->back()->with('success', "Bulk import completed successfully! {$count} new staff accounts provisioned.");
    }
}
