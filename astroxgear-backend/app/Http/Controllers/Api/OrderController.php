<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with('items.product')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($orders);
    }

    public function show(Request $request, $id)
    {
        $order = Order::where('user_id', $request->user()->id)
            ->with('items.product')
            ->findOrFail($id);

        return response()->json($order);
    }

    public function store(Request $request)
    {
        $request->validate([
            'shipping_name' => 'required|string',
            'shipping_email' => 'required|email',
            'shipping_phone' => 'required|string',
            'shipping_address' => 'required|string',
            'shipping_city' => 'required|string',
            'shipping_state' => 'required|string',
            'shipping_zip' => 'required|string',
            'shipping_country' => 'required|string',
            'payment_method' => 'required|string',
        ]);

        // Get user's cart
        $cartItems = Cart::where('user_id', $request->user()->id)
            ->with('product')
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'message' => 'Your cart is empty'
            ], 400);
        }

        // Calculate totals
        $subtotal = 0;
        foreach ($cartItems as $item) {
            $subtotal += $item->price * $item->quantity;
        }

        $tax = $subtotal * 0.1; // 10% tax
        $shipping_cost = 10.00; // Flat shipping
        $total = $subtotal + $tax + $shipping_cost;

        DB::beginTransaction();

        try {
            // Create order
            $order = Order::create([
                'user_id' => $request->user()->id,
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping_cost' => $shipping_cost,
                'discount' => 0,
                'total' => $total,
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $request->payment_method,
                'shipping_name' => $request->shipping_name,
                'shipping_email' => $request->shipping_email,
                'shipping_phone' => $request->shipping_phone,
                'shipping_address' => $request->shipping_address,
                'shipping_city' => $request->shipping_city,
                'shipping_state' => $request->shipping_state,
                'shipping_zip' => $request->shipping_zip,
                'shipping_country' => $request->shipping_country,
                'billing_name' => $request->billing_name ?? $request->shipping_name,
                'billing_address' => $request->billing_address ?? $request->shipping_address,
                'billing_city' => $request->billing_city ?? $request->shipping_city,
                'billing_state' => $request->billing_state ?? $request->shipping_state,
                'billing_zip' => $request->billing_zip ?? $request->shipping_zip,
                'billing_country' => $request->billing_country ?? $request->shipping_country,
                'order_notes' => $request->order_notes,
            ]);

            // Create order items
            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->product_name,
                    'product_sku' => $item->product->sku,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->price * $item->quantity,
                ]);

                // Update product stock
                $product = Product::find($item->product_id);
                $product->decrement('stock_quantity', $item->quantity);
            }

            // Clear cart
            Cart::where('user_id', $request->user()->id)->delete();

            DB::commit();

            return response()->json([
                'message' => 'Order placed successfully',
                'order' => $order->load('items'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}