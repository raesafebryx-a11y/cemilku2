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
            'items',
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
            'items',
            'payment',
        ]);

        return response()->json([
            'message' => 'Status pesanan berhasil diperbarui.',
            'order' => $order,
        ]);
    }
}
