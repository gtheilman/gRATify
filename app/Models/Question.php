<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $assessment_id
 * @property string|null $title
 * @property string|null $stem
 * @property int|null $points_possible
 * @property int|null $sequence
 * @property float|int|null $score
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Answer> $answers
 * @property \Illuminate\Support\Collection<int, \App\Models\Attempt> $attempts
 * @property-read \App\Models\Assessment|null $assessment
 */
class Question extends Model
{
    /** @use HasFactory<\Database\Factories\QuestionFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return HasMany<Answer, static>
     */
    public function answers(): HasMany
    {
        /** @var HasMany<Answer, static> $relation */
        $relation = $this->hasMany(Answer::class)->orderBy('sequence');
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
