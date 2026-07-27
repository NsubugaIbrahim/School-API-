<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id('student_id');
            $table->foreignId('user_id')->nullable()->references('user_id')->on('users')->nullOnDelete();
            $table->string('student_no', 20)->unique();
            $table->string('first_name', 60);
            $table->string('last_name', 60);
            $table->enum('gender', ['Male', 'Female', 'Other']);
            $table->date('date_of_birth')->nullable();
            $table->foreignId('class_id')->references('class_id')->on('classes');
            $table->date('enrollment_date')->default(now());
            $table->enum('status', ['Active', 'Inactive', 'Transferred', 'Graduated'])->default('Active');
            $table->string('profile_photo', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('guardians', function (Blueprint $table) {
            $table->id('guardian_id');
            $table->foreignId('user_id')->nullable()->references('user_id')->on('users')->nullOnDelete();
            $table->string('full_name', 120);
            $table->enum('relationship', ['Father', 'Mother', 'Guardian', 'Other']);
            $table->string('phone_primary', 20);
            $table->string('phone_secondary', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
        });

        Schema::create('student_guardian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->references('student_id')->on('students')->cascadeOnDelete();
            $table->foreignId('guardian_id')->references('guardian_id')->on('guardians')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->unique(['student_id', 'guardian_id']);
        });

        Schema::create('staff', function (Blueprint $table) {
            $table->id('staff_id');
            $table->foreignId('user_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->string('staff_no', 20)->unique();
            $table->string('designation', 100)->nullable();
            $table->date('hire_date')->nullable();
            $table->string('qualification', 150)->nullable();
            $table->enum('status', ['Active', 'On Leave', 'Terminated'])->default('Active');
        });

        Schema::create('subject_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->references('subject_id')->on('subjects');
            $table->foreignId('staff_id')->references('staff_id')->on('staff');
            $table->foreignId('class_id')->references('class_id')->on('classes');
            $table->foreignId('term_id')->references('term_id')->on('terms');
            $table->unique(['subject_id', 'class_id', 'term_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_teacher');
        Schema::dropIfExists('staff');
        Schema::dropIfExists('student_guardian');
        Schema::dropIfExists('guardians');
        Schema::dropIfExists('students');
    }
};
