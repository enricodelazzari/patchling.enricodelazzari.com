<?php

namespace App\Http\Resources;

use App\Models\Result;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Result
 */
class ResultResource extends JsonResource
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
            'name' => $this->name,
            'score' => $this->score,
            'rank' => $this->rank ?? $this->position(),
            'verdict' => $this->verdict,
            'verdict_label' => $this->verdict->label(),
            'months' => $this->months,
            'revenue' => $this->revenue,
            'features' => $this->features,
            'squashed' => $this->squashed,
            'breaches' => $this->breaches,
            'share_url' => route('results.show', $this->resource),
            'created_at' => $this->created_at,
        ];
    }
}
