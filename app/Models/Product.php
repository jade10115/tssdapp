<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'tbl_products';

    protected $fillable = [
        'user_id',
        'product_name',
        'image',
        'price',
        'unit',
        'current_stock',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }



protected static function booted()
{
    static::saving(function ($product) {
        if ($product->current_stock <= 0) {
            $product->status = 'Not Available';
        } elseif ($product->current_stock < 5) {
            $product->status = 'Low Qty';
        } else {
            $product->status = 'Available';
        }
    });
}

public function reports()
{
    return $this->hasMany(ProductReport::class, 'product_id');
}




}
