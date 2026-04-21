<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TupadAdlMaster extends Model
{
    use HasFactory;

    protected $table = 'tupad_adl_masters';

    protected $fillable = [
        'adl',
        'sponsor',
        'total_amount',
        'balance',
        'status',
    ];

    public function details()
    {
        return $this->hasMany(TupadAdlDetail::class, 'adl_master_id');
    }
     protected $casts = [
        'total_amount' => 'decimal:2',
        'balance'      => 'decimal:2',
    ];
}
