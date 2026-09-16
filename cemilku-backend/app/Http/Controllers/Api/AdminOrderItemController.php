<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class AdminOrderItemController extends Controller
{
    /**
     * Menampilkan semua order item.
     */
    public function index(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Akses hanya untuk admin.'
            ], 403);
        }

        $items = OrderItem::with([
            'order.user',
            'product'
        ])
            ->latest()
            ->get();

        return response()->json($items);
    }

    /**
     * Menampilkan detail order item.
     */
    public function show(Request $request, OrderItem $orderItem)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Akses hanya untuk admin.'
            ], 403);
        }

        $orderItem->load([
            'order.user',
            'order.address',
            'product'
        ]);

        return response()->json($orderItem);
    }

    /**
     * Menghapus order item.
     */
    public function destroy(Request $request, OrderItem $orderItem)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Akses hanya untuk admin.'
            ], 403);
        }

        $orderItem->delete();

        return response()->json([
            'message' => 'Order item berhasil dihapus.'
        ]);
    }
}
