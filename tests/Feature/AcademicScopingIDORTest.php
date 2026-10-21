<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\ParentsProfile;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Leave;
use App\Models\Subject;
use App\Services\TenantProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AcademicScopingIDORTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $parentUserA;
    protected $studentUserA;
    protected $studentA;

    protected $parentUserB;
    protected $studentUserB;
    protected $studentB;

    protected $teacherUserA;
    protected $teacherUserB;
    protected $adminUser;

    protected $sectionA;
    protected $sectionB;
    protected $assignmentA;

    protected function setUp(): void
    {
        parent::setUp();

        $id = 'testacademicidor' . uniqid();
        $provisioningService = new TenantProvisioningService();
        $this->tenant = $provisioningService->provisionSchool([
            'id' => $id,
            'name' => 'Test Academic IDOR Campus',
            'admin_email' => 'admin@academic.test',
            'admin_password' => 'Secret@123',
        ]);

        tenancy()->initialize($this->tenant);

        $this->adminUser = User::where('user_type', 'admin')->first();

        $class = SchoolClass::create(['name' => 'Grade 10', 'level' => 'matric', 'numeric_grade' => 10]);
        $this->sectionA = Section::create(['class_id' => $class->id, 'name' => 'A']);
        $this->sectionB = Section::create(['class_id' => $class->id, 'name' => 'B']);

        $subject = Subject::create([
            'class_id' => $class->id,
            'name' => 'Physics',
            'code' => 'PHY-10',
            'total_marks' => 100,
        ]);

        // Teachers
        $this->teacherUserA = User::create([
            'name' => 'Teacher A',
            'email' => 'teacherA@academic.test',
            'user_type' => 'teacher',
            'password' => Hash::make('password'),
        ]);

        $this->teacherUserB = User::create([
            'name' => 'Teacher B',
            'email' => 'teacherB@academic.test',
            'user_type' => 'teacher',
            'password' => Hash::make('password'),
        ]);

        // Assign Teacher A as class teacher to Section A
        $this->sectionA->update(['class_teacher_id' => $this->teacherUserA->id]);

        // Family A
        $this->parentUserA = User::create([
            'name' => 'Parent A',
            'email' => 'parentA@academic.test',
            'user_type' => 'parent',
            'password' => Hash::make('password'),
        ]);
        $parentProfileA = ParentsProfile::create([
            'user_id' => $this->parentUserA->id,
            'father_name' => 'Father A',
        ]);

        $this->studentUserA = User::create([
            'name' => 'Student A',
            'email' => 'studentA@academic.test',
            'user_type' => 'student',
            'password' => Hash::make('password'),
        ]);
        $this->studentA = Student::create([
            'user_id' => $this->studentUserA->id,
            'parent_id' => $parentProfileA->id,
            'current_class_id' => $class->id,
            'current_section_id' => $this->sectionA->id,
            'roll_number' => 'G10-001',
            'admission_number' => 'ADM-201',
            'status' => 'Active',
        ]);

        // Family B
        $this->parentUserB = User::create([
            'name' => 'Parent B',
            'email' => 'parentB@academic.test',
            'user_type' => 'parent',
            'password' => Hash::make('password'),
        ]);
        $parentProfileB = ParentsProfile::create([
            'user_id' => $this->parentUserB->id,
            'father_name' => 'Father B',
        ]);

        $this->studentUserB = User::create([
            'name' => 'Student B',
            'email' => 'studentB@academic.test',
            'user_type' => 'student',
            'password' => Hash::make('password'),
        ]);
        $this->studentB = Student::create([
            'user_id' => $this->studentUserB->id,
            'parent_id' => $parentProfileB->id,
            'current_class_id' => $class->id,
            'current_section_id' => $this->sectionB->id,
            'roll_number' => 'G10-002',
            'admission_number' => 'ADM-202',
            'status' => 'Active',
        ]);

        // Assignment for Section A by Teacher A
        $this->assignmentA = Assignment::create([
            'subject_id' => $subject->id,
            'section_id' => $this->sectionA->id,
            'teacher_id' => $this->teacherUserA->id,
            'title' => 'Newton Laws of Motion',
            'description' => 'Complete chapter questions',
            'due_date' => now()->addDays(7)->toDateString(),
            'total_marks' => 20,
        ]);
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        parent::tearDown();
    }

    /**
     * Parent A cannot submit leave request for Student B (returns HTTP 403 Forbidden).
     */
    public function test_parent_cannot_submit_leave_for_other_student(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $response = $this->actingAs($this->parentUserA)->post("http://{$domain}/parent/leaves", [
            'student_id' => $this->studentB->id,
            'reason' => 'Fever and cold',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
        ]);
        $response->assertStatus(403);
    }

    /**
     * Parent A can submit leave request for own child Student A.
     */
    public function test_parent_can_submit_leave_for_own_child(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $response = $this->actingAs($this->parentUserA)->post("http://{$domain}/parent/leaves", [
            'student_id' => $this->studentA->id,
            'reason' => 'Family event',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
        ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('leaves', [
            'student_id' => $this->studentA->id,
            'reason' => 'Family event',
        ]);
    }

    /**
     * Student B (in Section B) cannot submit to an assignment designated for Section A (returns HTTP 403 Forbidden).
     */
    public function test_student_cannot_submit_assignment_for_other_section(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $response = $this->actingAs($this->studentUserB)->post("http://{$domain}/student/assignments/{$this->assignmentA->id}/submit", [
            'submission_text' => 'My solution',
        ]);
        $response->assertStatus(403);
    }

    /**
     * Student A (in Section A) can submit to an assignment designated for Section A.
     */
    public function test_student_can_submit_assignment_for_own_section(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $response = $this->actingAs($this->studentUserA)->post("http://{$domain}/student/assignments/{$this->assignmentA->id}/submit", [
            'submission_text' => 'Here are my solutions to chapter 3.',
        ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('assignment_submissions', [
            'assignment_id' => $this->assignmentA->id,
            'student_id' => $this->studentA->id,
        ]);
    }

    /**
     * Teacher B cannot grade an assignment created by Teacher A (returns HTTP 403 Forbidden).
     */
    public function test_teacher_cannot_grade_assignment_of_another_teacher(): void
    {
        $submission = AssignmentSubmission::create([
            'assignment_id' => $this->assignmentA->id,
            'student_id' => $this->studentA->id,
            'submission_text' => 'Submitted work',
            'status' => 'submitted',
        ]);

        $domain = $this->tenant->domains->first()->domain;
        $response = $this->actingAs($this->teacherUserB)->post("http://{$domain}/teacher/submissions/{$submission->id}/grade", [
            'obtained_marks' => 18,
            'teacher_remarks' => 'Good job',
        ]);
        $response->assertStatus(403);
    }

    /**
     * Teacher A can grade an assignment created by Teacher A.
     */
    public function test_assigned_teacher_can_grade_assignment(): void
    {
        $submission = AssignmentSubmission::create([
            'assignment_id' => $this->assignmentA->id,
            'student_id' => $this->studentA->id,
            'submission_text' => 'Submitted work',
            'status' => 'submitted',
        ]);

        $domain = $this->tenant->domains->first()->domain;
        $response = $this->actingAs($this->teacherUserA)->post("http://{$domain}/teacher/submissions/{$submission->id}/grade", [
            'obtained_marks' => 19,
            'teacher_remarks' => 'Excellent analysis',
        ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('assignment_submissions', [
            'id' => $submission->id,
            'obtained_marks' => 19,
            'status' => 'graded',
        ]);
    }

    /**
     * Teacher B (not class teacher of Section A) cannot approve leave for Student A.
     */
    public function test_unassigned_teacher_cannot_approve_leave(): void
    {
        $leave = Leave::create([
            'student_id' => $this->studentA->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'reason' => 'Sick leave',
            'status' => 'pending',
        ]);

        $domain = $this->tenant->domains->first()->domain;
        $response = $this->actingAs($this->teacherUserB)->post("http://{$domain}/teacher/student-leaves/{$leave->id}/status", [
            'status' => 'approved',
            'admin_remarks' => 'Approved',
        ]);
        $response->assertStatus(403);
    }
}
