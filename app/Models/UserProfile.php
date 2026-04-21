<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $table = 'tbl_user_profile';

    protected $fillable = [
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'division',
        'position_id',
        'phone',
        'address',
        'profile_image',
    ];

    protected $appends = ['full_name'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function getFullNameAttribute()
    {
        $mid = $this->middle_name ? ($this->middle_name . ' ') : '';
        $suf = $this->suffix ? (' ' . $this->suffix) : '';
        return trim($this->first_name . ' ' . $mid . $this->last_name . $suf);
    }
}
