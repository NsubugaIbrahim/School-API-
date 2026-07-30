<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\FeeStructure;
use App\Models\FeeType;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds enough data to test every screen in the Android app end-to-end.
 *
 * Run with: php artisan db:seed
 *
 * Test login credentials (password is "password" for all):
 *   Student: brian.student@school.ug
 *   Teacher: grace.a@school.ug
 *   Bursar:  bursar@school.ug
 *   Admin:   admin@school.ug
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // If database is already seeded, stop immediately
        if (\App\Models\User::exists()) {
            $this->command->info('Database already seeded. Skipping.');
            return;
        }
        // ── Roles ──
        $roles = [];
        foreach (['Admin', 'Teacher', 'Bursar', 'Student', 'Parent'] as $name) {
            $roles[$name] = Role::create([
                'role_name' => $name,
                'description' => "{$name} role",
            ]);
        }

        // ── Terms ──
        $term1 = Term::create(['term_name' => 'Term 1', 'academic_year' => 2025, 'start_date' => '2025-02-03', 'end_date' => '2025-05-09', 'is_active' => false]);
        $term2 = Term::create(['term_name' => 'Term 2', 'academic_year' => 2025, 'start_date' => '2025-06-02', 'end_date' => '2025-08-08', 'is_active' => true]);
        Term::create(['term_name' => 'Term 3', 'academic_year' => 2025, 'start_date' => '2025-09-15', 'end_date' => '2025-11-28', 'is_active' => false]);

        // ── Classes ──
        $s1a = SchoolClass::create(['class_name' => 'Senior 1', 'stream' => 'A', 'level' => 'Secondary', 'capacity' => 50]);
        $s2a = SchoolClass::create(['class_name' => 'Senior 2', 'stream' => 'A', 'level' => 'Secondary', 'capacity' => 50]);
        $s3a = SchoolClass::create(['class_name' => 'Senior 3', 'stream' => 'A', 'level' => 'Secondary', 'capacity' => 50]);
        $s4a = SchoolClass::create(['class_name' => 'Senior 4', 'stream' => 'A', 'level' => 'Secondary', 'capacity' => 50]);

        // ── Subjects ──
        $maths = Subject::create(['subject_name' => 'Mathematics', 'subject_code' => 'MTH', 'level' => 'Secondary']);
        $english = Subject::create(['subject_name' => 'English Language', 'subject_code' => 'ENG', 'level' => 'Secondary']);
        $physics = Subject::create(['subject_name' => 'Physics', 'subject_code' => 'PHY', 'level' => 'Secondary']);
        $biology = Subject::create(['subject_name' => 'Biology', 'subject_code' => 'BIO', 'level' => 'Secondary']);
        $computer = Subject::create(['subject_name' => 'Computer Studies', 'subject_code' => 'CST', 'level' => 'Secondary']);

        // ── Fee types ──
        $tuition = FeeType::create(['fee_name' => 'Tuition Fee', 'description' => 'Main school fees', 'is_mandatory' => true]);
        $development = FeeType::create(['fee_name' => 'Development Fee', 'description' => 'Infrastructure', 'is_mandatory' => true]);
        $pta = FeeType::create(['fee_name' => 'PTA Fee', 'description' => 'Parents association', 'is_mandatory' => true]);

        // ── Fee structure for Term 2, Senior 1A ──
        FeeStructure::create(['class_id' => $s1a->class_id, 'fee_type_id' => $tuition->fee_type_id, 'term_id' => $term2->term_id, 'amount' => 450000, 'due_date' => '2025-06-15']);
        FeeStructure::create(['class_id' => $s1a->class_id, 'fee_type_id' => $development->fee_type_id, 'term_id' => $term2->term_id, 'amount' => 50000, 'due_date' => '2025-06-15']);
        FeeStructure::create(['class_id' => $s1a->class_id, 'fee_type_id' => $pta->fee_type_id, 'term_id' => $term2->term_id, 'amount' => 20000, 'due_date' => '2025-06-15']);

        // ── Users ──
        $adminUser = User::create([
            'full_name' => 'Admin User', 'username' => 'admin', 'email' => 'admin@school.ug',
            'password_hash' => Hash::make('password'), 'role_id' => $roles['Admin']->role_id, 'phone' => '0700000000',
        ]);

        $teacherUser = User::create([
            'full_name' => 'Grace Akullo', 'username' => 'gakullo', 'email' => 'grace.a@school.ug',
            'password_hash' => Hash::make('password'), 'role_id' => $roles['Teacher']->role_id, 'phone' => '0723456789',
        ]);

        $bursarUser = User::create([
            'full_name' => 'John Ssebugwawo', 'username' => 'jbursar', 'email' => 'bursar@school.ug',
            'password_hash' => Hash::make('password'), 'role_id' => $roles['Bursar']->role_id, 'phone' => '0712345678',
        ]);

        $studentUser = User::create([
            'full_name' => 'Brian Mukasa', 'username' => 'bmukasa', 'email' => 'brian.student@school.ug',
            'password_hash' => Hash::make('password'), 'role_id' => $roles['Student']->role_id, 'phone' => '0701111111',
        ]);

        $parentUser = User::create([
            'full_name' => 'Aisha Nalubega (Parent)', 'username' => 'analubega_p', 'email' => 'aisha.parent@gmail.com',
            'password_hash' => Hash::make('password'), 'role_id' => $roles['Parent']->role_id, 'phone' => '0702222222',
        ]);

        // ── Staff ──
        $teacherStaff = Staff::create(['user_id' => $teacherUser->user_id, 'staff_no' => 'TCH001', 'designation' => 'Mathematics Teacher', 'hire_date' => '2020-02-01']);
        Staff::create(['user_id' => $bursarUser->user_id, 'staff_no' => 'BUR001', 'designation' => 'Bursar', 'hire_date' => '2019-03-01']);
        Staff::create(['user_id' => $adminUser->user_id, 'staff_no' => 'ADM001', 'designation' => 'Administrator', 'hire_date' => '2018-01-01']);

        // Grace is the class teacher for Senior 1A
        $s1a->update(['class_teacher_id' => $teacherUser->user_id]);

        // ── Students ──
        $brian = Student::create([
            'user_id' => $studentUser->user_id, 'student_no' => 'SCH2025001',
            'first_name' => 'Brian', 'last_name' => 'Mukasa', 'gender' => 'Male',
            'date_of_birth' => '2010-03-14', 'class_id' => $s1a->class_id, 'enrollment_date' => '2025-02-03',
        ]);

        $aisha = Student::create([
            'user_id' => null, 'student_no' => 'SCH2025002',
            'first_name' => 'Aisha', 'last_name' => 'Nalubega', 'gender' => 'Female',
            'date_of_birth' => '2010-07-22', 'class_id' => $s1a->class_id, 'enrollment_date' => '2025-02-03',
        ]);

        Student::create([
            'user_id' => null, 'student_no' => 'SCH2025003',
            'first_name' => 'Daniel', 'last_name' => 'Ssali', 'gender' => 'Male',
            'date_of_birth' => '2009-01-05', 'class_id' => $s2a->class_id, 'enrollment_date' => '2025-02-03',
        ]);

        Student::create([
            'user_id' => null, 'student_no' => 'SCH2025004',
            'first_name' => 'Patricia', 'last_name' => 'Apio', 'gender' => 'Female',
            'date_of_birth' => '2008-11-30', 'class_id' => $s3a->class_id, 'enrollment_date' => '2025-02-03',
        ]);

        // ── Guardian: parent of Aisha ──
        $guardian = Guardian::create([
            'user_id' => $parentUser->user_id, 'full_name' => 'Aisha Nalubega Snr',
            'relationship' => 'Mother', 'phone_primary' => '0702222222', 'email' => 'aisha.parent@gmail.com',
        ]);
        $aisha->guardians()->attach($guardian->guardian_id, ['is_primary' => true]);

        // ── Payments for Brian (Term 2) ──
        $payment1 = Payment::create([
            'payment_code' => 'PAY-2025-000001', 'student_id' => $brian->student_id, 'term_id' => $term2->term_id,
            'fee_type_id' => $tuition->fee_type_id, 'amount_paid' => 450000, 'payment_date' => '2025-06-03',
            'payment_method' => 'Mobile Money', 'reference_no' => 'MM2025060300123', 'recorded_by' => $bursarUser->user_id,
        ]);
        PaymentReceipt::create(['receipt_no' => 'RCP-2025-000001', 'payment_id' => $payment1->payment_id, 'issued_date' => '2025-06-03', 'issued_by' => $bursarUser->user_id]);

        $payment2 = Payment::create([
            'payment_code' => 'PAY-2025-000002', 'student_id' => $brian->student_id, 'term_id' => $term2->term_id,
            'fee_type_id' => $development->fee_type_id, 'amount_paid' => 50000, 'payment_date' => '2025-06-03',
            'payment_method' => 'Mobile Money', 'reference_no' => 'MM2025060300124', 'recorded_by' => $bursarUser->user_id,
        ]);
        PaymentReceipt::create(['receipt_no' => 'RCP-2025-000002', 'payment_id' => $payment2->payment_id, 'issued_date' => '2025-06-03', 'issued_by' => $bursarUser->user_id]);
        // Brian has paid 500,000 of 520,000 due -> PARTIAL status

        // ── Attendance for Brian (last 5 school days) ──
        $statuses = ['Present', 'Present', 'Late', 'Present', 'Absent'];
        $date = now()->subDays(7);
        foreach ($statuses as $status) {
            // skip weekends
            while ($date->isWeekend()) {
                $date->addDay();
            }
            Attendance::create([
                'student_id' => $brian->student_id, 'class_id' => $s1a->class_id,
                'attendance_date' => $date->toDateString(), 'status' => $status,
                'recorded_by' => $teacherUser->user_id,
            ]);
            $date->addDay();
        }

        // ── Exam + results for Brian ──
        $exam = Exam::create([
            'exam_name' => 'Term 1 End of Term Exams', 'exam_type' => 'End of Term',
            'term_id' => $term1->term_id, 'start_date' => '2025-04-28', 'end_date' => '2025-05-09',
            'is_published' => true,
        ]);

        $results = [
            [$maths->subject_id, 72], [$english->subject_id, 68],
            [$physics->subject_id, 55], [$biology->subject_id, 60], [$computer->subject_id, 80],
        ];
        foreach ($results as [$subjectId, $marks]) {
            ExamResult::create([
                'exam_id' => $exam->exam_id, 'student_id' => $brian->student_id, 'subject_id' => $subjectId,
                'marks_obtained' => $marks, 'total_marks' => 100,
                'grade' => ExamResult::gradeForMarks($marks), 'entered_by' => $teacherUser->user_id,
            ]);
        }

        // An unpublished exam — used to test "Results not yet released"
        Exam::create([
            'exam_name' => 'Term 2 Mid-Term CAT', 'exam_type' => 'Continuous Assessment',
            'term_id' => $term2->term_id, 'start_date' => '2025-07-01', 'end_date' => '2025-07-05',
            'is_published' => false,
        ]);

        // ── Announcements ──
        Announcement::create([
            'title' => 'Term 2 Reporting Day', 'slug' => 'term-2-reporting-day',
            'content' => 'All students are required to report on Monday 2nd June 2025 with all fees cleared.',
            'audience' => 'All', 'is_published' => true, 'posted_by' => $adminUser->user_id, 'posted_date' => '2025-05-20',
        ]);

        Announcement::create([
            'title' => 'Mid-Term Test Schedule', 'slug' => 'mid-term-test-schedule',
            'content' => 'Mid-term tests will run from 1st to 5th July. Check the notice board for the full timetable.',
            'audience' => 'Students', 'is_published' => true, 'posted_by' => $teacherUser->user_id, 'posted_date' => '2025-06-10',
        ]);

        Announcement::create([
            'title' => 'Staff Meeting — Friday', 'slug' => 'staff-meeting-friday',
            'content' => 'All staff to attend the Friday 3pm meeting in the staff room.',
            'audience' => 'Staff', 'is_published' => true, 'posted_by' => $adminUser->user_id, 'posted_date' => '2025-06-11',
        ]);

        $this->command->info('Seeding complete!');
        $this->command->info('Test logins (password = "password"):');
        $this->command->info('  Student: brian.student@school.ug');
        $this->command->info('  Teacher: grace.a@school.ug');
        $this->command->info('  Bursar:  bursar@school.ug');
        $this->command->info('  Admin:   admin@school.ug');
    }
}
