<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ItemLelang; 

// Ubah nama class dari BindsLelang menjadi Bid
class BidsLelang extends Model
{
    use HasFactory;

    // Pastikan tabelnya mengarah ke 'bids' (sesuai migrasi)
    // Kalau nama tabel di database Anda 'bids_lelangs', ganti string di bawah ini.
    protected $table = 'bids'; 

    protected $fillable = [
        'user_id', 
        'item_id', 
        'amount', 
    ];
    
    protected $casts = [
        'amount' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function item()
    {
        // Pastikan ini ItemLelang::class
        return $this->belongsTo(ItemLelang::class, 'item_id');
    }
}