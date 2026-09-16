<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function uploadProof(Request $request, Payment $payment)
    {
        abort_unless($payment->order->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'proof_image' => ['required', 'image', 'max:2048'],
        ]);

        $path = $request->file('proof_image')->store('payment-proofs', 'public');

        $payment->update([
            'proof_image' => $path,
        ]);

        return response()->json($payment);
    }
}
