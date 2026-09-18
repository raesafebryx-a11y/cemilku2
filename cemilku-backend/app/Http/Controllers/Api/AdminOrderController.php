<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(
            $request->user()->role === 'admin',
            403,
            'Anda tidak memiliki akses admin.'
        );

        $orders = Order::with([
            'user:id,name,email',
            'address',
            'items.product',
            'payment',
        ])
        ->latest()
        ->get();

        return response()->json($orders);
    }

    public function show(Request $request, Order $order)
    {
        abort_unless(
            $request->user()->role === 'admin',
            403,
            'Anda tidak memiliki akses admin.'
        );

        $order->load([
            'user:id,name,email',
            'address',
            'items.product',
            'payment',
        ]);

        return response()->json($order);
    }

    public function updateStatus(Request $request, Order $order)
    {
        abort_unless(
            $request->user()->role === 'admin',
            403,
            'Anda tidak memiliki akses admin.'
        );

        $data = $request->validate([
            'status' => [
                'required',
                'string',
                'in:pending,processing,shipped,completed,cancelled',
            ],
        ]);

        $order->update([
            'status' => $data['status'],
        ]);

        $order->load([
            'user:id,name,email',
            'address',
            'items.product',
            'payment',
        ]);

        return response()->json([
            'message' => 'Status pesanan berhasil diperbarui.',
            'order' => $order,
        ]);
    }

    public function updatePaymentStatus(Request $request, Order $order)
    {
        abort_unless(
            $request->user()->role === 'admin',
            403,
            'Anda tidak memiliki akses admin.'
        );

        $data = $request->validate([
            'status' => [
                'required',
                'string',
                'in:pending,waiting_verification,paid,failed,expired',
            ],
        ]);

        if ($order->payment) {
            $order->payment->update([
                'status' => $data['status'],
                'paid_at' => $data['status'] === 'paid' ? now() : $order->payment->paid_at,
            ]);
        }

        $order->load([
            'user:id,name,email',
            'address',
            'items.product',
            'payment',
        ]);

        return response()->json([
            'message' => 'Status pembayaran berhasil diperbarui.',
            'order' => $order,
        ]);
    }
}