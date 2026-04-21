<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TupadAdlBreakdown extends Model
{
    use HasFactory;

    protected $table = 'tupad_adl_breakdowns';

   protected $fillable = [
    'adl_detail_id',
    'lgu',
    'amount',
    'beneficiaries',
    'status',
    'osh_date',
    'payout_date',

    // NEW
    'four_ps',
    'seniors',
    'pwd',
    'female',
];


    protected $casts = [
    'amount'        => 'float',
    'beneficiaries' => 'integer',
    'osh_date'      => 'date:Y-m-d',
    'payout_date'   => 'date:Y-m-d',

    // NEW
    'four_ps'       => 'integer',
    'seniors'       => 'integer',
    'pwd'           => 'integer',
    'female'        => 'integer',
];

    public function adlDetail()
    {
        return $this->belongsTo(TupadAdlDetail::class, 'adl_detail_id');
    }

    public function beneficiaries()
{
    return $this->hasMany(
        \App\Models\TupadBenStatus::class,
        'tupad_adl_breakdown_id'
    );
}

}
