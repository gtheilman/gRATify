<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $assessment_id
 * @property string|null $user_id
 * @property string|null $group_label
 * @property float|int|null $score
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Attempt> $attempts
 * @property-read \App\Models\Assessment|null $assessment
 */
class Presentation extends Model
{
    /** @use HasFactory<\Database\Factories\PresentationFactory> */
    use SoftDeletes, HasFactory;

    protected $guarded = [];

    /**
     * @return HasMany<Attempt, static>
     */
    public function attempts(): HasMany
    {
        /** @var HasMany<Attempt, static> $relation */
        $relation = $this->hasMany(Attempt::class);
        return $relation;
    }

    /**
     * @return BelongsTo<Assessment, static>
     */
    public function assessment(): BelongsTo
    {
        /** @var BelongsTo<Assessment, static> $relation */
        $relation = $this->belongsTo(Assessment::class);
        return $relation;
    }
}
