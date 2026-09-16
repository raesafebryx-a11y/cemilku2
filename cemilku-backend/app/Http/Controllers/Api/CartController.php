<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Menampilkan keranjang milik user yang sedang login.
     */
    public function index(Request $request)
    {
        $cart = $request->user()
            ->cart()
            ->firstOrCreate([]);

        $cart->load('items.product');

        return response()->json($cart);
    }

    /**
     * Menambahkan produk ke keranjang.
     */
    public function addItem(Request $request)
    {
        $data = $request->validate([
            'product_id' => [
                'required',
                'exists:products,id',
            ],
            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        $cart = $request->user()
            ->cart()
            ->firstOrCreate([]);

        $product = Product::findOrFail($data['product_id']);

        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        /*
         * Kalau produk sudah ada di keranjang,
         * quantity baru = quantity lama + quantity yang ditambahkan.
         */
        $newQuantity = $item
            ? $item->quantity + $data['quantity']
            : $data['quantity'];

        /*
         * Pastikan quantity tidak melebihi stok produk.
         */
        if ($newQuantity > $product->stock) {
            return response()->json([
                'message' => "Stok tidak cukup. Stok tersedia hanya {$product->stock} pcs.",
            ], 422);
        }

        if ($item) {
            $item->quantity = $newQuantity;
            $item->save();
        } else {
            $item = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $data['quantity'],
            ]);
        }

        return response()->json(
            $item->load('product'),
            201
        );
    }

    /**
     * Mengubah jumlah produk di keranjang.
     */
    public function updateItem(
        Request $request,
        CartItem $cartItem
    ) {
        $this->authorizeOwnership(
            $request,
            $cartItem
        );

        $data = $request->validate([
            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        $product = $cartItem->product;

        /*
         * Pastikan quantity baru tidak melebihi stok.
         */
        if ($data['quantity'] > $product->stock) {
            return response()->json([
                'message' => "Stok tidak cukup. Stok tersedia hanya {$product->stock} pcs.",
            ], 422);
        }

        $cartItem->update([
            'quantity' => $data['quantity'],
        ]);

        return response()->json(
            $cartItem->load('product')
        );
    }

    /**
     * Menghapus produk dari keranjang.
     */
    public function removeItem(
        Request $request,
        CartItem $cartItem
    ) {
        $this->authorizeOwnership(
            $request,
            $cartItem
        );

        $cartItem->delete();

        return response()->json([
            'message' => 'Item dihapus dari keranjang',
        ]);
    }

    /**
     * Memastikan item keranjang milik user yang sedang login.
     */
    private function authorizeOwnership(
        Request $request,
        CartItem $cartItem
    ): void {
        abort_unless(
            $cartItem->cart->user_id === $request->user()->id,
            403
        );
    }
}
