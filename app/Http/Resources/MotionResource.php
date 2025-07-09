<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MotionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'motion_id' => $this->id,
            'sentence' => $this->sentence,
            'sub_classification' => SubClassificationResource::collection($this->sub_classifications),
        ];
    }
}
