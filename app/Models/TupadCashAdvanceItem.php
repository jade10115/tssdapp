<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TupadCashAdvanceItem extends Model
{
    protected $table = 'tupad_cash_advance_items';

    protected $fillable = [
        'cash_advance_id',
        'adl_breakdown_id',
        'lgu',          // <-- added
        'beneficiaries',
        'amount',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'beneficiaries' => 'integer',
        'amount'        => 'decimal:2',
    ];

    public function cashAdvance()
    {
        return $this->belongsTo(TupadCashAdvance::class, 'cash_advance_id');
    }

    public function breakdown()
    {
        return $this->belongsTo(TupadAdlBreakdown::class, 'adl_breakdown_id');
    }
}