<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table      = 'tblcategory';
    protected $primaryKey = 'id';

    protected $fillable = [
        'category_name',
        'category_slug',
        'category_description',
        'category_image',
        'parent_id',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(\App\Models\Product::class, 'category_id');
    }

    public function brands()
{
    return $this->hasMany(\App\Models\Brand::class, 'category_id');
}
}