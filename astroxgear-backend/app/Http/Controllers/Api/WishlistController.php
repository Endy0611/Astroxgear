<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $wishlist = Wishlist::where('user_id', $request->user()->id)
            ->with('product.category', 'product.brand')
            ->get();

        return response()->json($wishlist);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:tblproduct,id',
        ]);

        // Check if already in wishlist
        $exists = Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $request->product_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Product already in wishlist'
            ], 400);
        }

        $wishlistItem = Wishlist::create([
            'user_id' => $request->user()->id,
            'product_id' => $request->product_id,
        ]);

        return response()->json([
            'message' => 'Product added to wishlist',
            'wishlist_item' => $wishlistItem->load('product'),
        ], 201);
    }

    public function destroy(Request $request, $id)
    {
        $wishlistItem = Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $id)
            ->first();

        if (!$wishlistItem) {
            return response()->json([
                'message' => 'Item not found in wishlist'
            ], 404);
        }

        $wishlistItem->delete();

        return response()->json([
            'message' => 'Item removed from wishlist'
        ]);
    }
}