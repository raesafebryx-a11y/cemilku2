<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductDeleteCascadeTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_delete_removes_related_order_items(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
        ]);

        $address = Address::create([
            'user_id' => $user->id,
            'label' => 'Rumah',
            'recipient_name' => 'Test User',
            'phone' => '081234567890',
            'full_address' => 'Jl. Test No. 1',
            'city' => 'Bandung',
            'postal_code' => '40111',
        ]);

        $category = Category::create([
            'name' => 'Makanan',
            'slug' => 'makanan',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'Deskripsi',
            'price' => 10000,
            'stock' => 5,
            'image' => null,
            'is_active' => true,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'order_number' => 'ORD-TEST-001',
            'status' => 'pending',
            'subtotal' => 10000,
            'shipping_cost' => 0,
            'total' => 10000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
            'subtotal' => $product->price,
        ]);

        $product->delete();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('order_items', ['product_id' => $product->id]);
    }

    public function test_updating_product_replaces_previous_image_file(): void
    {
        Storage::fake('public');

        $category = Category::create([
            'name' => 'Makanan',
            'slug' => 'makanan',
        ]);

        $oldImagePath = 'products/old-image.jpg';
        Storage::disk('public')->put($oldImagePath, 'old-image-content');

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Produk Lama',
            'slug' => 'produk-lama',
            'description' => 'Deskripsi',
            'price' => 20000,
            'stock' => 10,
            'image' => $oldImagePath,
            'is_active' => true,
        ]);

        $newImage = UploadedFile::fake()->image('new-product.jpg', 800, 600);

        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/products/' . $product->id, [
                'category_id' => $category->id,
                'name' => 'Produk Baru',
                'slug' => 'produk-baru',
                'description' => 'Deskripsi baru',
                'price' => 25000,
                'stock' => 12,
                'is_active' => true,
                '_method' => 'PUT',
                'image' => $newImage,
            ]);

        $product->refresh();

        $this->assertNotEquals($oldImagePath, $product->image);
        $this->assertStringContainsString('products/', $product->image);
        $this->assertFalse(Storage::disk('public')->exists($oldImagePath));
        $this->assertTrue(Storage::disk('public')->exists($product->image));
    }
}
