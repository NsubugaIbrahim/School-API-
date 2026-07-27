<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Payment; // Used to safely check active terms if needed, or query Terms directly
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function getStudentCalendar(Request $request)
    {
        $user = Auth::user();

        // 1. Find the student linked to this user account
        $student = Student::where('user_id', $user->id)->first();
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student record not found for this account.'
            ], 404);
        }

        // 2. Determine the year and month to filter (default to the current year/month)
        $year = $request->query('year', Carbon::now()->year);
        $month = $request->query('month', Carbon::now()->month);

        // 3. Fetch monthly attendance records
        $records = Attendance::where('student_id', $student->student_id)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->select('attendance_date', 'status', 'reason')
            ->get();

        // 4. Calculate Attendance Percentage for the Active Term
        $activeTerm = Term::where('is_active', true)->first();
        $percentage = 100.00; // Default if no records exist yet

        if ($activeTerm) {
            $termRecords = Attendance::where('student_id', $student->student_id)
                ->whereBetween('attendance_date', [$activeTerm->start_date, $activeTerm->end_date])
                ->get();

            $totalDays = $termRecords->count();
            if ($totalDays > 0) {
                // Present, Late, and Excused usually don't count against basic presence metrics, or you can isolate 'Present'
                $daysPresent = $termRecords->whereIn('status', ['Present', 'Late', 'Excused'])->count();
                $percentage = round(($daysPresent / $totalDays) * 100, 1);
            }
        }

        // 5. Format data smoothly for the calendar view mapping
        $calendarData = $records->map(function ($record) {
            return [
                'date' => $record->attendance_date, // Formats as YYYY-MM-DD
                'status' => $record->status,        // Present, Absent, Late, Excused
                'reason' => $record->reason         // text or null
            ];
        });

        return response()->json([
            'success' => true,
            'term_percentage' => $percentage,
            'current_month' => Carbon::createFromDate($year, $month, 1)->format('F Y'),
            'records' => $calendarData
        ], 200);
    }
}