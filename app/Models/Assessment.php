<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property bool $active
 * @property string|null $title
 * @property string|null $course
 * @property string|null $memo
 * @property string|null $short_url
 * @property string|null $bitly_error
 * @property string|null $password
 * @property \Illuminate\Support\Carbon|null $scheduled_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Question> $questions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Presentation> $presentations
 * @property-read \App\Models\User|null $user
 */
class Assessment extends Model
{
    /** @use HasFactory<\Database\Factories\AssessmentFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return HasMany<Question, static>
     */
    public function questions(): HasMany
    {
        /** @var HasMany<Question, static> $relation */
        $relation = $this->hasMany(Question::class)->orderBy('sequence');
        return $relation;
    }

    /**
     * @return HasMany<Presentation, static>
     */
    public function presentations(): HasMany
    {
        /** @var HasMany<Presentation, static> $relation */
        $relation = $this->hasMany(Presentation::class);
        return $relation;
    }

    /**
     * @return BelongsTo<User, static>
     */
    public function user(): BelongsTo
    {
        /** @var BelongsTo<User, static> $relation */
        $relation = $this->belongsTo(User::class);
        return $relation;
    }
}
