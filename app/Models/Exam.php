<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $table = 'exams';
    protected $primaryKey = 'exam_id';
    public $timestamps = false;

    protected $fillable = ['exam_name', 'exam_type', 'term_id', 'start_date', 'end_date', 'is_published'];

    public function term()
    {
        return $this->belongsTo(Term::class, 'term_id', 'term_id');
    }

    public function results()
    {
        return $this->hasMany(ExamResult::class, 'exam_id', 'exam_id');
    }
}
