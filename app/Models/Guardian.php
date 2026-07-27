<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guardian extends Model
{
    protected $table = 'guardians';
    protected $primaryKey = 'guardian_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'full_name', 'relationship', 'phone_primary', 'phone_secondary', 'email', 'address',
    ];

    public function students()
    {
        return $this->belongsToMany(
            Student::class,
            'student_guardian',
            'guardian_id',
            'student_id'
        )->withPivot('is_primary');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
