<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $question_id
 * @property string|null $answer_text
 * @property string|null $feedback
 * @property bool|null $correct
 * @property int|null $sequence
 * @property float|int|null $points
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Question|null $question
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Attempt> $attempts
 */
class Answer extends Model
{
    /** @use HasFactory<\Database\Factories\AnswerFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<Question, static>
     */
    public function question(): BelongsTo
    {
        /** @var BelongsTo<Question, static> $relation */
        $relation = $this->belongsTo(Question::class);
        return $relation;
    }

    /**
     * @return HasMany<Attempt, static>
     */
    public function attempts(): HasMany
    {
        /** @var HasMany<Attempt, static> $relation */
        $relation = $this->hasMany(Attempt::class);
        return $relation;
    }
}
