    <?php

    use App\Http\Controllers\Api\AddressController;
    use App\Http\Controllers\Api\AdminContactController;
    use App\Http\Controllers\Api\AdminOrderController;
    use App\Http\Controllers\Api\AdminOrderItemController;
    use App\Http\Controllers\Api\AuthController;
    use App\Http\Controllers\Api\CartController;
    use App\Http\Controllers\Api\CategoryController;
    use App\Http\Controllers\Api\ContactController;
    use App\Http\Controllers\Api\GoogleAuthController;
    use App\Http\Controllers\Api\OrderController;
    use App\Http\Controllers\Api\PaymentController;
    use App\Http\Controllers\Api\ProductController;
    use App\Http\Controllers\Api\RajaOngkirController;
    use Illuminate\Support\Facades\Route;

    // =====================================================
    // PUBLIC ROUTES
    // =====================================================

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // =====================================================
    // MIDTRANS CALLBACK
    // =====================================================

    Route::post('/midtrans/notification', [PaymentController::class, 'handleNotification']);


    // =====================================================
    // GOOGLE AUTH
    // =====================================================
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect']);
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);

    // =====================================================
    // CATEGORIES (Public)
    // =====================================================

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);

    // =====================================================
    // PRODUCTS (Public)
    // =====================================================

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);

    // =====================================================
    // CONTACTS
    // =====================================================

    Route::apiResource('contacts', ContactController::class)
        ->only([
            'index',
            'store',
            'show',
            'update',
            'destroy',
        ]);

    // =====================================================
    // RAJAONGKIR SHIPPING (PUBLIC)
    // =====================================================

    Route::get('/provinces', [RajaOngkirController::class, 'provinces']);
    Route::get('/cities/{provinceId}', [RajaOngkirController::class, 'cities']);
    Route::post('/shipping-cost', [RajaOngkirController::class, 'cost']);

    // =====================================================
    // ROUTES WAJIB LOGIN (PROTECTED)
    // =====================================================

    Route::middleware('auth:sanctum')->group(function () {

        // =================================================
        // AUTH USER PROFILE
        // =================================================

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        
        // Alias /user agar cocok dengan request Axios / Pinia
        Route::get('/user', [AuthController::class, 'me']);

        // Route Update Profile & Update Password
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/profile/password', [AuthController::class, 'updatePassword']);

        // =================================================
        // ADMIN CONTACT MANAGEMENT
        // =================================================
        Route::get('/admin/contacts', [AdminContactController::class, 'index']);
        Route::get('/admin/contacts/{contact}', [AdminContactController::class, 'show']);
        Route::put('/admin/contacts/{contact}', [AdminContactController::class, 'update']);
        Route::delete('/admin/contacts/{contact}', [AdminContactController::class, 'destroy']);

        // =================================================
        // ADMIN ORDER ITEM MANAGEMENT
        // =================================================
        Route::get('/admin/order-items', [AdminOrderItemController::class, 'index']);
        Route::get('/admin/order-items/{orderItem}', [AdminOrderItemController::class, 'show']);
        Route::delete('/admin/order-items/{orderItem}', [AdminOrderItemController::class, 'destroy']);

        // =================================================
        // ADMIN ORDER MANAGEMENT
        // =================================================
        Route::get('/admin/orders', [AdminOrderController::class, 'index']);
        Route::get('/admin/orders/{order}', [AdminOrderController::class, 'show']);
        Route::put('/admin/orders/{order}/status', [AdminOrderController::class, 'updateStatus']);
        Route::put('/admin/orders/{order}/payment-status', [AdminOrderController::class, 'updatePaymentStatus']);

        // =================================================
        // CATEGORY MANAGEMENT
        // =================================================

        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        // =================================================
        // PRODUCT MANAGEMENT
        // =================================================

        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);

        // =================================================
        // CART
        // =================================================

        Route::get('/cart', [CartController::class, 'index']);
        Route::post('/cart/items', [CartController::class, 'addItem']);
        Route::put('/cart/items/{cartItem}', [CartController::class, 'updateItem']);
        Route::delete('/cart/items/{cartItem}', [CartController::class, 'removeItem']);

        // =================================================
        // ADDRESSES
        // =================================================

        Route::get('/addresses', [AddressController::class, 'index']);
        Route::post('/addresses', [AddressController::class, 'store']);

        // =================================================
        // ORDERS
        // =================================================

        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::post('/orders', [OrderController::class, 'store']);
        
        // =================================================
        // PAYMENTS
        // =================================================

        // Midtrans: request Snap Token
        Route::post('/orders/{order}/snap-token', [PaymentController::class, 'createSnapToken']);

        // Midtrans: simulasi pembayaran sukses (demo / sandbox)
        Route::post('/orders/{order}/pay-simulate', [PaymentController::class, 'simulateMidtransSuccess']);

        // Upload bukti transfer manual (fallback)
        Route::post('/payments/{payment}/proof', [PaymentController::class, 'uploadProof']);
    });