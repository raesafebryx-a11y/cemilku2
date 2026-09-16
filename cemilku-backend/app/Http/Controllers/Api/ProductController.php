<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    private function deleteProductImage(?string $imagePath): void
    {
        if (empty($imagePath)) {
            return;
        }

        $normalizedPath = preg_replace('#^/?storage/#i', '', $imagePath);
        $normalizedPath = preg_replace('#^/?public/#i', '', $normalizedPath);
        $normalizedPath = ltrim(str_replace('\\', '/', $normalizedPath), '/');

        if ($normalizedPath === '') {
            return;
        }

        if (Storage::disk('public')->exists($normalizedPath)) {
            Storage::disk('public')->delete($normalizedPath);
        }
    }

    public function index(Request $request)
    {
        $query = Product::query()
            ->with('category')
            ->when(
                ! $request->filled('search'),
                fn ($query) => $query->where('is_active', true)
            );

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return response()->json(
            $query->latest()->paginate(12)
        );
    }

    public function show(Product $product)
    {
        $product->load('category');

        return response()->json($product);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request
                ->file('image')
                ->store('products', 'public');
        }

        $data['is_active'] = $request->boolean('is_active', true);

        $product = Product::create($data);

        $product->load('category');

        return response()->json([
            'message' => 'Produk berhasil ditambahkan.',
            'data' => $product,
        ], 201);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'unique:products,slug,' . $product->id,
            ],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        if ($request->hasFile('image')) {
            $this->deleteProductImage($product->image);

            $data['image'] = $request
                ->file('image')
                ->store('products', 'public');
        }

        $data['is_active'] = $request->boolean('is_active', $product->is_active);

        $product->update($data);

        $product->load('category');

        return response()->json([
            'message' => 'Produk berhasil diperbarui.',
            'data' => $product,
        ]);
    }

    public function destroy(Product $product)
    {
        $this->deleteProductImage($product->image);

        $product->delete();

        return response()->json([
            'message' => 'Produk berhasil dihapus.',
        ]);
    }
}
