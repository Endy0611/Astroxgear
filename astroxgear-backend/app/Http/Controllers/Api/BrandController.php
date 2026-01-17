<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::where('is_active', true)
            ->orderBy('brand_name')
            ->get();

        return response()->json($brands);
    }

    public function show($id)
    {
        $brand = Brand::with('products')
            ->findOrFail($id);

        return response()->json($brand);
    }
}