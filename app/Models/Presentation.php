<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Presentation extends Model
{
    use SoftDeletes, HasFactory;

    protected $guarded = [];

    public function attempts()
    {
        return $this->hasMany(Attempt::class);
    }

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }
}
