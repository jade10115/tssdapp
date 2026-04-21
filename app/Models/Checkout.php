<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Checkout extends Model
{
    protected $fillable = [
        'user_id',
        'approved_by_id',
        'issued_by_id',
        'total_price',
        'status'
    ];

    /** Requester Profile */
    public function userProfile()
    {
        return $this->belongsTo(UserProfile::class, 'user_id', 'user_id');
    }

    /** Approved By */
    public function approvedBy()
    {
        return $this->belongsTo(UserProfile::class, 'approved_by_id', 'user_id');
    }

    /** Issued By */
    public function issuedBy()
    {
        return $this->belongsTo(UserProfile::class, 'issued_by_id', 'user_id');
    }

    /** Checkout Items */
    public function items()
    {
        return $this->hasMany(CheckoutItem::class, 'checkout_id');
    }
}
