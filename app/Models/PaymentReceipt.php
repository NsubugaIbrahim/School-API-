<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentReceipt extends Model
{
    protected $table = 'payment_receipts';
    protected $primaryKey = 'receipt_id';
    public $timestamps = false;

    protected $fillable = ['receipt_no', 'payment_id', 'issued_date', 'issued_by', 'pdf_path'];

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'payment_id');
    }
}
