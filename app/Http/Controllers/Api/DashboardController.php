<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Attendance;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getDashboardData(Request $request)
    {
        $user = Auth::user();
        
        // 1. Determine Role cleanly from the users table attribute
        // If your users table uses a different column name (like 'type'), swap 'role' here
        $role = strtolower($user->role ?? 'teacher'); 
        $isAdmin = ($role === 'admin');

        // 2. Welcome Message Metadata
        $welcome = [
            'name' => $user->name ?? $user->first_name ?? 'Staff Member',
            'role' => $isAdmin ? 'Admin' : 'Teacher'
        ];

        // 3. Card: Total Students calculation
        if ($isAdmin) {
            // Admin sees global school numbers
            $totalStudents = Student::count();
        } else {
            // Teacher sees only students assigned to classes they manage
            // Your migration links classes directly to user_id via 'class_teacher_id'
            $classIds = SchoolClass::where('class_teacher_id', $user->id)->pluck('class_id');
            $totalStudents = Student::whereIn('class_id', $classIds)->count();
        }

        // 4. Card: Today's Attendance Summary
        $today = Carbon::today()->toDateString();
        
        if ($isAdmin) {
            $totalClasses = SchoolClass::count();
            // Count unique classes that submitted attendance today
            $classesMarkedToday = Attendance::where('attendance_date', $today)
                ->distinct('class_id')
                ->count('class_id');
                
            $attendanceSummary = [
                'status' => $classesMarkedToday >= $totalClasses && $totalClasses > 0 ? 'Complete' : 'Pending',
                'detail_text' => "$classesMarkedToday of $totalClasses classes marked today"
            ];
        } else {
            // Teacher perspective: Check if their assigned classes have records today
            $classIds = SchoolClass::where('class_teacher_id', $user->id)->pluck('class_id');
            
            if ($classIds->isEmpty()) {
                $attendanceSummary = [
                    'status' => 'No Class',
                    'detail_text' => 'You are not assigned as a class teacher.'
                ];
            } else {
                $hasMarkedToday = Attendance::where('attendance_date', $today)
                    ->whereIn('class_id', $classIds)
                    ->exists();

                $attendanceSummary = [
                    'status' => $hasMarkedToday ? 'Marked' : 'Not Marked',
                    'detail_text' => $hasMarkedToday ? "Today's attendance is complete" : "You have not marked attendance today"
                ];
            }
        }

        // 5. Card: Pending Tasks (Results verification)
        $latestExam = Exam::latest('exam_id')->first();
        $pendingTasks = [];

        if ($latestExam) {
            if ($isAdmin) {
                $pendingTasks[] = "Verify and finalize entries for " . $latestExam->exam_name;
            } else {
                // Check if this specific teacher has entered any marks for this exam
                $hasEnteredResults = ExamResult::where('exam_id', $latestExam->exam_id)
                    ->where('entered_by', $user->id)
                    ->exists();

                if (!$hasEnteredResults) {
                    $pendingTasks[] = "Results not yet entered for " . $latestExam->exam_name;
                }
            }
        }
        
        if (empty($pendingTasks)) {
            $pendingTasks[] = "All caught up! No pending tasks.";
        }

        // 6. Quick Actions Config
        $quickActions = [
            ['label' => 'Mark Attendance', 'action' => 'MARK_ATTENDANCE', 'enabled' => !$isAdmin],
            ['label' => 'Enter Results', 'action' => 'ENTER_RESULTS', 'enabled' => !$isAdmin],
            ['label' => 'View Students', 'action' => 'VIEW_STUDENTS', 'enabled' => true]
        ];

        return response()->json([
            'success' => true,
            'welcome' => $welcome,
            'total_students' => $totalStudents,
            'attendance_summary' => $attendanceSummary,
            'pending_tasks' => $pendingTasks,
            'quick_actions' => $quickActions
        ], 200);
    }
}