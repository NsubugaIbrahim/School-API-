<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    protected $table = 'terms';
    protected $primaryKey = 'term_id';
    public $timestamps = false;

    protected $fillable = ['term_name', 'academic_year', 'start_date', 'end_date', 'is_active'];

    public static function active(): ?self
    {
        return static::where('is_active', true)->first();
    }
}
