<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\Student;
use App\Models\User;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\FeeChallan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

class FeeGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $id = 'testschoolfee' . uniqid();
        $this->tenant = Tenant::create([
            'id' => $id,
            'name' => 'Test Fee Campus',
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
     * Test creating a fee challan and checking late fee escalation.
     */
    public function test_fee_challan_escalation_and_command(): void
    {
        $class = SchoolClass::create(['name' => 'Grade 10', 'level' => 'matric', 'numeric_grade' => 10]);
        $section = Section::create(['class_id' => $class->id, 'name' => 'A']);
        
        $user = User::create([
            'name' => 'Ali Raza',
            'email' => 'ali@lms.edu',
            'password' => bcrypt('password'),
            'user_type' => 'student',
        ]);

        $student = Student::create([
            'user_id' => $user->id,
            'current_class_id' => $class->id,
            'current_section_id' => $section->id,
            'roll_number' => 'G10-001',
            'admission_number' => 'ADM-900',
            'status' => 'Active',
        ]);

        $challan = FeeChallan::create([
            'student_id' => $student->id,
            'challan_number' => 'CHL-10101',
            'month_year' => '2026-07',
            'issue_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->subDays(2)->toDateString(), // Overdue
            'total_amount' => 5000.00,
            'status' => 'unpaid',
        ]);

        $this->assertEquals('unpaid', $challan->status);

        // Run fee reminders and escalation command
        tenancy()->end(); // Revert to let command run across tenants
        Artisan::call('fees:send-reminders');

        tenancy()->initialize($this->tenant);
        
        $updatedChallan = FeeChallan::find($challan->id);
        $this->assertEquals('overdue', $updatedChallan->status);
        $this->assertEquals(5500.00, $updatedChallan->total_amount); // PKR 500 late fee penalty applied

        // Run fee reminders and escalation command a second time
        tenancy()->end();
        Artisan::call('fees:send-reminders');

        tenancy()->initialize($this->tenant);
        
        $updatedChallan2 = FeeChallan::find($challan->id);
        $this->assertEquals('overdue', $updatedChallan2->status);
        $this->assertEquals(5500.00, $updatedChallan2->total_amount); // Should still be 5500, not 6000
        $this->assertEquals(500.00, $updatedChallan2->fine_amount); // Fine amount is persisted as 500
    }
}
