<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens; // ✅ TRAIT YANG BENAR DARI SANCTUM
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relasi: User (Admin) bisa membuat banyak Item lelang
     */
    public function items()
    {
        return $this->hasMany(ItemLelang::class);
    }

    /**
     * Relasi: User (Regular) bisa melakukan banyak Bid
     */
    public function bids()
    {
        return $this->hasMany(Bid::class);
    }

    /**
     * Relasi: User bisa memenangkan banyak Item
     */
    public function wonItems()
    {
        return $this->hasMany(Item::class, 'winner_id');
    }
}
