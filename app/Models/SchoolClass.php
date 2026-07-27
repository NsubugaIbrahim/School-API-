<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Maps to the `classes` table. Named SchoolClass because `Class`
 * is a reserved word in PHP and can't be used as a class name.
 */
class SchoolClass extends Model
{
    protected $table = 'classes';
    protected $primaryKey = 'class_id';
    public $timestamps = false;

    protected $fillable = [
        'class_name', 'stream', 'level', 'capacity', 'class_teacher_id',
    ];

    public function students()
    {
        return $this->hasMany(Student::class, 'class_id', 'class_id');
    }

    public function classTeacher()
    {
        return $this->belongsTo(User::class, 'class_teacher_id', 'user_id');
    }

    public function feeStructures()
    {
        return $this->hasMany(FeeStructure::class, 'class_id', 'class_id');
    }

    public function displayName(): string
    {
        return $this->stream ? "{$this->class_name} {$this->stream}" : $this->class_name;
    }
}
