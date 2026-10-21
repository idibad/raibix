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
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->decimal('admission_fee', 10, 2)->default(0);
            $table->decimal('registration_fee', 10, 2)->default(0);
            $table->decimal('security_fee', 10, 2)->default(0);
            $table->decimal('monthly_tuition', 10, 2)->default(0);
            $table->decimal('annual_charges', 10, 2)->default(0);
            $table->decimal('lab_charges', 10, 2)->default(0);
            $table->decimal('transport_charges', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('fee_challans', function (Blueprint $table) {
            $table->id();
            $table->string('challan_number')->unique(); // e.g. CH-202607-0001
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('fee_type')->default('monthly'); // admission, monthly, annual, custom
            $table->string('month_year'); // e.g. 2026-07
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('tuition_fee', 10, 2)->default(0);
            $table->decimal('admission_fee', 10, 2)->default(0);
            $table->decimal('registration_fee', 10, 2)->default(0);
            $table->decimal('security_fee', 10, 2)->default(0);
            $table->decimal('lab_fee', 10, 2)->default(0);
            $table->decimal('transport_fee', 10, 2)->default(0);
            $table->decimal('library_fine', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0); // scholarship / sibling discount
            $table->decimal('fine_amount', 10, 2)->default(0); // overdue fine
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->string('status')->default('unpaid'); // unpaid, partial, paid, overdue
            $table->timestamps();
        });

        Schema::create('fee_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_challan_id')->constrained('fee_challans')->onDelete('cascade');
            $table->string('gateway'); // jazzcash, meezan_bank, bank_deposit, easypaisa, cash
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->string('transaction_reference')->nullable(); // TID or Bank Receipt Number
            
            // Regional Gateway specific fields (JazzCash / Meezan Bank API)
            $table->string('jazzcash_mobile_number')->nullable();
            $table->string('jazzcash_response_code')->nullable();
            $table->string('meezan_account_number')->nullable();
            $table->string('meezan_bill_reference')->nullable();
            $table->json('gateway_raw_response')->nullable();
            
            $table->string('status')->default('success'); // pending, success, failed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_transactions');
        Schema::dropIfExists('fee_challans');
        Schema::dropIfExists('fee_structures');
    }
};
