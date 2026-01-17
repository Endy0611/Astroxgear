<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $table = 'tblbrand';

    protected $fillable = [
        'brand_name',
        'brand_slug',
        'brand_description',
        'brand_logo',
        'brand_website',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'brand_id');
    }
}