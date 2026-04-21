<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckoutItem extends Model
{
    
    protected $fillable = ['checkout_id', 'product_id', 'quantity', 'price', 'approved_qty',
    'status','unit'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function checkout()
    {
        return $this->belongsTo(Checkout::class, 'checkout_id');
    }
}
