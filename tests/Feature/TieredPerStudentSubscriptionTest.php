<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TieredPerStudentSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations and seed subscription plans
        $this->artisan('migrate');
    }

    public function test_four_tiered_subscription_plans_exist_in_database(): void
    {
        $starter = SubscriptionPlan::where('code', 'starter')->first();
        $this->assertNotNull($starter);
        $this->assertEquals('Starter', $starter->name);
        $this->assertEquals('Small schools', $starter->tagline);
        $this->assertEquals(20.00, $starter->price_per_student);
        $this->assertEquals(2000.00, $starter->minimum_monthly_bill);

        $professional = SubscriptionPlan::where('code', 'professional')->first();
        $this->assertNotNull($professional);
        $this->assertEquals('Professional', $professional->name);
        $this->assertEquals('Recommended', $professional->tagline);
        $this->assertEquals(35.00, $professional->price_per_student);
        $this->assertEquals(3500.00, $professional->minimum_monthly_bill);

        $premium = SubscriptionPlan::where('code', 'premium')->first();
        $this->assertNotNull($premium);
        $this->assertEquals('Premium', $premium->name);
        $this->assertEquals('Advanced', $premium->tagline);
        $this->assertEquals(55.00, $premium->price_per_student);
        $this->assertEquals(5500.00, $premium->minimum_monthly_bill);

        $enterprise = SubscriptionPlan::where('code', 'enterprise')->first();
        $this->assertNotNull($enterprise);
        $this->assertEquals('Enterprise', $enterprise->name);
        $this->assertEquals('Custom', $enterprise->tagline);
        $this->assertEquals('custom', $enterprise->pricing_type);
    }

    public function test_starter_monthly_bill_calculates_with_minimum_floor(): void
    {
        $plan = SubscriptionPlan::where('code', 'starter')->first();

        // 50 students @ PKR 20 = PKR 1,000 -> Below PKR 2,000 floor -> Expect 2,000
        $this->assertEquals(2000.00, $plan->calculateMonthlyBill(50));

        // 100 students @ PKR 20 = PKR 2,000 -> Exactly PKR 2,000 floor -> Expect 2,000
        $this->assertEquals(2000.00, $plan->calculateMonthlyBill(100));

        // 250 students @ PKR 20 = PKR 5,000 -> Above floor -> Expect 5,000
        $this->assertEquals(5000.00, $plan->calculateMonthlyBill(250));
    }

    public function test_professional_monthly_bill_calculates_with_minimum_floor(): void
    {
        $plan = SubscriptionPlan::where('code', 'professional')->first();

        // 50 students @ PKR 35 = PKR 1,750 -> Below PKR 3,500 floor -> Expect 3,500
        $this->assertEquals(3500.00, $plan->calculateMonthlyBill(50));

        // 100 students @ PKR 35 = PKR 3,500 -> Exactly PKR 3,500 floor -> Expect 3,500
        $this->assertEquals(3500.00, $plan->calculateMonthlyBill(100));

        // 200 students @ PKR 35 = PKR 7,000 -> Above floor -> Expect 7,000
        $this->assertEquals(7000.00, $plan->calculateMonthlyBill(200));
    }

    public function test_premium_monthly_bill_calculates_with_minimum_floor(): void
    {
        $plan = SubscriptionPlan::where('code', 'premium')->first();

        // 50 students @ PKR 55 = PKR 2,750 -> Below PKR 5,500 floor -> Expect 5,500
        $this->assertEquals(5500.00, $plan->calculateMonthlyBill(50));

        // 100 students @ PKR 55 = PKR 5,500 -> Exactly floor -> Expect 5,500
        $this->assertEquals(5500.00, $plan->calculateMonthlyBill(100));

        // 200 students @ PKR 55 = PKR 11,000 -> Above floor -> Expect 11,000
        $this->assertEquals(11000.00, $plan->calculateMonthlyBill(200));
    }

    public function test_module_permissions_gate_starter_from_fees(): void
    {
        $starter = SubscriptionPlan::where('code', 'starter')->first();
        $this->assertTrue($starter->hasModule('sis'));
        $this->assertTrue($starter->hasModule('attendance'));
        $this->assertTrue($starter->hasModule('academics'));
        $this->assertTrue($starter->hasModule('exams'));
        $this->assertFalse($starter->hasModule('fees'));

        $professional = SubscriptionPlan::where('code', 'professional')->first();
        $this->assertTrue($professional->hasModule('fees'));
        $this->assertTrue($professional->hasModule('chat'));
        $this->assertTrue($professional->hasModule('reports'));

        $premium = SubscriptionPlan::where('code', 'premium')->first();
        $this->assertTrue($premium->hasModule('fees'));
        $this->assertTrue($premium->hasModule('advanced_analytics'));
        $this->assertTrue($premium->hasModule('workflows'));

        $enterprise = SubscriptionPlan::where('code', 'enterprise')->first();
        $this->assertTrue($enterprise->hasModule('fees'));
        $this->assertTrue($enterprise->hasModule('anything'));
    }

    public function test_landing_page_returns_successful_response(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_checkout_page_loads_for_all_tiers(): void
    {
        $response = $this->get('/checkout/starter');
        $response->assertStatus(200);

        $response = $this->get('/checkout/professional');
        $response->assertStatus(200);

        $response = $this->get('/checkout/premium');
        $response->assertStatus(200);
    }
}
