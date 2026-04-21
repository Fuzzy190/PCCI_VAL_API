<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentChannel;
use Illuminate\Http\Request;

class PaymentChannelController extends Controller
{
    // Fetches active channels for users/members
    public function index()
    {
        return response()->json(PaymentChannel::where('is_active', true)->get());
    }

    // Super Admin / Admin access
    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_method' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_no' => 'required|string|max:255',
            'is_active' => 'boolean'
        ]);

        $channel = PaymentChannel::create($validated);
        return response()->json(['message' => 'Payment channel added.', 'data' => $channel], 201);
    }

    public function update(Request $request, PaymentChannel $paymentChannel)
    {
        $validated = $request->validate([
            'payment_method' => 'sometimes|required|string|max:255',
            'account_name' => 'sometimes|required|string|max:255',
            'account_no' => 'sometimes|required|string|max:255',
            'is_active' => 'boolean'
        ]);

        $paymentChannel->update($validated);
        return response()->json(['message' => 'Payment channel updated.', 'data' => $paymentChannel]);
    }

    public function destroy(PaymentChannel $paymentChannel)
    {
        $paymentChannel->delete();
        return response()->json(['message' => 'Payment channel deleted.']);
    }
}