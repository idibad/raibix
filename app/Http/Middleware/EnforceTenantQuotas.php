<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Student;
use App\Models\User;

class EnforceTenantQuotas
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();
        if (!$tenant) {
            return $next($request);
        }

        // We only need to check quotas on creation requests
        if ($request->isMethod('POST')) {
            $plan = $tenant->plan;
            if (!$plan) {
                return $next($request);
            }

            // Check student quota for student creation routes
            if ($request->is('students') || $request->is('api/students') || $request->routeIs('students.store')) {
                $studentCount = Student::count();
                if ($studentCount >= ($plan->max_students ?? 500)) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'error' => 'Quota Exceeded',
                            'message' => 'Your subscription plan limits you to ' . $plan->max_students . ' students. Please upgrade your plan.'
                        ], 403);
                    }
                    abort(403, 'Your subscription plan limits you to ' . $plan->max_students . ' students. Please upgrade your plan.');
                }
            }

            // Check teacher quota
            if ($request->is('teachers') || $request->is('api/teachers') || $request->routeIs('teachers.store')) {
                $teacherCount = User::where('role', 'teacher')->count();
                if ($teacherCount >= ($plan->max_teachers ?? 50)) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'error' => 'Quota Exceeded',
                            'message' => 'Your subscription plan limits you to ' . $plan->max_teachers . ' teachers. Please upgrade your plan.'
                        ], 403);
                    }
                    abort(403, 'Your subscription plan limits you to ' . $plan->max_teachers . ' teachers. Please upgrade your plan.');
                }
            }
        }

        return $next($request);
    }
}
