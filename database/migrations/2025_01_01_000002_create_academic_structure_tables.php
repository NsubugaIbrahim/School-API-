<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id('class_id');
            $table->string('class_name', 50);
            $table->string('stream', 20)->nullable();
            $table->enum('level', ['Primary', 'Secondary', 'A-Level']);
            $table->integer('capacity')->default(40);
            $table->foreignId('class_teacher_id')->nullable()->references('user_id')->on('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id('subject_id');
            $table->string('subject_name', 100);
            $table->string('subject_code', 10)->unique();
            $table->enum('level', ['Primary', 'Secondary', 'A-Level']);
            $table->boolean('is_active')->default(true);
        });

        Schema::create('terms', function (Blueprint $table) {
            $table->id('term_id');
            $table->string('term_name', 20);
            $table->year('academic_year');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false);
            $table->unique(['term_name', 'academic_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terms');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('classes');
    }
};
