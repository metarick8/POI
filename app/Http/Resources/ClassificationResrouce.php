<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassificationResrouce extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'classification_id' => $this->id,
            'classification' => $this->name
        ];
    }
}
