<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->user->debater && $this->type === 'debater'
                ? new DebaterResource($this->user->debater)
                : ($this->user->judge && in_array($this->type, ['chair_judge', 'panelist_judge'])
                    ? new JudgeResource($this->user->judge)
                    : null),
            'debate_id' => $this->debate_id,
            'debate_motion' => $this->debate?->motion?->sentence,
            'type' => $this->type, // Changed from 'role' to 'type'
            'status' => $this->status,
            'account_status' => $this->user?->isBanned() ? 'banned' : 'active',
            'date' => $this->created_at,
        ];
    }
}
