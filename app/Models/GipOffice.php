<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GipOffice extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code'];

    public function gipEmployees()
    {
        return $this->hasMany(GipEmployee::class, 'office_id');
    }
}