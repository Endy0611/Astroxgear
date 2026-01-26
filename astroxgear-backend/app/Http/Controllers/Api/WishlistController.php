<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    /**
     * GET /wishlist
     * Get all wishlist items for logged-in user
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        $wishlist = Wishlist::where('user_id', $user->id)
            ->with([
                'product.category',
                'product.brand'
            ])
            ->get();

        return response()->json([
            'data' => $wishlist
        ], 200);
    }

    /**
     * POST /wishlist
     * Add product to wishlist
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        $request->validate([
            // 🔴 CHANGE TABLE NAME IF NEEDED
            'product_id' => 'required|exists:products,id',
        ]);

        $productId = $request->product_id;

        // Prevent duplicate
        $exists = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Product already in wishlist'
            ], 409);
        }

        $wishlistItem = Wishlist::create([
            'user_id' => $user->id,
            'product_id' => $productId,
        ]);

        return response()->json([
            'message' => 'Product added to wishlist',
            'data' => $wishlistItem->load([
                'product.category',
                'product.brand'
            ])
        ], 201);
    }

    /**
     * DELETE /wishlist/{product_id}
     * Remove product from wishlist
     */
    public function destroy(Request $request, $productId)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        $wishlistItem = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if (!$wishlistItem) {
            return response()->json([
                'message' => 'Product not found in wishlist'
            ], 404);
        }

        $wishlistItem->delete();

        return response()->json([
            'message' => 'Product removed from wishlist'
        ], 200);
    }
}