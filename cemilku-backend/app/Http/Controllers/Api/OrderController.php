<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Mengambil daftar order beserta relasi item produk, pembayaran, dan user
        if ($user->role === 'admin' || $user->is_admin) {
            $orders = Order::with(['items.product', 'payment', 'user', 'address'])->latest()->get();
        } else {
            $orders = $user->orders()->with(['items.product', 'payment', 'address'])->latest()->get();
        }

        return response()->json($orders);
    }

    public function show(Request $request, Order $order)
    {
        // Admin diperbolehkan melihat detail semua order
        if ($request->user()->role !== 'admin' && !$request->user()->is_admin) {
            abort_unless($order->user_id === $request->user()->id, 403, 'Akses ditolak');
        }

        $order->load(['items.product', 'payment', 'address', 'user']);

        return response()->json($order);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'address_id' => ['required', 'exists:addresses,id'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $user = $request->user();
        $cart = $user->cart()->with('items.product')->first();

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Keranjang masih kosong'], 422);
        }

        $order = DB::transaction(function () use ($user, $cart, $data) {
            $subtotal = $cart->items->sum(fn ($item) => $item->product->price * $item->quantity);
            $shippingCost = $data['shipping_cost'] ?? 0;

            $order = Order::create([
                'user_id' => $user->id,
                'address_id' => $data['address_id'],
                'order_number' => 'CMK-'.strtoupper(Str::random(8)),
                'status' => 'pending',
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'total' => $subtotal + $shippingCost,
            ]);

            foreach ($cart->items as $item) {
                if ($item->product->stock < $item->quantity) {
                    throw new \RuntimeException("Stok {$item->product->name} tidak cukup");
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product->id,
                    'product_name' => $item->product->name,
                    'price' => $item->product->price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->product->price * $item->quantity,
                ]);

                // $item->product->decrement('stock', $item->quantity);
            }

            $order->payment()->create([
                'method' => 'transfer',
                'status' => 'pending',
                'amount' => $order->total,
            ]);

            $cart->items()->delete();

            return $order;
        });

        return response()->json($order->load(['items.product', 'payment']), 201);
    }

    
}