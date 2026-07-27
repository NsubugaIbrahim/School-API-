<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id('announcement_id');
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->text('content');
            $table->string('cover_image', 255)->nullable();
            $table->enum('audience', ['All', 'Students', 'Parents', 'Staff'])->default('All');
            $table->boolean('is_published')->default(false);
            $table->foreignId('posted_by')->nullable()->references('user_id')->on('users')->nullOnDelete();
            $table->date('posted_date')->default(now());
            $table->date('expiry_date')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id('event_id');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->date('event_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location', 150)->nullable();
            $table->boolean('is_public')->default(true);
            $table->foreignId('created_by')->nullable()->references('user_id')->on('users')->nullOnDelete();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id('message_id');
            $table->string('full_name', 120);
            $table->string('email', 100);
            $table->string('phone', 20)->nullable();
            $table->string('subject', 200)->nullable();
            $table->text('message');
            $table->enum('status', ['New', 'Read', 'Responded'])->default('New');
            $table->timestamp('submitted_at')->useCurrent();
        });

        Schema::create('admission_applications', function (Blueprint $table) {
            $table->id('application_id');
            $table->string('applicant_first_name', 60);
            $table->string('applicant_last_name', 60);
            $table->enum('gender', ['Male', 'Female', 'Other']);
            $table->date('date_of_birth')->nullable();
            $table->foreignId('desired_class_id')->references('class_id')->on('classes');
            $table->string('guardian_name', 120);
            $table->string('guardian_phone', 20);
            $table->string('guardian_email', 100)->nullable();
            $table->string('previous_school', 150)->nullable();
            $table->enum('status', ['Pending', 'Under Review', 'Accepted', 'Rejected'])->default('Pending');
            $table->timestamp('submitted_at')->useCurrent();
            $table->foreignId('reviewed_by')->nullable()->references('user_id')->on('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_applications');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('events');
        Schema::dropIfExists('announcements');
    }
};
