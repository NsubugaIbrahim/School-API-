<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $table = 'students';
    protected $primaryKey = 'student_id';

    protected $fillable = [
        'user_id', 'student_no', 'first_name', 'last_name', 'gender',
        'date_of_birth', 'class_id', 'enrollment_date', 'status', 'profile_photo',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id', 'class_id');
    }

    public function guardians()
    {
        return $this->belongsToMany(
            Guardian::class,
            'student_guardian',
            'student_id',
            'guardian_id'
        )->withPivot('is_primary');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'student_id', 'student_id');
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class, 'student_id', 'student_id');
    }

    public function examResults()
    {
        return $this->hasMany(ExamResult::class, 'student_id', 'student_id');
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
