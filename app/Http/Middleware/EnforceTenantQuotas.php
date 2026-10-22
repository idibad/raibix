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

        $plan = $tenant->plan;

        // Module Access Gating: Starter tier excludes Fee Management & Challans
        if ($plan && !$plan->hasModule('fees')) {
            $isFeeRoute = $request->is('admin/fees*', 'fees*', 'api/fees*', 'admin/defaulters*', 'admin/payments*')
                || $request->routeIs('tenant.admin.fees.*', 'tenant.challans.*');

            if ($isFeeRoute) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'Module Gated',
                        'message' => 'Fee Management is not included in the ' . $plan->name . ' plan. Please upgrade to Professional or higher to unlock Fee Management & Challan generation.'
                    ], 403);
                }

                if ($request->isMethod('GET')) {
                    return redirect()->route('tenant.admin.billing.index')->with('warning', 'Fee Management is not included in the ' . $plan->name . ' plan. Upgrade to Professional to unlock fee tracking and challan generation.');
                }

                abort(403, 'Fee Management is not included in the ' . $plan->name . ' plan. Please upgrade to Professional.');
            }
        }

        // We only need to check quotas on creation requests
        if ($request->isMethod('POST')) {
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
