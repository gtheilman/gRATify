<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Summary assessment payload for list views; includes password for editor usage.
 *
 * @mixin \App\Models\Assessment
 * @property int|null $presentations_count
 */
class AssessmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'owner_username' => $this->user?->username,
            'title' => $this->title,
            'course' => $this->course,
            'memo' => $this->memo,
            'active' => (bool) $this->active,
            'short_url' => $this->short_url,
            // Exposed so the editor UI can show/copy client links without extra fetches.
            'password' => $this->password,
            'scheduled_at' => $this->scheduled_at,
            'presentations_count' => $this->presentations_count ?? $this->presentations()->count(),
            'created_at' => $this->created_at,
        ];
    }
}
