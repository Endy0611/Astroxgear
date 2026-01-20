<?php
// CategoryController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json($categories);
    }

    public function show($id)
    {
        $category = Category::with(['children', 'products', 'brands'])
            ->findOrFail($id);

        return response()->json($category);
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_name' => 'required|string|max:255',
            'category_slug' => 'required|string|max:255|unique:tblcategory,category_slug',
            'category_description' => 'nullable|string',
            'parent_id' => 'nullable|exists:tblcategory,id',
            'category_image' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $category = Category::create([
            'category_name' => $request->category_name,
            'category_slug' => $request->category_slug,
            'category_description' => $request->category_description,
            'parent_id' => $request->parent_id,
            'category_image' => $request->category_image,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'category_name' => 'required|string|max:255',
            'category_slug' => 'required|string|max:255|unique:tblcategory,category_slug,' . $id,
            'category_description' => 'nullable|string',
            'parent_id' => 'nullable|exists:tblcategory,id',
            'category_image' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $category->update([
            'category_name' => $request->category_name,
            'category_slug' => $request->category_slug,
            'category_description' => $request->category_description,
            'parent_id' => $request->parent_id,
            'category_image' => $request->category_image,
            'sort_order' => $request->sort_order ?? $category->sort_order,
            'is_active' => $request->is_active ?? $category->is_active,
        ]);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category
        ]);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Check if category has products
        if ($category->products()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with existing products'
            ], 400);
        }

        // Check if category has brands
        if ($category->brands()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with existing brands'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }
}