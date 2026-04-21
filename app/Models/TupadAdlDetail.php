<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TupadAdlDetail extends Model
{
    use HasFactory;

    protected $table = 'tupad_adl_details';

    protected $fillable = [
        'division_id',
        'adl_master_id',
        'adl',
        'sponsor',
        'total_lgu',
        'amount',
        'balance',
        'percentage',
        'status',
    ];

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function master()
    {
        return $this->belongsTo(TupadAdlMaster::class, 'adl_master_id');
    }

    public function breakdowns()
{
    // ✅ correct FK: tupad_adl_breakdowns.adl_detail_id -> tupad_adl_details.id
    return $this->hasMany(TupadAdlBreakdown::class, 'adl_detail_id', 'id');
}
}
