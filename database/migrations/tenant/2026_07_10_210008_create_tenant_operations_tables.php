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
        Schema::create('transport_vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_number')->unique();
            $table->string('model_name')->nullable();
            $table->integer('capacity')->default(30);
            $table->string('driver_name');
            $table->string('driver_phone');
            $table->timestamps();
        });

        Schema::create('transport_routes', function (Blueprint $table) {
            $table->id();
            $table->string('route_name');
            $table->foreignId('vehicle_id')->nullable()->constrained('transport_vehicles')->onDelete('set null');
            $table->time('pickup_time')->nullable();
            $table->time('drop_time')->nullable();
            $table->decimal('monthly_fee', 8, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('transport_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('transport_routes')->onDelete('cascade');
            $table->string('stop_name');
            $table->time('pickup_time')->nullable();
            $table->timestamps();
        });

        Schema::create('library_books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('isbn')->nullable()->unique();
            $table->string('category')->nullable();
            $table->integer('total_copies')->default(1);
            $table->integer('available_copies')->default(1);
            $table->decimal('price', 8, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('book_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('library_books')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('return_date')->nullable();
            $table->decimal('fine_amount', 8, 2)->default(0);
            $table->string('status')->default('issued'); // issued, returned, overdue
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_name');
            $table->string('category'); // Books, Computers, Projectors, Furniture, Lab Equipment
            $table->integer('quantity')->default(1);
            $table->string('room_location')->nullable();
            $table->string('condition')->default('good'); // good, maintenance_required, broken
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('hr_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('month_year'); // e.g. 2026-07
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->decimal('allowances', 10, 2)->default(0);
            $table->decimal('deductions', 10, 2)->default(0);
            $table->decimal('net_salary', 10, 2)->default(0);
            $table->string('payment_status')->default('unpaid'); // unpaid, paid
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_salaries');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('book_issues');
        Schema::dropIfExists('library_books');
        Schema::dropIfExists('transport_stops');
        Schema::dropIfExists('transport_routes');
        Schema::dropIfExists('transport_vehicles');
    }
};
