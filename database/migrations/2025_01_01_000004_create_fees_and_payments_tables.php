<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_types', function (Blueprint $table) {
            $table->id('fee_type_id');
            $table->string('fee_name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_mandatory')->default(true);
        });

        Schema::create('fee_structure', function (Blueprint $table) {
            $table->id('structure_id');
            $table->foreignId('class_id')->references('class_id')->on('classes');
            $table->foreignId('fee_type_id')->references('fee_type_id')->on('fee_types');
            $table->foreignId('term_id')->references('term_id')->on('terms');
            $table->decimal('amount', 10, 2);
            $table->date('due_date')->nullable();
            $table->unique(['class_id', 'fee_type_id', 'term_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->string('payment_code', 30)->unique();
            $table->foreignId('student_id')->references('student_id')->on('students');
            $table->foreignId('term_id')->references('term_id')->on('terms');
            $table->foreignId('fee_type_id')->references('fee_type_id')->on('fee_types');
            $table->decimal('amount_paid', 10, 2);
            $table->date('payment_date');
            $table->enum('payment_method', ['Cash', 'Mobile Money', 'Bank Transfer', 'Cheque', 'Online']);
            $table->string('reference_no', 60)->nullable();
            $table->foreignId('recorded_by')->nullable()->references('user_id')->on('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('payment_receipts', function (Blueprint $table) {
            $table->id('receipt_id');
            $table->string('receipt_no', 30)->unique();
            $table->foreignId('payment_id')->unique()->references('payment_id')->on('payments');
            $table->date('issued_date')->default(now());
            $table->foreignId('issued_by')->nullable()->references('user_id')->on('users')->nullOnDelete();
            $table->string('pdf_path', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_receipts');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('fee_structure');
        Schema::dropIfExists('fee_types');
    }
};
