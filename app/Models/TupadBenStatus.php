<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TupadBenStatus extends Model
{
    protected $table = 'tupad_bens_status';

    protected $fillable = [
        'tupad_adl_breakdown_id',
        'first_name',
        'middle_name',
        'last_name',
        'ext_name',
        'full_name',
        'is_pwd',
        'is_four_ps',
        'is_senior',
        'age',
        'sex_raw',
    ];

    protected $casts = [
        'is_pwd' => 'boolean',
        'is_four_ps' => 'boolean',
        'is_senior' => 'boolean',
        'age' => 'integer',
    ];
}
