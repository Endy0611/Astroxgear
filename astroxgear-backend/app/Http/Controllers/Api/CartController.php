<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = Cart::with('product')
            ->where('user_id', $request->user()->id)
            ->get();

        // Transform the cart items to match frontend expectations
        $items = $cart->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->product_name,  // Changed from 'name'
                'product_image' => $item->product->product_image, // Changed from 'image'
                'product_slug' => $item->product->product_slug,   // Changed from 'slug'
                'price' => $item->price,
                'quantity' => $item->quantity,
                'stock_quantity' => $item->product->stock_quantity,
            ];
        });

        $total = $cart->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        return response()->json([
            'items' => $items,
            'total' => $total,
        ]);
    }

    public function count(Request $request)
    {
        $count = Cart::where('user_id', $request->user()->id)
            ->sum('quantity');

        return response()->json([
            'count' => $count
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:tblproduct,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        // Check stock
        if ($product->stock_quantity < $request->quantity) {
            return response()->json([
                'message' => 'Insufficient stock'
            ], 400);
        }

        $cartItem = Cart::where('user_id', $request->user()->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($cartItem) {
            // Update existing cart item
            $newQuantity = $cartItem->quantity + $request->quantity;
            
            // Check total quantity against stock
            if ($product->stock_quantity < $newQuantity) {
                return response()->json([
                    'message' => 'Insufficient stock. You already have ' . $cartItem->quantity . ' in cart.'
                ], 400);
            }
            
            $cartItem->quantity = $newQuantity;
            $cartItem->save();
        } else {
            $cartItem = Cart::create([
                'user_id' => $request->user()->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'price' => $product->sale_price ?? $product->price,
            ]);
        }

        return response()->json([
            'message' => 'Product added to cart',
            'cart_item' => $cartItem->load('product'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cartItem = Cart::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $product = $cartItem->product;

        if ($product->stock_quantity < $request->quantity) {
            return response()->json([
                'message' => 'Insufficient stock'
            ], 400);
        }

        $cartItem->update([
            'quantity' => $request->quantity,
        ]);

        return response()->json([
            'message' => 'Cart updated',
            'cart_item' => $cartItem->load('product'),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $cartItem = Cart::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $cartItem->delete();

        return response()->json([
            'message' => 'Item removed from cart'
        ]);
    }

    public function clear(Request $request)
    {
        Cart::where('user_id', $request->user()->id)->delete();

        return response()->json([
            'message' => 'Cart cleared'
        ]);
    }
}