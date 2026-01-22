<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        // Order core
        'order_number',
        'user_id',
        'subtotal',
        'tax',
        'shipping_cost',
        'discount',
        'total',
        'status',

        // Payment
        'payment_status',
        'payment_method',
        'transaction_id',

        // KHQR / Bakong
        'qr_code',
        'qr_md5',
        'qr_expiration',
        'bakong_hash',
        'from_account_id',
        'to_account_id',
        'description',
        'paid',
        'paid_at',

        // Shipping
        'shipping_name',
        'shipping_email',
        'shipping_phone',
        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_zip',
        'shipping_country',

        // Billing
        'billing_name',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_zip',
        'billing_country',

        // Other
        'order_notes',
        'coupon_id',
        'delivered_at',
    ];

    protected $casts = [
        // Prices
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',

        // Dates
        'delivered_at' => 'datetime',
        'paid_at' => 'datetime',

        // KHQR
        'qr_expiration' => 'integer',
        'paid' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}