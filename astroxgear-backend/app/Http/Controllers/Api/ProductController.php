<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'brand'])
            ->where('is_active', true);

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by brand
        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        // Filter by price range
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                  ->orWhere('product_description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 12);
        $products = $query->paginate($perPage);

        return response()->json($products);
    }

    public function show($id)
    {
        $product = Product::with(['category', 'brand', 'reviews.user'])
            ->findOrFail($id);

        // Increment views
        $product->increment('views');

        return response()->json($product);
    }

    public function featured()
    {
        $products = Product::with(['category', 'brand'])
            ->where('is_featured', true)
            ->where('is_active', true)
            ->limit(8)
            ->get();

        return response()->json($products);
    }

    public function newArrivals()
    {
        $products = Product::with(['category', 'brand'])
            ->where('is_new', true)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        return response()->json($products);
    }

    public function onSale()
    {
        $products = Product::with(['category', 'brand'])
            ->whereNotNull('sale_price')
            ->where('is_active', true)
            ->orderBy('discount_percentage', 'desc')
            ->limit(8)
            ->get();

        return response()->json($products);
    }

    // ProductController.php - Add these methods to your existing ProductController
public function store(Request $request)
{
    $request->validate([
        'product_name' => 'required|string|max:255',
        'sku' => 'required|string|max:100|unique:tblproduct,sku',
        'product_description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'sale_price' => 'nullable|numeric|min:0',
        'category_id' => 'required|exists:tblcategory,id',
        'brand_id' => 'required|exists:tblbrand,id',
        'stock_quantity' => 'required|integer|min:0',
        'product_image' => 'nullable|string',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'is_active' => 'boolean',
    ]);

    // Calculate discount percentage if sale_price exists
    $discountPercentage = 0;
    if ($request->sale_price && $request->price > 0) {
        $discountPercentage = (($request->price - $request->sale_price) / $request->price) * 100;
    }

    $product = Product::create([
        'product_name' => $request->product_name,
        'sku' => $request->sku,
        'product_description' => $request->product_description,
        'price' => $request->price,
        'sale_price' => $request->sale_price,
        'discount_percentage' => round($discountPercentage, 2),
        'category_id' => $request->category_id,
        'brand_id' => $request->brand_id,
        'stock_quantity' => $request->stock_quantity,
        'product_image' => $request->product_image,
        'is_featured' => $request->is_featured ?? false,
        'is_new' => $request->is_new ?? false,
        'is_active' => $request->is_active ?? true,
    ]);

    return response()->json([
        'message' => 'Product created successfully',
        'product' => $product->load(['category', 'brand'])
    ], 201);
}

public function update(Request $request, $id)
{
    $product = Product::findOrFail($id);

    $request->validate([
        'product_name' => 'required|string|max:255',
        'sku' => 'required|string|max:100|unique:tblproduct,sku,' . $id,
        'product_description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'sale_price' => 'nullable|numeric|min:0',
        'category_id' => 'required|exists:tblcategory,id',
        'brand_id' => 'required|exists:tblbrand,id',
        'stock_quantity' => 'required|integer|min:0',
        'product_image' => 'nullable|string',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'is_active' => 'boolean',
    ]);

    // Calculate discount percentage
    $discountPercentage = 0;
    if ($request->sale_price && $request->price > 0) {
        $discountPercentage = (($request->price - $request->sale_price) / $request->price) * 100;
    }

    $product->update([
        'product_name' => $request->product_name,
        'sku' => $request->sku,
        'product_description' => $request->product_description,
        'price' => $request->price,
        'sale_price' => $request->sale_price,
        'discount_percentage' => round($discountPercentage, 2),
        'category_id' => $request->category_id,
        'brand_id' => $request->brand_id,
        'stock_quantity' => $request->stock_quantity,
        'product_image' => $request->product_image,
        'is_featured' => $request->is_featured ?? $product->is_featured,
        'is_new' => $request->is_new ?? $product->is_new,
        'is_active' => $request->is_active ?? $product->is_active,
    ]);

    return response()->json([
        'message' => 'Product updated successfully',
        'product' => $product->load(['category', 'brand'])
    ]);
}

public function destroy($id)
{
    $product = Product::findOrFail($id);

    // Check if product is in any orders
    if ($product->orderItems()->count() > 0) {
        return response()->json([
            'message' => 'Cannot delete product that has been ordered'
        ], 400);
    }

    $product->delete();

    return response()->json([
        'message' => 'Product deleted successfully'
    ]);
}
}