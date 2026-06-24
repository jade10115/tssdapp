<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentTracking extends Model
{
    protected $table = 'document_tracking';

    protected $fillable = [
        'document_id', 'from_user_id', 'to_user_id', 'to_division',
        'status', 'received_at', 'remarks'
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function toUser()
{
    return $this->belongsTo(UserProfile::class, 'to_user_id', 'user_id');
}

public function fromUser()
{
    return $this->belongsTo(UserProfile::class, 'from_user_id', 'user_id');
}
}