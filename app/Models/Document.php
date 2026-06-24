<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Document extends Model
{
    protected $fillable = [
        'document_number', 'title', 'description', 'document_type_id',
        'submitted_by', 'origin_division', 'status'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($doc) {
            if (empty($doc->document_number)) {
                $doc->document_number = 'DOC-' . strtoupper(Str::random(8));
            }
        });
    }

    public function type()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function submitter()
    {
        return $this->belongsTo(UserProfile::class, 'submitted_by', 'user_id');
    }

    public function tracking()
    {
        return $this->hasMany(DocumentTracking::class)->orderBy('id');
    }

    // Get the latest pending / received tracking
    public function latestTracking()
    {
        return $this->hasOne(DocumentTracking::class)->latestOfMany();
    }
}