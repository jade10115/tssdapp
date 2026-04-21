<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TupadCashAdvance extends Model
{
    protected $table = 'tupad_cash_advances';

    protected $fillable = [
        'employee_id',
        'adl_id',
        'total_amount',
        'total_beneficiaries',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'total_amount'        => 'decimal:2',
        'total_beneficiaries' => 'integer',
    ];

    public function items()
    {
        return $this->hasMany(TupadCashAdvanceItem::class, 'cash_advance_id');
    }

    public function adlMaster()
    {
        return $this->belongsTo(TupadAdlMaster::class, 'adl_id');
    }
}