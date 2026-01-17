<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'tblproduct';

    protected $fillable = [
        'product_name',
        'product_slug',
        'sku',
        'product_description',
        'short_description',
        'category_id',
        'brand_id',
        'price',
        'sale_price',
        'cost_price',
        'discount_percentage',
        'product_image',
        'product_gallery',
        'weight',
        'dimensions',
        'specifications',
        'stock_quantity',
        'stock_status',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'is_featured',
        'is_new',
        'is_active',
        'views',
        'average_rating',
        'total_reviews',
    ];

    protected $casts = [
        'product_gallery' => 'array',
        'specifications' => 'array',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class, 'product_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    // Accessors
    public function getFinalPriceAttribute()
    {
        return $this->sale_price ?? $this->price;
    }

    public function getIsOnSaleAttribute()
    {
        return !is_null($this->sale_price) && $this->sale_price < $this->price;
    }
}