<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'user_id';

    protected $fillable = [
        'full_name', 'username', 'email', 'password_hash',
        'role_id', 'phone', 'profile_photo', 'status', 'last_login',
    ];

    protected $hidden = ['password_hash'];

    /**
     * Laravel's auth system expects a `password` attribute by default.
     * We store it as `password_hash` to match the school_management_system
     * schema, so map it here.
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    public function student()
    {
        return $this->hasOne(Student::class, 'user_id', 'user_id');
    }

    public function staff()
    {
        return $this->hasOne(Staff::class, 'user_id', 'user_id');
    }
}
