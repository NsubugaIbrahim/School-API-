<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $table = 'staff';
    protected $primaryKey = 'staff_id';
    public $timestamps = false;

    protected $fillable = ['user_id', 'staff_no', 'designation', 'hire_date', 'qualification', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Classes where this staff member is the class teacher.
     */
    public function classesTaught()
    {
        return $this->hasMany(SchoolClass::class, 'class_teacher_id', 'user_id');
    }
}
