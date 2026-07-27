<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'payment_id';
    const UPDATED_AT = null;

    protected $fillable = [
        'payment_code', 'student_id', 'term_id', 'fee_type_id', 'amount_paid',
        'payment_date', 'payment_method', 'reference_no', 'recorded_by', 'notes',
    ];

    public function feeType()
    {
        return $this->belongsTo(FeeType::class, 'fee_type_id', 'fee_type_id');
    }

    public function term()
    {
        return $this->belongsTo(Term::class, 'term_id', 'term_id');
    }

    public function receipt()
    {
        return $this->hasOne(PaymentReceipt::class, 'payment_id', 'payment_id');
    }
}
