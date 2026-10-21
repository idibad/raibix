<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\FeeChallan;
use App\Models\User;
use App\Models\Student;
use App\Models\SchoolClass;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentsWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $id = 'testschoolpayments' . uniqid();
        $this->tenant = Tenant::create([
            'id' => $id,
            'name' => 'Test Payments Campus',
            'stripe_secret_key' => 'sk_test_mock_secret',
        ]);

        $this->tenant->domains()->create([
            'domain' => $id . '.localhost',
        ]);

        tenancy()->initialize($this->tenant);
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        parent::tearDown();
    }

    /**
     * Test successful Stripe return updates challan status.
     */
    public function test_stripe_payment_return_updates_challan_status(): void
    {
        $class = SchoolClass::create(['name' => 'Grade 9', 'level' => 'matric', 'numeric_grade' => 9]);
        
        $user = User::create([
            'name' => 'Sohail Ahmad',
            'email' => 'sohail@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'student',
        ]);
        
        $student = Student::create([
            'user_id' => $user->id,
            'current_class_id' => $class->id,
            'roll_number' => 'G9-010',
            'admission_number' => 'ADM-901',
            'status' => 'Active',
        ]);

        $challan = FeeChallan::create([
            'student_id' => $student->id,
            'challan_number' => 'CHL-998877',
            'month_year' => '2026-07',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'total_amount' => 4500.00,
            'status' => 'unpaid',
        ]);

        $this->actingAs($user);

        // Hit the success callback route directly, bypassing tenancy routing middleware
        $response = $this->withoutMiddleware([
            \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
            \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class
        ])->get('/payments/success?session_id=cs_test_mock_session_id&challan_id=' . $challan->id);

        $response->assertRedirect();
        
        // Assert challan updated
        $updatedChallan = FeeChallan::find($challan->id);
        $this->assertEquals('paid', $updatedChallan->status);
        $this->assertEquals(4500.00, $updatedChallan->paid_amount);
    }
}
