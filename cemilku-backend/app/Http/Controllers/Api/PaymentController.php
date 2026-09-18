<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * =========================================================
     * CREATE SNAP TOKEN
     * =========================================================
     */
    public function createSnapToken(Request $request, Order $order)
    {
        $user = $request->user();

        abort_unless(
            $order->user_id === $user->id
            || $user->role === 'admin'
            || $user->is_admin,
            403,
            'Anda tidak memiliki akses ke pesanan ini.'
        );

        $order->load([
            'user',
            'items',
            'payment',
            'address',
        ]);

        if (!$order->payment) {
            return response()->json([
                'message' =>
                    'Data pembayaran pesanan tidak ditemukan.'
            ], 404);
        }

        if ($order->payment->status === 'paid') {
            return response()->json([
                'message' =>
                    'Pesanan ini sudah lunas.'
            ], 400);
        }

        /*
         * =====================================================
         * MIDTRANS CONFIG
         * =====================================================
         */
        $serverKey = trim(
            (string) config('midtrans.server_key')
        );

        $clientKey = trim(
            (string) config('midtrans.client_key')
        );

        $snapUrl = trim(
            (string) config('midtrans.snap_url')
        );

        $isProduction =
            (bool) config(
                'midtrans.is_production',
                false
            );

        if (
            empty($serverKey)
            || empty($clientKey)
            || empty($snapUrl)
        ) {
            Log::error(
                'Konfigurasi Midtrans tidak lengkap.',
                [
                    'server_key_exists' =>
                        !empty($serverKey),

                    'client_key_exists' =>
                        !empty($clientKey),

                    'snap_url' =>
                        $snapUrl,

                    'is_production' =>
                        $isProduction,
                ]
            );

            return response()->json([
                'message' =>
                    'Konfigurasi Midtrans belum lengkap.'
            ], 500);
        }

        /*
         * =====================================================
         * PAKSA URL SESUAI ENVIRONMENT
         * =====================================================
         */
        if ($isProduction) {
            $snapUrl =
                'https://app.midtrans.com/snap/v1/transactions';
        } else {
            $snapUrl =
                'https://app.sandbox.midtrans.com/snap/v1/transactions';
        }

        /*
         * =====================================================
         * MIDTRANS ORDER ID
         * =====================================================
         */
        $midtransOrderId =
            $order->order_number
            . '-'
            . time();

        /*
         * =====================================================
         * ITEM DETAILS
         * =====================================================
         */
        $itemDetails = [];

        foreach ($order->items as $item) {
            $itemDetails[] = [
                'id' =>
                    (string) $item->product_id,

                'price' =>
                    (int) round($item->price),

                'quantity' =>
                    (int) $item->quantity,

                'name' =>
                    mb_substr(
                        $item->product_name
                            ?? 'Cemilan',
                        0,
                        45
                    ),
            ];
        }

        /*
         * =====================================================
         * ONGKIR
         * =====================================================
         */
        if (
            (float) $order->shipping_cost > 0
        ) {
            $itemDetails[] = [
                'id' =>
                    'ONGKIR',

                'price' =>
                    (int) round(
                        $order->shipping_cost
                    ),

                'quantity' =>
                    1,

                'name' =>
                    'Ongkos Kirim',
            ];
        }

        /*
         * =====================================================
         * DATA TRANSAKSI
         * =====================================================
         */
        $params = [
            'transaction_details' => [
                'order_id' =>
                    $midtransOrderId,

                'gross_amount' =>
                    (int) round(
                        $order->total
                    ),
            ],

            'customer_details' => [
                'first_name' =>
                    $order->user->name
                    ?? 'Pelanggan',

                'email' =>
                    $order->user->email
                    ?? 'pelanggan@cemilku.test',

                'phone' =>
                    $order->address?->phone
                    ?? '',
            ],

            'item_details' =>
                $itemDetails,
        ];

        /*
         * =====================================================
         * BUAT AUTHORIZATION HEADER MANUAL
         * =====================================================
         *
         * Midtrans:
         *
         * Base64(ServerKey:)
         */
        $authorization =
            'Basic '
            . base64_encode(
                $serverKey . ':'
            );

        try {
            /*
             * =================================================
             * REQUEST SNAP MIDTRANS
             * =================================================
             */
            $response = Http::withHeaders([
                'Accept' =>
                    'application/json',

                'Content-Type' =>
                    'application/json',

                'Authorization' =>
                    $authorization,
            ])
                ->timeout(20)
                ->post(
                    $snapUrl,
                    $params
                );

            /*
             * =================================================
             * LOG AMAN
             * =================================================
             *
             * Jangan pernah log Server Key.
             */
            Log::info(
                'Midtrans Snap response.',
                [
                    'order_number' =>
                        $order->order_number,

                    'midtrans_order_id' =>
                        $midtransOrderId,

                    'http_status' =>
                        $response->status(),

                    'snap_url' =>
                        $snapUrl,

                    'server_key_prefix' =>
                        substr(
                            $serverKey,
                            0,
                            14
                        ),

                    'server_key_length' =>
                        strlen($serverKey),
                ]
            );

            /*
             * =================================================
             * BERHASIL
             * =================================================
             */
            if (
                $response->successful()
                && $response->json('token')
            ) {
                $token =
                    $response->json('token');

                $redirectUrl =
                    $response->json(
                        'redirect_url'
                    );

                /*
                 * Payment method Midtrans.
                 *
                 * Status tetap pending sampai
                 * webhook menyatakan berhasil.
                 */
                $order->payment->update([
                    'method' =>
                        'midtrans',
                ]);

                return response()->json([
                    'snap_token' =>
                        $token,

                    'redirect_url' =>
                        $redirectUrl,

                    'client_key' =>
                        $clientKey,

                    'is_production' =>
                        $isProduction,

                    'order_id' =>
                        $midtransOrderId,
                ], 200);
            }

            /*
             * =================================================
             * GAGAL
             * =================================================
             */
            Log::error(
                'Midtrans Snap gagal.',
                [
                    'order_number' =>
                        $order->order_number,

                    'midtrans_order_id' =>
                        $midtransOrderId,

                    'http_status' =>
                        $response->status(),

                    'response' =>
                        $response->json(),
                ]
            );

            return response()->json([
                'message' =>
                    'Gagal membuat transaksi Midtrans.',

                'error' =>
                    $response->json(),

                'http_status' =>
                    $response->status(),
            ], 502);

        } catch (\Throwable $e) {

            Log::error(
                'Midtrans Snap Exception.',
                [
                    'order_number' =>
                        $order->order_number,

                    'message' =>
                        $e->getMessage(),
                ]
            );

            return response()->json([
                'message' =>
                    'Terjadi kesalahan saat menghubungi Midtrans.',
            ], 500);
        }
    }

    /**
     * =========================================================
     * MIDTRANS WEBHOOK
     * =========================================================
     */
    public function handleNotification(Request $request)
    {
        $payload = $request->all();

        Log::info(
            'Midtrans Notification received',
            $payload
        );

        $orderIdField =
            $payload['order_id']
            ?? null;

        if (!$orderIdField) {
            return response()->json([
                'message' =>
                    'order_id tidak ditemukan.'
            ], 400);
        }

        $transactionStatus =
            $payload['transaction_status']
            ?? '';

        $fraudStatus =
            $payload['fraud_status']
            ?? '';

        $statusCode =
            $payload['status_code']
            ?? '';

        $grossAmount =
            $payload['gross_amount']
            ?? '';

        $signatureKey =
            $payload['signature_key']
            ?? '';

        /*
         * =====================================================
         * SERVER KEY
         * =====================================================
         */
        $serverKey = trim(
            (string) config(
                'midtrans.server_key'
            )
        );

        if (empty($serverKey)) {
            return response()->json([
                'message' =>
                    'Konfigurasi Midtrans tidak tersedia.'
            ], 500);
        }

        /*
         * =====================================================
         * SIGNATURE
         * =====================================================
         */
        $expectedSignature = hash(
            'sha512',
            $orderIdField
            . $statusCode
            . $grossAmount
            . $serverKey
        );

        if (
            empty($signatureKey)
            || !hash_equals(
                $expectedSignature,
                $signatureKey
            )
        ) {
            Log::warning(
                'Signature Midtrans tidak valid.',
                [
                    'order_id' =>
                        $orderIdField,
                ]
            );

            return response()->json([
                'message' =>
                    'Signature tidak valid.'
            ], 403);
        }

        /*
         * =====================================================
         * PARSE ORDER NUMBER
         * =====================================================
         *
         * CMK-XXXXX-1234567890
         * menjadi
         * CMK-XXXXX
         */
        $orderNumber = preg_replace(
            '/-\d+$/',
            '',
            $orderIdField
        );

        if (empty($orderNumber)) {
            return response()->json([
                'message' =>
                    'Format order_id tidak valid.'
            ], 400);
        }

        /*
         * =====================================================
         * CARI ORDER
         * =====================================================
         */
        $order = Order::with([
            'payment',
            'items.product',
        ])
            ->where(
                'order_number',
                $orderNumber
            )
            ->first();

        if (!$order) {
            Log::warning(
                'Order Midtrans tidak ditemukan.',
                [
                    'midtrans_order_id' =>
                        $orderIdField,

                    'parsed_order_number' =>
                        $orderNumber,
                ]
            );

            return response()->json([
                'message' =>
                    'Pesanan tidak ditemukan.'
            ], 404);
        }

        if (!$order->payment) {
            return response()->json([
                'message' =>
                    'Payment order tidak ditemukan.'
            ], 404);
        }

        /*
         * =====================================================
         * GROSS AMOUNT
         * =====================================================
         */
        if (
            abs(
                (float) $grossAmount
                - (float) $order->total
            ) > 0.01
        ) {
            Log::warning(
                'Gross amount Midtrans tidak sesuai.',
                [
                    'order_number' =>
                        $order->order_number,

                    'midtrans' =>
                        (float) $grossAmount,

                    'database' =>
                        (float) $order->total,
                ]
            );

            return response()->json([
                'message' =>
                    'Jumlah transaksi tidak sesuai.'
            ], 400);
        }

        /*
         * =====================================================
         * STATUS
         * =====================================================
         */
        $newStatus = 'pending';

        if (
            $transactionStatus === 'capture'
        ) {

            if (
                $fraudStatus === 'challenge'
            ) {
                $newStatus = 'pending';

            } elseif (
                $fraudStatus === 'accept'
                || empty($fraudStatus)
            ) {
                $newStatus = 'paid';

            } elseif (
                $fraudStatus === 'deny'
                || $fraudStatus === 'reject'
            ) {
                $newStatus = 'failed';
            }

        } elseif (
            $transactionStatus === 'settlement'
        ) {

            $newStatus = 'paid';

        } elseif (
            $transactionStatus === 'pending'
        ) {

            $newStatus = 'pending';

        } elseif (
            in_array(
                $transactionStatus,
                [
                    'deny',
                    'expire',
                    'cancel',
                    'failure',
                ],
                true
            )
        ) {

            $newStatus = 'failed';
        }

        /*
         * =====================================================
         * UPDATE DATABASE
         * =====================================================
         */
        DB::transaction(function () use (
            $order,
            $newStatus,
            $transactionStatus
        ) {

            $payment = Payment::where(
                'id',
                $order->payment->id
            )
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                throw new \RuntimeException(
                    'Payment order tidak ditemukan.'
                );
            }

            $oldStatus =
                $payment->status;

            /*
             * Jangan pernah menurunkan status
             * paid menjadi pending / failed.
             */
            $finalStatus =
                $newStatus;

            if (
                $oldStatus === 'paid'
                && $newStatus !== 'paid'
            ) {
                $finalStatus = 'paid';
            }

            $wasAlreadyPaid =
                $oldStatus === 'paid';

            /*
             * Update payment.
             */
            $payment->update([
                'method' =>
                    'midtrans',

                'status' =>
                    $finalStatus,

                'paid_at' =>
                    $finalStatus === 'paid'
                        ? (
                            $payment->paid_at
                            ?? now()
                        )
                        : null,
            ]);

            /*
             * =================================================
             * PAYMENT BERHASIL
             * =================================================
             */
            if (
                $finalStatus === 'paid'
                && !$wasAlreadyPaid
            ) {

                $order->load([
                    'items.product',
                ]);

                foreach (
                    $order->items
                    as $item
                ) {

                    $product = $item->product()
                        ->lockForUpdate()
                        ->first();

                    if (!$product) {
                        continue;
                    }

                    if (
                        $product->stock
                        < $item->quantity
                    ) {
                        throw new \RuntimeException(
                            "Stok {$product->name} tidak cukup."
                        );
                    }

                    $product->decrement(
                        'stock',
                        $item->quantity
                    );
                }

                $order->update([
                    'status' =>
                        'processing',
                ]);
            }

            /*
             * =================================================
             * PAYMENT GAGAL
             * =================================================
             */
            elseif (
                $finalStatus === 'failed'
                && !$wasAlreadyPaid
            ) {

                $order->update([
                    'status' =>
                        'cancelled',
                ]);
            }

            Log::info(
                'Status pembayaran Midtrans diperbarui.',
                [
                    'order_number' =>
                        $order->order_number,

                    'old_status' =>
                        $oldStatus,

                    'new_status' =>
                        $finalStatus,

                    'transaction_status' =>
                        $transactionStatus,
                ]
            );
        });

        return response()->json([
            'message' =>
                'Notifikasi Midtrans berhasil diproses.'
        ], 200);
    }

    /**
     * =========================================================
     * SIMULATE SUCCESS
     * =========================================================
     */
    public function simulateMidtransSuccess(
        Request $request,
        Order $order
    ) {
        $user = $request->user();

        abort_unless(
            $order->user_id === $user->id
            || $user->role === 'admin'
            || $user->is_admin,
            403,
            'Anda tidak memiliki akses ke pesanan ini.'
        );

        DB::transaction(function () use ($order) {

            $order->load([
                'items.product',
                'payment',
            ]);

            if (!$order->payment) {
                throw new \RuntimeException(
                    'Payment order tidak ditemukan.'
                );
            }

            if (
                $order->payment->status !== 'paid'
            ) {

                foreach (
                    $order->items
                    as $item
                ) {

                    $product = $item->product()
                        ->lockForUpdate()
                        ->first();

                    if (!$product) {
                        continue;
                    }

                    if (
                        $product->stock
                        < $item->quantity
                    ) {
                        throw new \RuntimeException(
                            "Stok {$product->name} tidak cukup."
                        );
                    }

                    $product->decrement(
                        'stock',
                        $item->quantity
                    );
                }

                $order->payment->update([
                    'method' =>
                        'midtrans',

                    'status' =>
                        'paid',

                    'paid_at' =>
                        now(),
                ]);
            }

            $order->update([
                'status' =>
                    'processing',
            ]);
        });

        return response()->json([
            'success' =>
                true,

            'message' =>
                'Pembayaran berhasil disimulasikan.',

            'order' =>
                $order->fresh([
                    'items.product',
                    'payment',
                    'address',
                    'user',
                ]),
        ]);
    }

    /**
     * =========================================================
     * UPLOAD BUKTI TRANSFER
     * =========================================================
     */
    public function uploadProof(
        Request $request,
        Payment $payment
    ) {
        abort_unless(
            $payment->order
            && $payment->order->user_id
                === $request->user()->id,
            403,
            'Anda tidak memiliki akses ke pembayaran ini.'
        );

        if (
            $payment->status === 'paid'
        ) {
            return response()->json([
                'message' =>
                    'Pembayaran ini sudah dikonfirmasi.'
            ], 400);
        }

        $request->validate([
            'proof_image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $path = $request
            ->file('proof_image')
            ->store(
                'payment-proofs',
                'public'
            );

        $payment->update([
            'proof_image' =>
                $path,

            'status' =>
                'waiting_verification',

            'paid_at' =>
                null,
        ]);

        return response()->json([
            'message' =>
                'Bukti pembayaran berhasil diupload.',

            'payment' =>
                $payment->fresh(),
        ], 200);
    }
}