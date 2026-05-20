<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentChannel;
use Illuminate\Http\Request;
use App\Http\Resources\PaymentChannelResource;

class PaymentChannelController extends Controller
{
    public function index(Request $request)
    {
        // SMART FIX: Admin panel sends ?all=true to see everything.
        // Applicants (public) don't send it, so they only see the active one!
        if ($request->query('all') === 'true') {
            $channels = PaymentChannel::orderBy('created_at', 'desc')->get();
        } else {
            $channels = PaymentChannel::where('is_active', true)->get();
        }

        return PaymentChannelResource::collection($channels);
    }

    public function store(Request $request)
    {
        if ($request->has('account_number') && !$request->has('account_no')) {
            $request->merge(['account_no' => $request->input('account_number')]);
        }

        $validated = $request->validate([
            'payment_method' => 'required|string|max:255',
            'account_name'   => 'required|string|max:255',
            'account_no'     => 'required|string|max:255',
            'amount'         => 'numeric|min:0|nullable',
            'is_active'      => 'boolean'
        ]);

        // EXCLUSIVE ACTIVE LOCK: If this is active, turn all others off
        if (isset($validated['is_active']) && $validated['is_active']) {
            PaymentChannel::query()->update(['is_active' => false]);
        }

        $channel = PaymentChannel::create($validated);

        return response()->json([
            'message' => 'Payment channel added.',
            'data'    => new PaymentChannelResource($channel)
        ], 201);
    }

    public function update(Request $request, PaymentChannel $paymentChannel)
    {
        if ($request->has('account_number') && !$request->has('account_no')) {
            $request->merge(['account_no' => $request->input('account_number')]);
        }

        $validated = $request->validate([
            'payment_method' => 'sometimes|required|string|max:255',
            'account_name'   => 'sometimes|required|string|max:255',
            'account_no'     => 'sometimes|required|string|max:255',
            'amount'         => 'sometimes|numeric|min:0|nullable',
            'is_active'      => 'boolean'
        ]);

        // EXCLUSIVE ACTIVE LOCK: If this is active, turn all others off
        if (isset($validated['is_active']) && $validated['is_active']) {
            PaymentChannel::where('id', '!=', $paymentChannel->id)->update(['is_active' => false]);
        }

        $paymentChannel->update($validated);

        return response()->json([
            'message' => 'Payment channel updated.',
            'data'    => new PaymentChannelResource($paymentChannel)
        ]);
    }

    public function destroy(PaymentChannel $paymentChannel)
    {
        $paymentChannel->delete();
        return response()->json(['message' => 'Payment channel deleted.']);
    }
}
