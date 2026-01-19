<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function presentations()
    {
        return $this->hasMany(Presentation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
