<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\SalaryStructure;
use App\Models\LibraryBook;
use App\Models\TransportRoute;
use App\Models\Hostel;
use App\Models\HostelRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ERPModulesTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $id = 'testschoolerp' . uniqid();
        $this->tenant = Tenant::create([
            'id' => $id,
            'name' => 'Test ERP Campus',
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
     * Test HR & Payroll employee onboarding.
     */
    public function test_employee_onboarding_hr(): void
    {
        $user = User::create([
            'name' => 'Asad Siddiqui',
            'email' => 'asad@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'staff',
        ]);

        $salary = SalaryStructure::create([
            'user_id' => $user->id,
            'base_salary' => 85000.00,
            'net_salary' => 85000.00,
        ]);

        $this->assertDatabaseHas('salary_structures', [
            'user_id' => $user->id,
            'net_salary' => 85000.00,
        ]);
    }

    /**
     * Test library books catalogue entries.
     */
    public function test_library_book_addition(): void
    {
        $book = LibraryBook::create([
            'title' => 'Introduction to Algorithms',
            'author' => 'Thomas H. Cormen',
            'isbn' => '9780262033848',
            'category' => 'Reference',
            'total_copies' => 10,
            'available_copies' => 10,
        ]);

        $this->assertDatabaseHas('library_books', [
            'isbn' => '9780262033848',
            'available_copies' => 10,
        ]);
    }

    /**
     * Test transport management routes setup.
     */
    public function test_transport_route_setup(): void
    {
        $route = TransportRoute::create([
            'route_name' => 'Gulshan-e-Iqbal to Campus',
            'monthly_fee' => 3500.00,
        ]);

        $this->assertDatabaseHas('transport_routes', [
            'route_name' => 'Gulshan-e-Iqbal to Campus',
            'monthly_fee' => 3500.00,
        ]);
    }

    /**
     * Test hostel room capacity registration.
     */
    public function test_hostel_room_allocation(): void
    {
        $hostel = Hostel::create([
            'name' => 'Jinnah Boys Hostel',
            'type' => 'boys',
            'address' => 'Adjacent Campus Blocks',
        ]);

        $room = HostelRoom::create([
            'hostel_id' => $hostel->id,
            'room_number' => 'B-102',
            'capacity' => 3,
            'rent_amount' => 8000.00,
        ]);

        $this->assertDatabaseHas('hostel_rooms', [
            'room_number' => 'B-102',
            'rent_amount' => 8000.00,
        ]);
    }

    /**
     * Test bulk certificate issuance.
     */
    public function test_bulk_certificate_issuance(): void
    {
        $admin = User::create([
            'name' => 'School Admin',
            'email' => 'admin@school.test',
            'password' => bcrypt('password'),
            'user_type' => 'admin',
        ]);

        $template = \App\Models\CertificateTemplate::create([
            'name' => 'Character Certificate',
            'type' => 'character',
            'content' => 'This is to certify that {{student_name}} son/daughter of {{father_name}} bearing Roll Number {{roll_number}} has successfully completed studies.',
            'variables' => ['{{student_name}}', '{{father_name}}', '{{roll_number}}'],
        ]);

        $class = \App\Models\SchoolClass::create([
            'name' => 'Grade 10',
            'level' => 'matric',
            'numeric_grade' => 10,
        ]);

        $section = \App\Models\Section::create([
            'class_id' => $class->id,
            'name' => 'A',
        ]);

        // Create two students
        $studentUser1 = User::create([
            'name' => 'Ali Khan',
            'email' => 'ali@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'student',
        ]);
        $student1 = \App\Models\Student::create([
            'user_id' => $studentUser1->id,
            'current_class_id' => $class->id,
            'current_section_id' => $section->id,
            'roll_number' => 'G10-001',
            'admission_number' => 'ADM-001',
            'admission_date' => '2026-08-01',
            'status' => 'Active',
        ]);

        $studentUser2 = User::create([
            'name' => 'Zainab Bibi',
            'email' => 'zainab@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'student',
        ]);
        $student2 = \App\Models\Student::create([
            'user_id' => $studentUser2->id,
            'current_class_id' => $class->id,
            'current_section_id' => $section->id,
            'roll_number' => 'G10-002',
            'admission_number' => 'ADM-002',
            'admission_date' => '2026-08-01',
            'status' => 'Active',
        ]);

        // Issue certificates in bulk
        $domain = $this->tenant->domains->first()->domain;
        $response = $this->actingAs($admin)->post("http://{$domain}/admin/certificates/bulk-issue", [
            'student_ids' => [$student1->id, $student2->id],
            'template_id' => $template->id,
            'issue_date' => '2026-07-30',
        ]);

        // Assert redirect to bulk print route
        $response->assertRedirect();
        
        $this->assertDatabaseHas('issued_certificates', [
            'student_id' => $student1->id,
            'template_id' => $template->id,
        ]);

        $this->assertDatabaseHas('issued_certificates', [
            'student_id' => $student2->id,
            'template_id' => $template->id,
        ]);
    }
}
