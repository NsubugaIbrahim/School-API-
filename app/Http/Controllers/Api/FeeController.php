<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\FeeStructure;
use App\Models\Payment;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeeController extends Controller
{
    public function getFeeSummaryAndHistory(Request $request)
    {
        $user = Auth::user();

        // 1. Identify the logged-in student
        $student = Student::where('user_id', $user->id)->first();
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student record not found for this account.'
            ], 404);
        }

        // 2. Identify the currently active term
        $activeTerm = Term::where('is_active', true)->first();
        if (!$activeTerm) {
            return response()->json([
                'success' => false,
                'message' => 'There is no active academic term set.'
            ], 404);
        }

        // 3. Calculate Total Fees Owed for this student's class during the active term
        $totalFeesDue = FeeStructure::where('class_id', $student->class_id)
            ->where('term_id', $activeTerm->term_id)
            ->sum('amount');

        // 4. Calculate Total Paid by the student for this term
        $totalPaid = Payment::where('student_id', $student->student_id)
            ->where('term_id', $activeTerm->term_id)
            ->sum('amount_paid');

        // 5. Calculate Outstanding Balance
        $outstandingBalance = max(0, $totalFeesDue - $totalPaid);

        // 6. Determine Status Badge
        $status = 'Unpaid';
        if ($totalPaid >= $totalFeesDue && $totalFeesDue > 0) {
            $status = 'Fully Paid';
        } elseif ($totalPaid > 0) {
            $status = 'Partial';
        }

        // 7. Get Payment History along with matching receipt numbers
        // We use leftJoin on payment_receipts using payment_id based on your schema
        $history = Payment::where('student_id', $student->student_id)
            ->where('payments.term_id', $activeTerm->term_id)
            ->leftJoin('payment_receipts', 'payments.payment_id', '=', 'payment_receipts.payment_id')
            ->select(
                'payments.payment_date',
                'payments.amount_paid',
                'payments.payment_method',
                'payment_receipts.receipt_no'
            )
            ->orderBy('payments.payment_date', 'desc')
            ->get();

        // 8. Format the final output cleanly for mobile UI consumption
        return response()->json([
            'success' => true,
            'term_name' => $activeTerm->term_name,
            'summary' => [
                'total_due' => (float) $totalFeesDue,
                'total_paid' => (float) $totalPaid,
                'outstanding_balance' => (float) $outstandingBalance,
                'status_badge' => $status
            ],
            'payment_history' => $history->map(function ($payment) {
                return [
                    'date' => $payment->payment_date,
                    'amount' => (float) $payment->amount_paid,
                    'payment_method' => $payment->payment_method,
                    'receipt_no' => $payment->receipt_no ?? 'N/A'
                ];
            })
        ], 200);
    }
}