<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeType extends Model
{
    protected $table = 'fee_types';
    protected $primaryKey = 'fee_type_id';
    public $timestamps = false;

    protected $fillable = ['fee_name', 'description', 'is_mandatory'];
}
