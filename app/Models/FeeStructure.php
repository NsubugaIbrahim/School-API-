<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    protected $table = 'fee_structure';
    protected $primaryKey = 'structure_id';
    public $timestamps = false;

    protected $fillable = ['class_id', 'fee_type_id', 'term_id', 'amount', 'due_date'];

    public function feeType()
    {
        return $this->belongsTo(FeeType::class, 'fee_type_id', 'fee_type_id');
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id', 'class_id');
    }

    public function term()
    {
        return $this->belongsTo(Term::class, 'term_id', 'term_id');
    }
}
