<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'price_per_night' => (float) $this->price_per_night,
            'max_guests' => $this->max_guests,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'images' => $this->images->map(fn ($img) => $img->url),
            'amenities' => $this->amenities,
            'status' => $this->status,
            'featured' => (bool) $this->is_featured,
            'rating' => $this->reviews_avg_overall_rating ? round($this->reviews_avg_overall_rating, 1) : null,
            'review_count' => $this->reviews_count ?? 0,
        ];
    }
}
