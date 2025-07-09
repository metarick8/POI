<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DebateInitialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'date' => $this->start_date,
            'time' => $this->start_time,
            'type' => $this->type,
            'status' => $this->status,
            'motion' => $this->ths
        ];
    }
}
