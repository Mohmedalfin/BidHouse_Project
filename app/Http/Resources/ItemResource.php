<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug'          => $this->slug,
            'name'          => $this->name,
            'description'   => $this->description,
            'image_url'     => $this->image,
            
            'starting_price' => $this->initial_price,
            'current_bid'    => $this->current_price, 
            
            'ends_at'       => $this->end_at->format('Y-m-d H:i:s'),
            'time_left'     => $this->end_at->diffForHumans(),
            
            'seller_name'   => $this->user->name,
            'status'        => $this->status,

            // --- TAMBAHAN BARU: Info Pemenang ---
            // Hanya muncul jika lelang sudah ditutup (closed)
            'winner_info' => $this->when($this->status === 'closed', function () {
                return [
                    'winner_name' => $this->winner ? $this->winner->name : 'Tidak ada penawar',
                    'sold_price'  => $this->current_price,
                    'closed_at'   => $this->updated_at->format('Y-m-d H:i:s'), // Waktu ditutup
                ];
            }),
        ];
    }
}