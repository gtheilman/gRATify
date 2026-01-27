<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $presentation_id
 * @property int $question_id
 * @property string $body
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Presentation|null $presentation
 * @property-read \App\Models\Question|null $question
 */
class Appeal extends Model
{
    /** @use HasFactory<\Database\Factories\AppealFactory> */
    use HasFactory;

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
     * @return BelongsTo<Question, static>
     */
    public function question(): BelongsTo
    {
        /** @var BelongsTo<Question, static> $relation */
        $relation = $this->belongsTo(Question::class);
        return $relation;
    }
}
