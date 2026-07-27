<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id('attendance_id');
            $table->foreignId('student_id')->references('student_id')->on('students');
            $table->foreignId('class_id')->references('class_id')->on('classes');
            $table->date('attendance_date');
            $table->enum('status', ['Present', 'Absent', 'Late', 'Excused']);
            $table->text('reason')->nullable();
            $table->foreignId('recorded_by')->nullable()->references('user_id')->on('users')->nullOnDelete();
            $table->unique(['student_id', 'attendance_date']);
        });

        Schema::create('exams', function (Blueprint $table) {
            $table->id('exam_id');
            $table->string('exam_name', 100);
            $table->enum('exam_type', ['Continuous Assessment', 'End of Term', 'Mock', 'UNEB']);
            $table->foreignId('term_id')->references('term_id')->on('terms');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_published')->default(false);
        });

        Schema::create('exam_results', function (Blueprint $table) {
            $table->id('result_id');
            $table->foreignId('exam_id')->references('exam_id')->on('exams');
            $table->foreignId('student_id')->references('student_id')->on('students');
            $table->foreignId('subject_id')->references('subject_id')->on('subjects');
            $table->decimal('marks_obtained', 6, 2);
            $table->decimal('total_marks', 6, 2)->default(100);
            $table->string('grade', 5)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('entered_by')->nullable()->references('user_id')->on('users')->nullOnDelete();
            $table->unique(['exam_id', 'student_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_results');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('attendance');
    }
};
