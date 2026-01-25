<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $presentation_id
 * @property int $answer_id
 * @property float|int|null $points
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Presentation|null $presentation
 * @property-read \App\Models\Answer|null $answer
 */
class Attempt extends Model
{
    /** @use HasFactory<\Database\Factories\AttemptFactory> */
    use SoftDeletes, HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<Presentation, static>
     */
    public function presentation(): BelongsTo
    {
        /** @var BelongsTo<Presentation, static> $relation */
        $relation = $this->belongsTo(Presentation::class);
        return $relation;
    }

    /**
     * @return BelongsTo<Answer, static>
     */
    public function answer(): BelongsTo
    {
        /** @var BelongsTo<Answer, static> $relation */
        $relation = $this->belongsTo(Answer::class);
        return $relation;
    }
}
