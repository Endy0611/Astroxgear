// BrandController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::with('category')
            ->where('is_active', true)
            ->orderBy('brand_name')
            ->get();

        return response()->json($brands);
    }

    public function show($id)
    {
        $brand = Brand::with(['products', 'category'])
            ->findOrFail($id);

        return response()->json($brand);
    }

    public function store(Request $request)
    {
        $request->validate([
            'brand_name' => 'required|string|max:255',
            'brand_slug' => 'required|string|max:255|unique:tblbrand,brand_slug',
            'brand_description' => 'nullable|string',
            'brand_logo' => 'nullable|string',
            'category_id' => 'required|exists:tblcategory,id',
            'is_active' => 'boolean',
        ]);

        $brand = Brand::create([
            'brand_name' => $request->brand_name,
            'brand_slug' => $request->brand_slug,
            'brand_description' => $request->brand_description,
            'brand_logo' => $request->brand_logo,
            'category_id' => $request->category_id,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'message' => 'Brand created successfully',
            'brand' => $brand->load('category')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);

        $request->validate([
            'brand_name' => 'required|string|max:255',
            'brand_slug' => 'required|string|max:255|unique:tblbrand,brand_slug,' . $id,
            'brand_description' => 'nullable|string',
            'brand_logo' => 'nullable|string',
            'category_id' => 'required|exists:tblcategory,id',
            'is_active' => 'boolean',
        ]);

        $brand->update([
            'brand_name' => $request->brand_name,
            'brand_slug' => $request->brand_slug,
            'brand_description' => $request->brand_description,
            'brand_logo' => $request->brand_logo,
            'category_id' => $request->category_id,
            'is_active' => $request->is_active ?? $brand->is_active,
        ]);

        return response()->json([
            'message' => 'Brand updated successfully',
            'brand' => $brand->load('category')
        ]);
    }

    public function destroy($id)
    {
        $brand = Brand::findOrFail($id);

        // Check if brand has products
        if ($brand->products()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete brand with existing products'
            ], 400);
        }

        $brand->delete();

        return response()->json([
            'message' => 'Brand deleted successfully'
        ]);
    }
}