<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BidsLelang; // <--- Pastikan ini ter-import (Opsional jika satu folder, tapi aman)

class ItemLelang extends Model
{
    use HasFactory;

    protected $table = 'items';

    protected $fillable = [
        'user_id',
        'slug',
        'name',
        'description',
        'image',
        'initial_price',
        'current_price',
        'status',
        'end_at',
        'winner_id',
    ];

    protected $casts = [
        'end_at' => 'datetime',
        'initial_price' => 'integer',
        'current_price' => 'integer',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function bids()
    {
        // --- PERBAIKAN DI SINI ---
        // Sebelumnya: return $this->hasMany(BindsLelang::class);
        // Sekarang (Benar):
        return $this->hasMany(BidsLelang::class, 'item_id');
    }
}