<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ---------------------------------------------------------
        // 1. HR & Payroll Expansion
        // ---------------------------------------------------------
        if (!Schema::hasTable('salary_structures')) {
            Schema::create('salary_structures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('base_salary', 10, 2);
                $table->json('allowances')->nullable(); // array of {name, amount}
                $table->json('deductions')->nullable(); // array of {name, amount}
                $table->decimal('net_salary', 10, 2);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('payrolls')) {
            Schema::create('payrolls', function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('month');
                $table->unsignedSmallInteger('year');
                $table->enum('status', ['draft', 'generated', 'paid'])->default('draft');
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payslips')) {
            Schema::create('payslips', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('basic_pay', 10, 2);
                $table->decimal('allowances', 10, 2)->default(0);
                $table->decimal('deductions', 10, 2)->default(0);
                $table->decimal('net_pay', 10, 2);
                $table->enum('status', ['unpaid', 'paid'])->default('unpaid');
                $table->timestamps();
            });
        }

        // ---------------------------------------------------------
        // 2. Finance Expansion (Expenses, Vendors, Refunds)
        // ---------------------------------------------------------
        if (!Schema::hasTable('vendors')) {
            Schema::create('vendors', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('contact_person')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->text('address')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
                $table->id();
                $table->string('category');
                $table->decimal('amount', 10, 2);
                $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
                $table->string('reference')->nullable();
                $table->date('date');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('refund_requests')) {
            Schema::create('refund_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('fee_challan_id')->nullable()->constrained('fee_challans')->nullOnDelete();
                $table->decimal('amount', 10, 2);
                $table->string('reason');
                $table->enum('status', ['pending', 'approved', 'rejected', 'refunded'])->default('pending');
                $table->text('admin_notes')->nullable();
                $table->timestamps();
            });
        }

        // ---------------------------------------------------------
        // 4. Transport Management Expansion
        // ---------------------------------------------------------
        if (!Schema::hasTable('transport_allocations')) {
            Schema::create('transport_allocations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('user_type'); // 'student' or 'staff'
                $table->foreignId('route_id')->constrained('transport_routes')->cascadeOnDelete();
                $table->foreignId('vehicle_id')->nullable()->constrained('transport_vehicles')->nullOnDelete();
                $table->string('pickup_point');
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
            });
        }

        // ---------------------------------------------------------
        // 5. Hostel Management
        // ---------------------------------------------------------
        if (!Schema::hasTable('hostels')) {
            Schema::create('hostels', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->enum('type', ['boys', 'girls', 'co-ed']);
                $table->text('address')->nullable();
                $table->string('warden_name')->nullable();
                $table->string('warden_phone')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hostel_rooms')) {
            Schema::create('hostel_rooms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hostel_id')->constrained('hostels')->cascadeOnDelete();
                $table->string('room_number');
                $table->unsignedInteger('capacity');
                $table->decimal('rent_amount', 8, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hostel_allocations')) {
            Schema::create('hostel_allocations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('room_id')->constrained('hostel_rooms')->cascadeOnDelete();
                $table->date('allocation_date');
                $table->date('vacate_date')->nullable();
                $table->enum('status', ['active', 'vacated'])->default('active');
                $table->timestamps();
            });
        }

        // ---------------------------------------------------------
        // 6. Inventory / Asset Management Expansion
        // ---------------------------------------------------------
        if (!Schema::hasTable('inventory_categories')) {
            Schema::create('inventory_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('inventory_items') && !Schema::hasColumn('inventory_items', 'category_id')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->foreignId('category_id')->nullable()->constrained('inventory_categories')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('inventory_issues')) {
            Schema::create('inventory_issues', function (Blueprint $table) {
                $table->id();
                $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Issued to staff
                $table->integer('quantity')->default(1);
                $table->date('issue_date');
                $table->date('return_date')->nullable();
                $table->enum('status', ['issued', 'returned', 'lost'])->default('issued');
                $table->timestamps();
            });
        }

        // ---------------------------------------------------------
        // 7. Academic Calendar
        // ---------------------------------------------------------
        if (!Schema::hasTable('academic_events')) {
            Schema::create('academic_events', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->date('start_date');
                $table->date('end_date');
                $table->enum('type', ['holiday', 'exam', 'ptm', 'event', 'other'])->default('event');
                $table->boolean('is_school_off')->default(false);
                $table->timestamps();
            });
        }

        // ---------------------------------------------------------
        // 8. Certificates
        // ---------------------------------------------------------
        if (!Schema::hasTable('certificate_templates')) {
            Schema::create('certificate_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->enum('type', ['transfer', 'character', 'appreciation', 'custom']);
                $table->longText('content');
                $table->json('variables')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('issued_certificates')) {
            Schema::create('issued_certificates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('template_id')->constrained('certificate_templates')->cascadeOnDelete();
                $table->date('issue_date');
                $table->string('reference_number')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issued_certificates');
        Schema::dropIfExists('certificate_templates');
        Schema::dropIfExists('academic_events');
        Schema::dropIfExists('inventory_issues');
        if (Schema::hasTable('inventory_items') && Schema::hasColumn('inventory_items', 'category_id')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            });
        }
        Schema::dropIfExists('inventory_categories');
        Schema::dropIfExists('hostel_allocations');
        Schema::dropIfExists('hostel_rooms');
        Schema::dropIfExists('hostels');
        Schema::dropIfExists('transport_allocations');
        Schema::dropIfExists('refund_requests');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('salary_structures');
    }
};
