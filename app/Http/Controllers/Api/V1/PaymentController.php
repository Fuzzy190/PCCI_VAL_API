<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Payment;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\MembershipType;
use App\Models\Transaction;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with(['applicant', 'membershipType', 'receivedBy'])->latest()->paginate(10);

        return PaymentResource::collection($payments);
    }

    // public function store(StorePaymentRequest $request)
    // {
    //     $payment = Payment::create($request->validated());

    //     return new PaymentResource($payment);
    // }

    public function store(StorePaymentRequest $request)
    {
        $membershipType = MembershipType::findOrFail($request->membership_type_id);
        
        // Generate OR Number once so we can use it in both tables
        $orNumber = 'OR-' . date('Y') . '-' . str_pad(Payment::count() + 1, 3, '0', STR_PAD_LEFT);
        $amount = $membershipType->price;

        // 1. Create the standard Payment record
        $payment = Payment::create([
            'applicant_id' => $request->applicant_id,
            'membership_type_id' => $request->membership_type_id,
            'or_number' => $orNumber,
            'amount' => $amount,
            'received_by_user_id' => auth()->id(),
            'payment_date' => now()->toDateString(),
        ]);

        // 2. AUTOMATICALLY CREATE GLOBAL TRANSACTION
        Transaction::create([
            'or_number' => $orNumber,
            'transaction_type' => 'initial_registration', // Matches your enum
            'applicant_id' => $request->applicant_id,     // Link to applicant
            'amount' => $amount,
            'payment_method' => 'cash', // Defaulting to cash, or get from request if available
            'status' => 'approved', 
            'processed_by_user_id' => auth()->id(),
            'notes' => 'Generated automatically from applicant registration payment.',
        ]);

        // Auto-update the applicant status to "paid"[cite: 8]
        $payment->applicant->update(['status' => 'paid']);

        return new PaymentResource($payment);
    }


    public function show(Payment $payment)
    {
        $payment->load(['applicant', 'membershipType', 'receivedBy']);

        return new PaymentResource($payment);
    }

    public function update(UpdatePaymentRequest $request, Payment $payment)
    {
        $payment->update($request->validated());

        return new PaymentResource($payment);
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();

        return response()->json([
            'message' => 'Payment deleted successfully'
        ]);
    }
}
