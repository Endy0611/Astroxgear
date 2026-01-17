<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use App\Models\Product;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:tblproduct,id',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'required|string',
        ]);

        // Check if user already reviewed this product
        $existingReview = ProductReview::where('user_id', $request->user()->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'message' => 'You have already reviewed this product'
            ], 400);
        }

        $review = ProductReview::create([
            'product_id' => $request->product_id,
            'user_id' => $request->user()->id,
            'rating' => $request->rating,
            'title' => $request->title,
            'comment' => $request->comment,
            'is_approved' => true, // Auto-approve or set to false for moderation
        ]);

        // Update product average rating
        $this->updateProductRating($request->product_id);

        return response()->json([
            'message' => 'Review submitted successfully',
            'review' => $review->load('user'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'required|string',
        ]);

        $review = ProductReview::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $review->update([
            'rating' => $request->rating,
            'title' => $request->title,
            'comment' => $request->comment,
        ]);

        // Update product average rating
        $this->updateProductRating($review->product_id);

        return response()->json([
            'message' => 'Review updated successfully',
            'review' => $review,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $review = ProductReview::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $productId = $review->product_id;
        $review->delete();

        // Update product average rating
        $this->updateProductRating($productId);

        return response()->json([
            'message' => 'Review deleted successfully'
        ]);
    }

    private function updateProductRating($productId)
    {
        $product = Product::find($productId);
        
        $avgRating = ProductReview::where('product_id', $productId)
            ->where('is_approved', true)
            ->avg('rating');
        
        $totalReviews = ProductReview::where('product_id', $productId)
            ->where('is_approved', true)
            ->count();

        $product->update([
            'average_rating' => round($avgRating, 2) ?? 0,
            'total_reviews' => $totalReviews,
        ]);
    }
}