<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubClassificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $resource = [
            'sub_classification' => $this->name,
        ];


        if (!$request->has('simple_sub_classification')) {
            $resource = array_merge($resource, [
                'sub_classification_id' => $this->id,
                'classification' => $this->classification->name,
            ]);
        }

        return $resource;
    }
}
