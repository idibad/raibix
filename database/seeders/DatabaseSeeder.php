<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Check if we are inside a tenant database (Stancl Tenancy initialized)
        if (tenancy()->initialized) {
            $this->seedTenantDemoData();
        } else {
            $this->seedCentralDemoData();
        }
    }

    protected function seedCentralDemoData(): void
    {
        User::firstOrCreate(['email' => 'testadmin@lms.com'], [
            'name' => 'Super Admin',
            'password' => Hash::make('testadmin'),
        ]);

        if (\App\Models\SubscriptionPlan::count() === 0) {
            \App\Models\SubscriptionPlan::create(['name' => 'Starter Plan', 'code' => 'starter', 'max_students' => 300, 'price_monthly' => 12000, 'features' => ['Admissions', 'Attendance', 'Fee Management']]);
            \App\Models\SubscriptionPlan::create(['name' => 'Growth Plan', 'code' => 'growth', 'max_students' => 1000, 'price_monthly' => 25000, 'features' => ['Admissions', 'Attendance', 'AI Face Recognition', 'LMS', 'Examinations']]);
            \App\Models\SubscriptionPlan::create(['name' => 'Enterprise Plan', 'code' => 'enterprise', 'max_students' => 5000, 'price_monthly' => 60000, 'features' => ['All 16 Modules', 'AI Automation Engine', 'Multi-Campus', 'Dedicated Support']]);
        }
    }

    protected function seedTenantDemoData(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Core Permissions across modules
        $permissions = [
            'manage admissions', 'mark attendance', 'view child attendance', 'manage fees',
            'pay fee online', 'upload lms material', 'evaluate assignments', 'manage examinations',
            'enter marks', 'view results', 'manage transport', 'manage library',
            'manage inventory', 'send notices', 'access ai features',
        ];

        foreach ($permissions as $permissionName) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // Roles
        $principalRole = Role::firstOrCreate(['name' => 'Principal', 'guard_name' => 'web']);
        $teacherRole = Role::firstOrCreate(['name' => 'Secondary School Teachers', 'guard_name' => 'web']);
        $studentRole = Role::firstOrCreate(['name' => 'Matric (9-10)', 'guard_name' => 'web']);
        $parentRole = Role::firstOrCreate(['name' => 'Guardian', 'guard_name' => 'web']);

        $principalRole->syncPermissions($permissions);
        $teacherRole->syncPermissions(['mark attendance', 'upload lms material', 'evaluate assignments', 'enter marks', 'view results', 'access ai features']);
        $studentRole->syncPermissions(['view results']);
        $parentRole->syncPermissions(['view child attendance', 'pay fee online', 'view results']);

        $tenantId = tenancy()->tenant ? tenancy()->tenant->id : 'excellence';

        // 1. Admin (Principal) Users
        $admin = User::firstOrCreate(['email' => "principal@{$tenantId}.lmscloud.test"], [
            'name' => 'Dr. Arshad Khan (Principal)',
            'phone' => '03001234567',
            'user_type' => 'admin',
            'status' => 'active',
            'password' => Hash::make('Secret@123'),
        ]);
        $admin->assignRole($principalRole);
        
        $admin2 = User::firstOrCreate(['email' => 'admin@school.test'], [
            'name' => 'School Admin Officer',
            'phone' => '03001234000',
            'user_type' => 'admin',
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);
        $admin2->assignRole($principalRole);

        // Staff Users for non-teacher roles (G14 testing)
        $librarianUser = User::firstOrCreate(['email' => 'librarian@school.test'], [
            'name' => 'Mrs. Fatima Zahra (Librarian)',
            'phone' => '03129876543',
            'user_type' => 'librarian',
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);
        $librarianUser->assignRole($teacherRole);

        $accountantUser = User::firstOrCreate(['email' => 'accountant@school.test'], [
            'name' => 'Mr. Farooq Qureshi (Accountant)',
            'phone' => '03214567890',
            'user_type' => 'accountant',
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);
        $accountantUser->assignRole($teacherRole);

        $receptionistUser = User::firstOrCreate(['email' => 'receptionist@school.test'], [
            'name' => 'Miss Hina Shah (Receptionist/Registrar)',
            'phone' => '03338765432',
            'user_type' => 'receptionist',
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);
        $receptionistUser->assignRole($teacherRole);

        // 2. Teacher Users
        $teacher = User::firstOrCreate(['email' => "teacher@{$tenantId}.lmscloud.test"], [
            'name' => 'Ms. Ayesha Siddiqui (Sr. Teacher)',
            'phone' => '03001234568',
            'user_type' => 'teacher',
            'status' => 'active',
            'password' => Hash::make('Teacher@123'),
        ]);
        $teacher->assignRole($teacherRole);

        $teacher2 = User::firstOrCreate(['email' => 'teacher@school.test'], [
            'name' => 'Ms. Ayesha Siddiqui (Sr. Teacher)',
            'phone' => '03001234568',
            'user_type' => 'teacher',
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);
        $teacher2->assignRole($teacherRole);

        // 3. Parent Users
        $parent = User::firstOrCreate(['email' => "parent@{$tenantId}.lmscloud.test"], [
            'name' => 'Ahmed Ali (Father/Guardian)',
            'phone' => '03001234570',
            'user_type' => 'parent',
            'status' => 'active',
            'password' => Hash::make('Parent@123'),
        ]);
        $parent->assignRole($parentRole);

        $parent2 = User::firstOrCreate(['email' => 'parent@school.test'], [
            'name' => 'Ahmed Ali (Father/Guardian)',
            'phone' => '03001234570',
            'user_type' => 'parent',
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);
        $parent2->assignRole($parentRole);

        $parentProfile = \App\Models\ParentsProfile::firstOrCreate(['user_id' => $parent->id], [
            'father_name' => 'Ahmed Ali',
            'father_cnic' => '42101-1234567-1',
            'father_occupation' => 'Business Owner',
            'phone' => '03001234570',
            'address' => 'House 42-B, Block 6, PECHS, Karachi',
        ]);

        // 4. Academic Years & Terms
        $academicYear = \App\Models\AcademicYear::firstOrCreate(['name' => '2026-2027 Session'], [
            'start_date' => '2026-08-01',
            'end_date' => '2027-05-31',
            'is_active' => true,
        ]);

        $term1 = \App\Models\Term::firstOrCreate([
            'academic_year_id' => $academicYear->id,
            'name' => 'Term 1 Midterm',
        ], [
            'start_date' => '2026-08-01',
            'end_date' => '2026-12-15',
        ]);

        // 5. Classes & Sections
        $class = \App\Models\SchoolClass::firstOrCreate(['name' => 'Grade 10'], [
            'level' => 'matric',
            'numeric_grade' => 10,
        ]);

        $section = \App\Models\Section::firstOrCreate([
            'class_id' => $class->id,
            'name' => 'A',
        ], [
            'room_number' => 'Lab-A',
            'class_teacher_id' => $teacher->id,
        ]);

        // 6. Student Users & Profiles
        $studentUser = User::firstOrCreate(['email' => "student@{$tenantId}.lmscloud.test"], [
            'name' => 'Bilal Ahmed (Class 10-A)',
            'phone' => '03001234569',
            'user_type' => 'student',
            'status' => 'active',
            'password' => Hash::make('Student@123'),
        ]);
        $studentUser->assignRole($studentRole);

        $studentUser2 = User::firstOrCreate(['email' => 'student@school.test'], [
            'name' => 'Bilal Ahmed (Class 10-A)',
            'phone' => '03001234569',
            'user_type' => 'student',
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);
        $studentUser2->assignRole($studentRole);

        $student = \App\Models\Student::firstOrCreate(['user_id' => $studentUser->id], [
            'parent_id' => $parentProfile->id,
            'current_class_id' => $class->id,
            'current_section_id' => $section->id,
            'roll_number' => 'G10-A01',
            'admission_number' => 'ADM-2026-001',
            'admission_date' => '2026-08-01',
            'status' => 'Active',
        ]);

        // 7. Subjects & Timetable Entries
        $subjectEng = \App\Models\Subject::firstOrCreate([
            'class_id' => $class->id,
            'name' => 'English Literature',
        ], [
            'code' => 'ENG-101',
            'type' => 'theory',
            'total_marks' => 100,
        ]);

        $subjectMath = \App\Models\Subject::firstOrCreate([
            'class_id' => $class->id,
            'name' => 'Advanced Mathematics',
        ], [
            'code' => 'MATH-102',
            'type' => 'theory',
            'total_marks' => 100,
        ]);

        \App\Models\Timetable::firstOrCreate([
            'section_id' => $section->id,
            'subject_id' => $subjectEng->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:30:00',
        ], [
            'end_time' => '09:30:00',
            'teacher_id' => $teacher->id,
            'room_number' => 'Room 102',
        ]);

        \App\Models\Timetable::firstOrCreate([
            'section_id' => $section->id,
            'subject_id' => $subjectMath->id,
            'day_of_week' => 'Monday',
            'start_time' => '09:30:00',
        ], [
            'end_time' => '10:30:00',
            'teacher_id' => $teacher->id,
            'room_number' => 'Room 102',
        ]);

        // 8. Admissions Pipeline - comprehensive all 6 stages
        // Stage 1: Active Inquiries (new / contacted)
        \App\Models\AdmissionInquiry::firstOrCreate(['inquiry_code' => 'INQ-2026-001'], [
            'student_name' => 'Kamran Khan',
            'parent_name' => 'Khan Mohammad',
            'parent_phone' => '03001234567',
            'parent_email' => 'kamran@example.com',
            'desired_class_id' => $class->id,
            'status' => 'new',
        ]);
        \App\Models\AdmissionInquiry::firstOrCreate(['inquiry_code' => 'INQ-2026-002'], [
            'student_name' => 'Hira Baig',
            'parent_name' => 'Baig Sahab',
            'parent_phone' => '03211112222',
            'parent_email' => 'hirabaig@gmail.com',
            'desired_class_id' => $class->id,
            'previous_school' => 'Aga Khan School, Karachi',
            'status' => 'contacted',
        ]);
        \App\Models\AdmissionInquiry::firstOrCreate(['inquiry_code' => 'INQ-2026-003'], [
            'student_name' => 'Usman Ghani',
            'parent_name' => 'Abdul Ghani',
            'parent_phone' => '03335557777',
            'desired_class_id' => $class->id,
            'status' => 'new',
        ]);

        // Stage 2: Application submitted, merit pending
        $app2 = \App\Models\AdmissionApplication::firstOrCreate(['application_code' => 'APP-2026-0001'], [
            'student_name' => 'Farrukh Tashkentov',
            'class_id' => $class->id,
            'father_name' => 'Tashkent Ali',
            'father_phone' => '03009998877',
            'father_cnic' => '42101-9876543-1',
            'test_date' => '2026-07-28',
            'merit_status' => 'pending',
            'admission_status' => 'applied',
        ]);
        \App\Models\AdmissionApplication::firstOrCreate(['application_code' => 'APP-2026-0002'], [
            'student_name' => 'Zainab Hussain',
            'class_id' => $class->id,
            'father_name' => 'Hussain Ahmed',
            'father_phone' => '03111223344',
            'test_date' => '2026-07-29',
            'merit_status' => 'pending',
            'admission_status' => 'applied',
        ]);

        // Stage 3 & 4: Merit approved, fee challan generated, unpaid
        $app3 = \App\Models\AdmissionApplication::firstOrCreate(['application_code' => 'APP-2026-0003'], [
            'student_name' => 'Omar Abdullah',
            'class_id' => $class->id,
            'father_name' => 'Abdullah Shah',
            'father_phone' => '03441234567',
            'test_marks_obtained' => 72,
            'test_total_marks' => 100,
            'merit_status' => 'approved',
            'admission_status' => 'fee_pending',
        ]);

        // Create a temporary student record for the fee challan (needed by getChallanForApp lookup)
        $tempUser3 = \App\Models\User::firstOrCreate(['email' => "temp3@{$tenantId}.lmscloud.test"], [
            'name' => 'Temp Student 3',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'user_type' => 'student',
        ]);
        $tempStudent3 = \App\Models\Student::firstOrCreate(['admission_number' => 'ADM-TEMP-' . $app3->id], [
            'user_id' => $tempUser3->id,
            'current_class_id' => $class->id,
            'roll_number' => 'TEMP-' . $app3->id,
            'admission_date' => now(),
            'status' => 'inactive',
        ]);

        $admFeeStudent3 = \App\Models\FeeChallan::firstOrCreate(['challan_number' => 'ADM-CHL-0003'], [
            'student_id' => $tempStudent3->id,
            'fee_type' => 'admission',
            'month_year' => date('Y-m'),
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(15)->format('Y-m-d'),
            'admission_fee' => 5000,
            'registration_fee' => 1500,
            'security_fee' => 3000,
            'tuition_fee' => 8500,
            'total_amount' => 18000,
            'status' => 'unpaid',
        ]);

        // Stage 5: Fee paid, ready for final enrollment signoff
        $app5 = \App\Models\AdmissionApplication::firstOrCreate(['application_code' => 'APP-2026-0004'], [
            'student_name' => 'Nadia Qureshi',
            'class_id' => $class->id,
            'father_name' => 'Qureshi Sahab',
            'father_phone' => '03556677889',
            'test_marks_obtained' => 88,
            'test_total_marks' => 100,
            'merit_status' => 'approved',
            'admission_status' => 'fee_pending',
        ]);

        $tempUser5 = \App\Models\User::firstOrCreate(['email' => "temp5@{$tenantId}.lmscloud.test"], [
            'name' => 'Temp Student 5',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'user_type' => 'student',
        ]);
        $tempStudent5 = \App\Models\Student::firstOrCreate(['admission_number' => 'ADM-TEMP-' . $app5->id], [
            'user_id' => $tempUser5->id,
            'current_class_id' => $class->id,
            'roll_number' => 'TEMP-' . $app5->id,
            'admission_date' => now(),
            'status' => 'inactive',
        ]);

        $paidAdmChallan = \App\Models\FeeChallan::firstOrCreate(['challan_number' => 'ADM-CHL-0004'], [
            'student_id' => $tempStudent5->id,
            'fee_type' => 'admission',
            'month_year' => date('Y-m'),
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(15)->format('Y-m-d'),
            'admission_fee' => 5000,
            'registration_fee' => 1500,
            'security_fee' => 3000,
            'tuition_fee' => 8500,
            'total_amount' => 18000,
            'status' => 'paid',
        ]);

        if ($paidAdmChallan->transactions()->count() === 0) {
            $paidAdmChallan->transactions()->create([
                'gateway' => 'cash',
                'amount' => 18000,
                'payment_date' => now()->format('Y-m-d'),
                'transaction_reference' => 'CASH-' . strtoupper(\Illuminate\Support\Str::random(8)),
                'status' => 'success',
            ]);
        }

        // Stage 6: Already enrolled student (re-use $student above)

        // 9. Library Books & Issuances
        $book = \App\Models\LibraryBook::firstOrCreate(['isbn' => '9780262033848'], [
            'title' => 'Introduction to Algorithms',
            'author' => 'Thomas H. Cormen',
            'category' => 'Computer Science',
            'total_copies' => 10,
            'available_copies' => 9,
            'price' => 2500.00,
        ]);

        $book2 = \App\Models\LibraryBook::firstOrCreate(['isbn' => '9780735619678'], [
            'title' => 'Code: The Hidden Language of Computer Hardware',
            'author' => 'Charles Petzold',
            'category' => 'Computer Science',
            'total_copies' => 5,
            'available_copies' => 4,
            'price' => 1800.00,
        ]);

        $book3 = \App\Models\LibraryBook::firstOrCreate(['isbn' => '9780143105985'], [
            'title' => 'Thinking, Fast and Slow',
            'author' => 'Daniel Kahneman',
            'category' => 'Psychology',
            'total_copies' => 8,
            'available_copies' => 7,
            'price' => 2200.00,
        ]);

        \App\Models\BookIssue::firstOrCreate([
            'book_id' => $book->id,
            'student_id' => $student->id,
        ], [
            'issue_date' => '2026-07-15',
            'due_date' => '2026-07-30',
            'status' => 'issued',
        ]);

        // 10. Transport Routes & Vehicles
        $vehicle = \App\Models\TransportVehicle::firstOrCreate(['vehicle_number' => 'BSA-452'], [
            'model_name' => 'Toyota Coaster 2024',
            'capacity' => 30,
            'driver_name' => 'Nawaz Shareef',
            'driver_phone' => '03456789012',
        ]);

        $vehicle2 = \App\Models\TransportVehicle::firstOrCreate(['vehicle_number' => 'KHI-789'], [
            'model_name' => 'Hino Dutro 2023',
            'capacity' => 45,
            'driver_name' => 'Akbar Ali',
            'driver_phone' => '03123456789',
        ]);

        $route = \App\Models\TransportRoute::firstOrCreate(['route_name' => 'Gulshan-e-Iqbal to PECHS'], [
            'vehicle_id' => $vehicle->id,
            'pickup_time' => '07:00:00',
            'drop_time' => '14:30:00',
            'monthly_fee' => 3500.00,
        ]);

        $route2 = \App\Models\TransportRoute::firstOrCreate(['route_name' => 'DHA Phase 6 to School'], [
            'vehicle_id' => $vehicle2->id,
            'pickup_time' => '06:45:00',
            'drop_time' => '14:45:00',
            'monthly_fee' => 4500.00,
        ]);

        \App\Models\TransportStop::firstOrCreate([
            'route_id' => $route->id,
            'stop_name' => 'Nipa Chowrangi Stop',
        ], [
            'pickup_time' => '07:15:00',
        ]);

        \App\Models\TransportStop::firstOrCreate([
            'route_id' => $route->id,
            'stop_name' => 'Johar Chowrangi Stop',
        ], [
            'pickup_time' => '07:25:00',
        ]);

        \App\Models\TransportAllocation::firstOrCreate([
            'user_id' => $studentUser->id,
            'user_type' => 'student',
        ], [
            'route_id' => $route->id,
            'vehicle_id' => $vehicle->id,
            'pickup_point' => 'Nipa Chowrangi Stop',
            'status' => 'active',
        ]);

        // 11. Hostels & Allocations
        $hostel = \App\Models\Hostel::firstOrCreate(['name' => 'Quaid Boys Boarding House'], [
            'type' => 'boys',
            'address' => 'Sector B-3, University Rd, Karachi',
            'warden_name' => 'Professor Salim Jamil',
            'warden_phone' => '03131234567',
        ]);

        $hostel2 = \App\Models\Hostel::firstOrCreate(['name' => 'Fatima Girls Residence'], [
            'type' => 'girls',
            'address' => 'Block 14, North Nazimabad, Karachi',
            'warden_name' => 'Mrs. Rabia Ijaz',
            'warden_phone' => '03219876543',
        ]);

        $room = \App\Models\HostelRoom::firstOrCreate([
            'hostel_id' => $hostel->id,
            'room_number' => '102-B',
        ], [
            'capacity' => 3,
            'rent_amount' => 8500.00,
        ]);

        $room2 = \App\Models\HostelRoom::firstOrCreate([
            'hostel_id' => $hostel->id,
            'room_number' => '201-A',
        ], [
            'capacity' => 2,
            'rent_amount' => 9000.00,
        ]);

        \App\Models\HostelAllocation::firstOrCreate(['student_id' => $student->id], [
            'room_id' => $room->id,
            'allocation_date' => '2026-08-01',
            'status' => 'active',
        ]);

        // 12. HR & Payroll structures
        \App\Models\SalaryStructure::firstOrCreate(['user_id' => $teacher->id], [
            'base_salary' => 75000.00,
            'net_salary' => 92000.00,
        ]);

        \App\Models\SalaryStructure::firstOrCreate(['user_id' => $teacher2->id], [
            'base_salary' => 55000.00,
            'net_salary' => 66000.00,
        ]);

        // Staff Attendance Logs (raw DB - no Eloquent model)
        \Illuminate\Support\Facades\DB::table('staff_attendances')->updateOrInsert(
            ['user_id' => $teacher->id, 'date' => now()->format('Y-m-d')],
            [
                'status' => 'present',
                'check_in_time' => '07:45:00',
                'check_out_time' => '14:30:00',
                'remarks' => 'Regular attendance',
                'marked_by_id' => $teacher->id,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        // Staff Leave Request (raw DB - no Eloquent model)
        if (!\Illuminate\Support\Facades\DB::table('staff_leaves')->where('user_id', $teacher2->id)->exists()) {
            \Illuminate\Support\Facades\DB::table('staff_leaves')->insert([
                'user_id' => $teacher2->id,
                'leave_type' => 'casual',
                'start_date' => now()->addDays(5)->format('Y-m-d'),
                'end_date' => now()->addDays(6)->format('Y-m-d'),
                'days_count' => 2,
                'reason' => 'Family event - sister wedding',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 13. Inventory Categories & Items
        $invCategory = \App\Models\InventoryCategory::firstOrCreate(['name' => 'Sports Equipment'], [
            'description' => 'Football, Cricket bats, Badminton kits, and table tennis gears.',
        ]);

        $invCategory2 = \App\Models\InventoryCategory::firstOrCreate(['name' => 'Lab Equipment'], [
            'description' => 'Physics, Chemistry and Biology lab instruments and consumables.',
        ]);

        $invItem = \App\Models\InventoryItem::firstOrCreate(['item_name' => 'Premium Leather Cricket Balls'], [
            'category_id' => $invCategory->id,
            'category' => $invCategory->name,
            'quantity' => 15,
            'room_location' => 'Sports Room Block-C',
            'condition' => 'good',
            'unit_cost' => 1200.00,
        ]);

        $invItem2 = \App\Models\InventoryItem::firstOrCreate(['item_name' => 'Physics Lab Multimeters'], [
            'category_id' => $invCategory2->id,
            'category' => $invCategory2->name,
            'quantity' => 20,
            'room_location' => 'Physics Lab Block-A',
            'condition' => 'good',
            'unit_cost' => 3500.00,
        ]);

        \App\Models\InventoryIssue::firstOrCreate([
            'item_id' => $invItem->id,
            'user_id' => $teacher->id,
        ], [
            'quantity' => 2,
            'issue_date' => '2026-07-20',
            'status' => 'issued',
        ]);

        // 14. Academic Calendar Events
        \App\Models\AcademicEvent::firstOrCreate(['title' => 'Annual Science Exhibition 2026'], [
            'description' => 'Students will display physics, chemistry and computer automation models.',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-12',
            'type' => 'event',
            'is_school_off' => false,
        ]);

        \App\Models\AcademicEvent::firstOrCreate(['title' => 'Eid ul Adha Holiday'], [
            'description' => 'School closed for Eid ul Adha celebrations.',
            'start_date' => '2026-06-16',
            'end_date' => '2026-06-18',
            'type' => 'holiday',
            'is_school_off' => true,
        ]);

        \App\Models\AcademicEvent::firstOrCreate(['title' => 'PTM — Mid-Year Parent Teacher Meeting'], [
            'description' => 'Parents are invited to meet class teachers to discuss academic progress.',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-20',
            'type' => 'ptm',
            'is_school_off' => false,
        ]);

        // 15. School Notices
        \App\Models\Notice::firstOrCreate(['title' => 'Important: Mid-Term Examination Dates Announced'], [
            'content' => 'Dear parents, please note that the Mid-term exams will start from September 15. Ensure students revise accordingly.',
            'target_audience' => 'parents',
            'publish_date' => '2026-07-23',
        ]);

        \App\Models\Notice::firstOrCreate(['title' => 'Uniform Compliance Reminder — Summer Term'], [
            'content' => 'All students are reminded to wear the complete school uniform including ID card lanyard. Violations will result in disciplinary notes.',
            'target_audience' => 'all',
            'publish_date' => '2026-07-22',
        ]);

        \App\Models\Notice::firstOrCreate(['title' => 'Staff Workshop — Modern Pedagogy & AI Tools'], [
            'content' => 'All teaching staff are required to attend the mandatory Professional Development Workshop on August 5th in the Main Auditorium.',
            'target_audience' => 'teachers',
            'publish_date' => '2026-07-20',
        ]);

        // 16. Fee Challans & Transactions
        $challan1 = \App\Models\FeeChallan::firstOrCreate(['challan_number' => 'CHL-202607-001'], [
            'student_id' => $student->id,
            'month_year' => '2026-07',
            'issue_date' => '2026-07-01',
            'due_date' => '2026-07-15',
            'tuition_fee' => 5000.00,
            'total_amount' => 5000.00,
            'status' => 'unpaid',
        ]);

        $challan2 = \App\Models\FeeChallan::firstOrCreate(['challan_number' => 'CHL-202606-001'], [
            'student_id' => $student->id,
            'month_year' => '2026-06',
            'issue_date' => '2026-06-01',
            'due_date' => '2026-06-15',
            'tuition_fee' => 5000.00,
            'total_amount' => 5000.00,
            'status' => 'paid',
        ]);

        if ($challan2->transactions()->count() === 0) {
            $challan2->transactions()->create([
                'gateway' => 'jazzcash',
                'amount' => 5000.00,
                'payment_date' => '2026-06-10',
                'transaction_reference' => 'TID-JC-998877',
                'status' => 'success',
            ]);
        }

        $challan3 = \App\Models\FeeChallan::firstOrCreate(['challan_number' => 'CHL-202605-001'], [
            'student_id' => $student->id,
            'month_year' => '2026-05',
            'issue_date' => '2026-05-01',
            'due_date' => '2026-05-15',
            'tuition_fee' => 5000.00,
            'total_amount' => 5000.00,
            'status' => 'paid',
        ]);

        if ($challan3->transactions()->count() === 0) {
            $challan3->transactions()->create([
                'gateway' => 'stripe',
                'amount' => 5000.00,
                'payment_date' => '2026-05-08',
                'transaction_reference' => 'pi_3NqXxYClCIKljWvJ1234abcd',
                'status' => 'success',
            ]);
        }

        // 17. Sports Activities
        \App\Models\SportsActivity::firstOrCreate(['title' => 'Boys Varsity Cricket Team'], [
            'sport_type' => 'Sports',
            'schedule_date' => 'Monday & Thursday, 4:00 PM',
            'venue' => 'School Main Cricket Ground',
            'max_participants' => 22,
            'coach_teacher_id' => $teacher->id,
            'status' => 'Ongoing',
        ]);

        \App\Models\SportsActivity::firstOrCreate(['title' => 'Inter-School Debate Club'], [
            'sport_type' => 'Debate',
            'schedule_date' => 'Wednesday, 3:30 PM',
            'venue' => 'Main Auditorium',
            'max_participants' => 30,
            'coach_teacher_id' => $teacher2->id,
            'status' => 'Ongoing',
        ]);

        // 18. Exams
        $exam = \App\Models\Exam::firstOrCreate([
            'name' => 'Mid-Term Examination 2026',
            'academic_year_id' => $academicYear->id,
        ], [
            'term_id' => $term1->id,
            'start_date' => '2026-09-15',
            'end_date' => '2026-09-22',
            'is_published' => true,
        ]);

        // 19. Default Certificate Templates
        \App\Models\CertificateTemplate::firstOrCreate(['type' => 'transfer'], [
            'name' => 'School Leaving Certificate',
            'content' => 'This is to certify that {{student_name}} son/daughter of {{father_name}} bearing Roll Number {{roll_number}} has been a student of {{school_name}} in Class {{class_name}} (Section: {{section_name}}). Their conduct during their stay at this institution was exemplary. They are leaving the school on {{issue_date}} because of completion of studies / parental relocation.',
            'variables' => ['{{student_name}}', '{{father_name}}', '{{roll_number}}', '{{class_name}}', '{{section_name}}', '{{issue_date}}', '{{reference_number}}', '{{school_name}}']
        ]);

        \App\Models\CertificateTemplate::firstOrCreate(['type' => 'character'], [
            'name' => 'Character Certificate',
            'content' => 'This is to certify that {{student_name}} son/daughter of {{father_name}} bearing Roll Number {{roll_number}} has successfully completed their academic term in Class {{class_name}} (Section: {{section_name}}). During their enrollment, they demonstrated excellent moral character, high discipline, responsibility, and an active participation in extracurricular activities.',
            'variables' => ['{{student_name}}', '{{father_name}}', '{{roll_number}}', '{{class_name}}', '{{section_name}}', '{{issue_date}}', '{{reference_number}}', '{{school_name}}']
        ]);

        \App\Models\CertificateTemplate::firstOrCreate(['type' => 'appreciation'], [
            'name' => 'Certificate of Appreciation',
            'content' => 'This is to certify that {{student_name}} son/daughter of {{father_name}} has demonstrated exceptional diligence, dedication, and academic brilliance in Class {{class_name}}. This certificate is awarded on {{issue_date}} in recognition of their outstanding performance and contribution to school life.',
            'variables' => ['{{student_name}}', '{{father_name}}', '{{roll_number}}', '{{class_name}}', '{{section_name}}', '{{issue_date}}', '{{reference_number}}', '{{school_name}}']
        ]);
    }
}
