<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemAllResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // Tampilan ringkas untuk List (Index)
            'slug'          => $this->slug,
            'name'          => $this->name,
            'image_url'     => $this->image, 
            'current_bid'   => $this->current_price, // Fokus ke harga saat ini
            'status'        => $this->status,
            'time_left'     => $this->end_at->diffForHumans(),
            // Kita tidak menampilkan deskripsi panjang atau seller di list biar ringan
        ];
    }
}