<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLES = ['admin', 'staff', 'customer'];

    protected $fillable = ['name', 'email', 'phone', 'password', 'role', 'is_active', 'preferences'];

    protected $hidden = ['password'];

    // Same as the column defaults, so a new model in memory behaves like a saved one.
    protected $attributes = ['role' => 'customer', 'is_active' => true];

    protected function casts(): array
    {
        return [
            'password'    => 'hashed',
            'is_active'   => 'boolean',
            'preferences' => 'array',
        ];
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['admin', 'staff'], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function wishlist(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'wishlists')->withPivot('created_at');
    }
}
