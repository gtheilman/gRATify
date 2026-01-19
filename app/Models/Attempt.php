<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attempt extends Model
{
    use SoftDeletes, HasFactory;

    protected $guarded = [];

    public function presentation()
    {
        return $this->belongsTo(Presentation::class);
    }

    public function answer()
    {
        return $this->belongsTo(Answer::class);
    }
}
