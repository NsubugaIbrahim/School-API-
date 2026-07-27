<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Endpoints consumed by:
 *  - StaffDashboardScreen.kt (Task 7) -> getStaffSummary()
 *  - MarkAttendanceScreen.kt (Task 8) -> submitAttendance()
 *  - EnterResultsScreen.kt (Task 9)   -> submitExamResults()
 *
 * All routes are scoped to /api/staff/{id}/... or /api/classes/{classId}/...
 * where {id} is `staff.staff_id` returned at login.
 */
class StaffController extends Controller
{
    /**
     * GET /api/staff/{id}/summary
     *
     * Response shape MUST match Models.kt -> StaffSummary:
     *   { staffName, role, totalStudents, attendanceMarkedToday, pendingTasks }
     *
     * - Admin: totalStudents = count across the whole school
     * - Teacher: totalStudents = count across classes they are class_teacher for
     * - Bursar: totalStudents = whole school (for fee-overview context)
     */
   public function summary(int $id)
    {
        $staff = Staff::with('user.role')->findOrFail($id);
        $roleName = $staff->user->role->role_name ?? 'Staff';

        $classesSummary = [];

        if ($roleName === 'Teacher') {
            $classes = SchoolClass::where('class_teacher_id', $staff->user_id)
                ->withCount(['students' => function ($query) {
                    $query->where('status', 'Active');
                }])
                ->get();

            foreach ($classes as $class) {
                $classesSummary[] = [
                    'classId' => $class->class_id,
                    'className' => $class->class_name,
                    'studentCount' => $class->students_count
                ];
            }

            $totalStudents = array_sum(array_column($classesSummary, 'studentCount'));
        } else {
            $totalStudents = Student::where('status', 'Active')->count();
        }

        $attendanceMarkedToday = $this->attendanceMarkedToday($staff, $roleName);
        $pendingTasks = $this->pendingTasks($staff, $roleName);

        return response()->json([
            'staffName' => $staff->user->full_name,
            'role' => $roleName,
            'totalStudents' => $totalStudents,
            'attendanceMarkedToday' => $attendanceMarkedToday,
            'pendingTasks' => $pendingTasks,

            'class_id' => ($roleName === 'Teacher' && isset($classes) && $classes->isNotEmpty()) ? $classes->first()->class_id : null,
            'class_name' => ($roleName === 'Teacher' && isset($classes) && $classes->isNotEmpty()) ? $classes->first()->class_name : null,
            'stream' => ($roleName === 'Teacher' && isset($classes) && $classes->isNotEmpty()) ? $classes->first()->stream : null,
            'classes' => $classesSummary,
        ]);
    }

    /**
     * POST /api/classes/{classId}/attendance
     *
     * Expected body shape (array of objects):
     *   [{ "student_id": 12, "date": "2026-06-13", "status": "PRESENT", "reason": null }, ...]
     *
     * Upserts on (student_id, attendance_date) so re-submitting for the
     * same day updates existing records instead of creating duplicates —
     * this satisfies the "resubmitting same day" requirement in Task 8.
     */
    public function submitAttendance(Request $request, int $classId)
    {
        $schoolClass = SchoolClass::findOrFail($classId);

        $request->validate([
            '*.student_id' => 'required|integer|exists:students,student_id',
            '*.date' => 'required|date',
            '*.status' => 'required|string|in:PRESENT,ABSENT,LATE,EXCUSED,Present,Absent,Late,Excused',
            '*.reason' => 'nullable|string',
        ]);

        $recordedBy = $request->user()->user_id;

        DB::transaction(function () use ($request, $schoolClass, $recordedBy) {
            foreach ($request->all() as $entry) {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $entry['student_id'],
                        'attendance_date' => $entry['date'],
                    ],
                    [
                        'class_id' => $schoolClass->class_id,
                        'status' => ucfirst(strtolower($entry['status'])),
                        'reason' => $entry['reason'] ?? null,
                        'recorded_by' => $recordedBy,
                    ]
                );
            }
        });

        return response()->json(null, 204);
    }

    /**
     * POST /api/exams/{examId}/results
     *
     * Expected body shape (array of objects):
     *   [{ "student_id": 12, "subject_id": 1, "marks": 78, "total_marks": 100 }, ...]
     *
     * Grade is auto-calculated server-side via ExamResult::gradeForMarks()
     * so the Android app and API never disagree on grading bands.
     * Upserts on (exam_id, student_id, subject_id).
     */
    public function submitExamResults(Request $request, int $examId)
    {
        $exam = Exam::findOrFail($examId);

        $request->validate([
            '*.student_id' => 'required|integer|exists:students,student_id',
            '*.subject_id' => 'required|integer|exists:subjects,subject_id',
            '*.marks' => 'required|numeric|min:0',
            '*.total_marks' => 'nullable|numeric|min:1',
        ]);

        $enteredBy = $request->user()->user_id;

        DB::transaction(function () use ($request, $exam, $enteredBy) {
            foreach ($request->all() as $entry) {
                $totalMarks = $entry['total_marks'] ?? 100;
                $marks = $entry['marks'];

                if ($marks > $totalMarks) {
                    abort(422, "marks ({$marks}) cannot exceed total_marks ({$totalMarks})");
                }

                ExamResult::updateOrCreate(
                    [
                        'exam_id' => $exam->exam_id,
                        'student_id' => $entry['student_id'],
                        'subject_id' => $entry['subject_id'],
                    ],
                    [
                        'marks_obtained' => $marks,
                        'total_marks' => $totalMarks,
                        'grade' => ExamResult::gradeForMarks($marks, $totalMarks),
                        'entered_by' => $enteredBy,
                    ]
                );
            }
        });

        return response()->json(null, 204);
    }

    /**
     * GET /api/classes/{classId}/students
     *
     * Not in the original SchoolApiService.kt contract, but needed by
     * MarkAttendanceScreen.kt and EnterResultsScreen.kt to populate the
     * student list for a class (see TODOs in those files — add this
     * method to SchoolApiService.kt when picking up Task 8 or 9).
     *
     * Returns a simple array: [{ studentId, fullName, studentNo }, ...]
     */
    public function studentsInClass(int $classId)
    {
        if ($classId <= 0) {
            return response()->json([]);
        }

        return Student::where('class_id', $classId)
            ->where('status', 'Active')
            ->with('schoolClass') // Ensure relationship is loaded
            ->orderBy('first_name')
            ->get()
            ->map(fn ($s) => [
                'student_id' => $s->student_id,
                'first_name' => $s->first_name,
                'last_name' => $s->last_name,
                'student_no' => $s->student_no,
                'class_name' => $s->schoolClass->class_name ?? 'N/A',
                'stream' => $s->schoolClass->stream ?? 'N/A',
            ]);
    }

    // ───────────────────────── Helpers ─────────────────────────

    private function attendanceMarkedToday(Staff $staff, string $roleName): bool
    {
        $today = now()->toDateString();

        if ($roleName === 'Teacher') {
            $classIds = SchoolClass::where('class_teacher_id', $staff->user_id)->pluck('class_id');

            if ($classIds->isEmpty()) {
                return false;
            }

            return Attendance::whereIn('class_id', $classIds)
                ->where('attendance_date', $today)
                ->exists();
        }

        return Attendance::where('attendance_date', $today)->exists();
    }

    private function pendingTasks(Staff $staff, string $roleName): array
    {
        $tasks = [];

        if ($roleName === 'Teacher') {
            $classIds = SchoolClass::where('class_teacher_id', $staff->user_id)->pluck('class_id');

            if ($classIds->isNotEmpty()
                && ! Attendance::whereIn('class_id', $classIds)->where('attendance_date', now()->toDateString())->exists()) {
                $tasks[] = 'Attendance not yet marked for today';
            }
        }

        $unpublished = Exam::where('is_published', false)
            ->whereHas('results')
            ->pluck('exam_name');

        foreach ($unpublished as $examName) {
            $tasks[] = "Results not yet published for {$examName}";
        }

        return $tasks;
    }

    public function search(Request $request)
    {
        $q = $request->query('q');
        if (empty($q)) return response()->json([]);

        return Student::where('first_name', 'like', "%$q%")
            ->orWhere('last_name', 'like', "%$q%")
            ->with('schoolClass')
            ->limit(20)
            ->get()
            ->map(fn($s) => [
                'student_id' => $s->student_id,
                'first_name' => $s->first_name,
                'last_name' => $s->last_name,
                'class_name' => $s->schoolClass->class_name ?? 'N/A',
                'stream' => $s->schoolClass->stream ?? 'N/A', // Added stream
            ]);
    }
    
   public function exams()
    {
        // Consistent naming for Android ExamOption model
        return Exam::where('is_published', false)
            ->get(['exam_id', 'exam_name']);
    }
            
    public function subjects() {
        return DB::table ('subjects')->get (['subject_id', 'subject_name as name']);
    }       
    
}
