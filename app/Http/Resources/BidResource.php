<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BidResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'bid_amount'  => $this->amount,
            // Pastikan relasi 'user' ada di model BidsLelang agar ini tidak error
            'bidder_name' => $this->user ? $this->user->name : 'Unknown', 
            'bid_time'    => $this->created_at->format('Y-m-d H:i:s'),
            'time_ago'    => $this->created_at->diffForHumans(),
        ];
    }
}