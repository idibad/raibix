<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\SubscriptionPlan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $plans = [
            [
                'code' => 'starter',
                'name' => 'Starter',
                'tagline' => 'Small schools',
                'summary' => 'Best for schools digitizing their basic academic operations.',
                'pricing_type' => 'per_student',
                'price_per_student' => 20.00,
                'minimum_monthly_bill' => 2000.00,
                'price_monthly' => 2000.00, // baseline minimum monthly bill
                'price_yearly' => 24000.00,
                'max_students' => 5000,
                'max_teachers' => 100,
                'features' => [
                    'Student and staff records',
                    'Attendance management',
                    'Classes, sections and timetables',
                    'Exams, marks and report cards',
                    'Homework and announcements',
                    'Basic parent and student portals',
                ],
                'modules' => ['sis', 'attendance', 'academics', 'exams', 'homework', 'portals'],
                'is_active' => true,
            ],
            [
                'code' => 'professional',
                'name' => 'Professional',
                'tagline' => 'Recommended',
                'summary' => 'Best for established schools wanting an integrated management system.',
                'pricing_type' => 'per_student',
                'price_per_student' => 35.00,
                'minimum_monthly_bill' => 3500.00,
                'price_monthly' => 3500.00, // baseline minimum monthly bill
                'price_yearly' => 42000.00,
                'max_students' => 10000,
                'max_teachers' => 250,
                'features' => [
                    'Everything in Starter',
                    'Fee management and payment records',
                    'Parent–teacher communication',
                    'Assignments and academic progress',
                    'More detailed reports and analytics',
                    'Role-based access and audit history',
                    'Priority support',
                ],
                'modules' => ['sis', 'attendance', 'academics', 'exams', 'homework', 'portals', 'fees', 'chat', 'reports', 'audit'],
                'is_active' => true,
            ],
            [
                'code' => 'premium',
                'name' => 'Premium',
                'tagline' => 'Advanced',
                'summary' => 'Offer only when these advanced features and support services are ready.',
                'pricing_type' => 'per_student',
                'price_per_student' => 55.00,
                'minimum_monthly_bill' => 5500.00,
                'price_monthly' => 5500.00, // baseline minimum monthly bill
                'price_yearly' => 66000.00,
                'max_students' => 25000,
                'max_teachers' => 500,
                'features' => [
                    'Everything in Professional',
                    'Advanced academic and administrative analytics',
                    'Configurable workflows and reports',
                    'Advanced exam and assessment tools',
                    'Data import assistance',
                    'Priority onboarding and staff training',
                    'Enhanced support arrangements',
                ],
                'modules' => [
                    'sis', 'attendance', 'academics', 'exams', 'homework', 'portals',
                    'fees', 'chat', 'reports', 'audit', 'advanced_analytics',
                    'workflows', 'hr', 'payroll', 'library', 'transport', 'hostel', 'inventory'
                ],
                'is_active' => true,
            ],
            [
                'code' => 'enterprise',
                'name' => 'Enterprise',
                'tagline' => 'Custom',
                'summary' => 'For school chains and multi-campus institutions.',
                'pricing_type' => 'custom',
                'price_per_student' => 0.00,
                'minimum_monthly_bill' => 0.00,
                'price_monthly' => 0.00,
                'price_yearly' => 0.00,
                'max_students' => 999999,
                'max_teachers' => 99999,
                'features' => [
                    'Central management across campuses',
                    'Consolidated reporting',
                    'Custom integrations and workflows',
                    'Dedicated onboarding and service agreement',
                    'Negotiated volume pricing',
                ],
                'modules' => ['all'],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $planData) {
            SubscriptionPlan::updateOrCreate(
                ['code' => $planData['code']],
                $planData
            );
        }

        // Deactivate old legacy plans if any
        SubscriptionPlan::whereNotIn('code', ['starter', 'professional', 'premium', 'enterprise'])->update(['is_active' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep plans
    }
};
