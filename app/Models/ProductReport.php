<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'month',
        'year',
        'price',
        'starting_qty',
        'added_qty',
        'released_qty',
        'remaining_qty',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeAnnual($query, $productId, $year)
    {
        return $query->where('product_id', $productId)
            ->where('year', $year)
            ->orderBy('month');
    }

    public static function annualSummary($productId, $year)
    {
        $reports = self::annual($productId, $year)->get();

        return [
            'opening_stock' => $reports->first()->starting_qty ?? 0,
            'closing_stock' => $reports->last()->remaining_qty ?? 0,
            'total_added'   => $reports->sum('added_qty'),
            'total_released'=> $reports->sum('released_qty'),
            'net_change'    => $reports->sum('added_qty') - $reports->sum('released_qty'),
            'avg_price'     => round($reports->avg('price'), 2),
        ];
    }

    // Accessor for total cost
    public function getTotalCostAttribute()
    {
        return $this->remaining_qty * $this->price;
    }
}