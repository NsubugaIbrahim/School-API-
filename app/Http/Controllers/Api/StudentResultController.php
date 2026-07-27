<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentResultController extends Controller
{
    /**
     * Get a list of all published exams for the dropdown menu.
     */
    public function getAvailableExams()
    {
        $exams = Exam::where('is_published', true)
            ->select('exam_id', 'exam_name', 'exam_type')
            ->get();

        return response()->json([
            'success' => true,
            'exams' => $exams
        ], 200);
    }

    /**
     * Get exam results and overall average for the logged-in student.
     */
    public function getExamResults(Request $request, $exam_id)
    {
        $user = Auth::user();

        // 1. Find the student record associated with the logged-in user
        // Assumes your students table has a user_id foreign key linking back to users
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student record not found for this user.'
            ], 404);
        }

        // 2. Fetch the exam details to make sure it exists
        $exam = Exam::find($exam_id);
        if (!$exam) {
            return response()->json([
                'success' => false,
                'message' => 'Exam not found.'
            ], 404);
        }

        // 3. Fetch exam results with the related subject details
        $results = ExamResult::with('subject:subject_id,subject_name')
            ->where('exam_id', $exam_id)
            ->where('student_id', $student->student_id)
            ->get();

        if ($results->isEmpty()) {
            return response()->json([
                'success' => true,
                'exam_name' => $exam->exam_name,
                'overall_average' => 0,
                'results' => []
            ], 200);
        }

        // 4. Calculate the overall average score
        $totalMarksObtained = $results->sum('marks_obtained');
        $totalMaxMarks = $results->sum('total_marks');
        
        $overallAverage = $totalMaxMarks > 0 
            ? round(($totalMarksObtained / $totalMaxMarks) * 100, 2) 
            : 0;

        // 5. Transform data structure into a clean mobile-friendly format
        $formattedResults = $results->map(function ($result) {
            return [
                'subject_name' => $result->subject ? $result->subject->subject_name : 'Unknown Subject',
                'marks_obtained' => (float) $result->marks_obtained,
                'total_marks' => (float) $result->total_marks,
                'grade' => $result->grade,
                'remarks' => $result->remarks,
            ];
        });

        return response()->json([
            'success' => true,
            'exam_name' => $exam->exam_name,
            'overall_average' => $overallAverage,
            'results' => $formattedResults
        ], 200);
    }
}