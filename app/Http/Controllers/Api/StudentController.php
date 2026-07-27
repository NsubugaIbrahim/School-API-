<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\FeeStructure;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Http\Request;

/**
 * Endpoints consumed by:
 *  - StudentDashboardViewModel.kt  -> getStudentSummary()
 *  - ResultsScreen.kt (Task 4)     -> getExamOptions(), getExamResults()
 *  - FeesScreen.kt (Task 5)        -> getFeeSummary()
 *  - AttendanceScreen.kt (Task 6)  -> getAttendance()
 *
 * All routes are scoped to /api/students/{id}/... where {id} is the
 * `students.student_id` returned at login (see AuthController::resolveDomainId).
 */
class StudentController extends Controller
{
    /**
     * GET /api/students/{id}/summary
     *
     * Response shape MUST match Models.kt -> StudentSummary:
     *   { studentName, className, termName, feeBalance,
     *     attendancePercent, latestExamAverage }
     */
    public function summary(int $id)
    {
        $student = Student::with('schoolClass')->findOrFail($id);
        $term = Term::active();

        $feeBalance = $this->calculateFeeBalance($student, $term?->term_id);
        $attendancePercent = $this->calculateAttendancePercent($student);
        $latestExamAverage = $this->latestExamAverage($student);

        return response()->json([
            'studentName' => $student->fullName(),
            'className' => $student->schoolClass->displayName(),
            'termName' => $term ? "{$term->term_name} {$term->academic_year}" : 'N/A',
            'feeBalance' => $feeBalance,
            'attendancePercent' => $attendancePercent,
            'latestExamAverage' => $latestExamAverage,
        ]);
    }

    /**
     * GET /api/students/{id}/exams
     *
     * Returns the list of exams the student can view results for.
     * Response shape MUST match Models.kt -> List<ExamOption>:
     *   [{ examId, examName, termName, isPublished }, ...]
     */
    public function examOptions(int $id)
    {
        $student = Student::findOrFail($id);

        // Only list exams for which this student has at least one result.
        $examIds = ExamResult::where('student_id', $student->student_id)
            ->distinct()
            ->pluck('exam_id');

        $exams = Exam::with('term')
            ->whereIn('exam_id', $examIds)
            ->orderByDesc('exam_id')
            ->get()
            ->map(fn (Exam $exam) => [
                'examId' => $exam->exam_id,
                'examName' => $exam->exam_name,
                'termName' => $exam->term ? "{$exam->term->term_name} {$exam->term->academic_year}" : '',
                'isPublished' => (bool) $exam->is_published,
            ]);

        return response()->json($exams);
    }

    /**
     * GET /api/students/{id}/results?exam_id=
     *
     * Response shape MUST match Models.kt -> ExamResultResponse:
     *   { exam: ExamOption, results: [SubjectResult, ...], average }
     *
     * If the exam is not published, `results` is returned empty and
     * the Android app should show "Results not yet released" (see
     * ResultsScreen.kt TODOs).
     */
    public function results(Request $request, int $id)
    {
        $student = Student::findOrFail($id);

        $request->validate(['exam_id' => 'required|integer|exists:exams,exam_id']);

        $exam = Exam::with('term')->findOrFail($request->integer('exam_id'));

        $examOption = [
            'examId' => $exam->exam_id,
            'examName' => $exam->exam_name,
            'termName' => $exam->term ? "{$exam->term->term_name} {$exam->term->academic_year}" : '',
            'isPublished' => (bool) $exam->is_published,
        ];

        if (! $exam->is_published) {
            return response()->json([
                'exam' => $examOption,
                'results' => [],
                'average' => 0,
            ]);
        }

        $results = ExamResult::with('subject')
            ->where('exam_id', $exam->exam_id)
            ->where('student_id', $student->student_id)
            ->get();

        $subjectResults = $results->map(fn (ExamResult $r) => [
            'subjectName' => $r->subject->subject_name,
            'marksObtained' => (float) $r->marks_obtained,
            'totalMarks' => (float) $r->total_marks,
            'grade' => $r->grade,
        ]);

        $average = $results->isEmpty()
            ? 0
            : round($results->avg(fn (ExamResult $r) => ($r->marks_obtained / $r->total_marks) * 100), 1);

        return response()->json([
            'exam' => $examOption,
            'results' => $subjectResults,
            'average' => $average,
        ]);
    }

    /**
     * GET /api/students/{id}/fees
     *
     * Response shape MUST match Models.kt -> FeeSummary:
     *   { totalDue, totalPaid, balance, status, history: [PaymentRecord, ...] }
     *
     * `status` must be one of: FULLY_PAID, PARTIAL, UNPAID
     * (see PaymentStatus enum in Models.kt)
     */
    public function fees(int $id)
    {
        $student = Student::findOrFail($id);
        $term = Term::active();

        $totalDue = FeeStructure::where('class_id', $student->class_id)
            ->when($term, fn ($q) => $q->where('term_id', $term->term_id))
            ->sum('amount');

        $payments = Payment::with('receipt')
            ->where('student_id', $student->student_id)
            ->when($term, fn ($q) => $q->where('term_id', $term->term_id))
            ->orderByDesc('payment_date')
            ->get();

        $totalPaid = $payments->sum('amount_paid');
        $balance = max(0, $totalDue - $totalPaid);

        $status = match (true) {
            $totalPaid <= 0 => 'UNPAID',
            $balance <= 0 => 'FULLY_PAID',
            default => 'PARTIAL',
        };

        $history = $payments->map(fn (Payment $p) => [
            'paymentCode' => $p->payment_code,
            'date' => $p->payment_date,
            'amount' => (float) $p->amount_paid,
            'method' => $p->payment_method,
            'receiptNumber' => $p->receipt?->receipt_no,
        ]);

        return response()->json([
            'totalDue' => (float) $totalDue,
            'totalPaid' => (float) $totalPaid,
            'balance' => (float) $balance,
            'status' => $status,
            'history' => $history,
        ]);
    }

    /**
     * GET /api/students/{id}/attendance?month=&year=
     *
     * Response shape MUST match Models.kt -> AttendanceSummary:
     *   { percent, days: [AttendanceDay, ...] }
     *
     * `status` for each day must be one of: PRESENT, ABSENT, LATE, EXCUSED
     * (see AttendanceStatus enum in Models.kt)
     */
    public function attendance(Request $request, int $id)
    {
        $student = Student::findOrFail($id);

        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $month = $request->integer('month');
        $year = $request->integer('year');

        $records = Attendance::where('student_id', $student->student_id)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->orderBy('attendance_date')
            ->get();

        $days = $records->map(fn (Attendance $a) => [
            'date' => $a->attendance_date,
            'status' => strtoupper($a->status),
            'reason' => $a->reason,
        ]);

        $percent = $records->isEmpty()
            ? 0
            : round($records->where('status', 'Present')->count() / $records->count() * 100, 1);

        return response()->json([
            'percent' => $percent,
            'days' => $days,
        ]);
    }

    // ───────────────────────── Helpers ─────────────────────────

    private function calculateFeeBalance(Student $student, ?int $termId): float
    {
        $totalDue = FeeStructure::where('class_id', $student->class_id)
            ->when($termId, fn ($q) => $q->where('term_id', $termId))
            ->sum('amount');

        $totalPaid = Payment::where('student_id', $student->student_id)
            ->when($termId, fn ($q) => $q->where('term_id', $termId))
            ->sum('amount_paid');

        return max(0, (float) $totalDue - (float) $totalPaid);
    }

    private function calculateAttendancePercent(Student $student): float
    {
        $total = Attendance::where('student_id', $student->student_id)->count();

        if ($total === 0) {
            return 0;
        }

        $present = Attendance::where('student_id', $student->student_id)
            ->where('status', 'Present')
            ->count();

        return round($present / $total * 100, 1);
    }

    private function latestExamAverage(Student $student): ?float
    {
        $latestExamId = ExamResult::where('student_id', $student->student_id)
            ->orderByDesc('exam_id')
            ->value('exam_id');

        if (! $latestExamId) {
            return null;
        }

        $results = ExamResult::where('student_id', $student->student_id)
            ->where('exam_id', $latestExamId)
            ->get();

        if ($results->isEmpty()) {
            return null;
        }

        return round($results->avg(fn (ExamResult $r) => ($r->marks_obtained / $r->total_marks) * 100), 1);
    }
}
