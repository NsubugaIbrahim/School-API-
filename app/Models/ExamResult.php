<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamResult extends Model
{
    protected $table = 'exam_results';
    protected $primaryKey = 'result_id';
    public $timestamps = false;

    protected $fillable = [
        'exam_id', 'student_id', 'subject_id', 'marks_obtained',
        'total_marks', 'grade', 'remarks', 'entered_by',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'subject_id');
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id', 'exam_id');
    }

    /**
     * Standard Uganda-style grading bands. Used by the
     * Enter Results screen (Task 9) for auto-calculating grades.
     *
     * Adjust these bands if your school uses a different scale —
     * this is the single source of truth so the Android app and
     * API always agree.
     */
    public static function gradeForMarks(float $marks, float $totalMarks = 100): string
    {
        $pct = $totalMarks > 0 ? ($marks / $totalMarks) * 100 : 0;

        return match (true) {
            $pct >= 80 => 'A',
            $pct >= 70 => 'B',
            $pct >= 60 => 'C',
            $pct >= 50 => 'D',
            $pct >= 40 => 'E',
            default => 'F',
        };
    }
}
