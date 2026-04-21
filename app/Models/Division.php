<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
use HasFactory;

protected $table = 'divisions';

    protected $fillable = ['division','color','status'];

public function adlDetails()
{
    return $this->hasMany(TupadAdlDetail::class, 'division_id');
}
}