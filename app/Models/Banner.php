<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasTranslatableText;

    public const TYPES = ['hero', 'promo', 'marquee'];

    protected $fillable = [
        'type', 'title', 'subtitle', 'button_text', 'link', 'image', 'mobile_image',
        'starts_at', 'ends_at', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'starts_at'  => 'datetime',
        'ends_at'    => 'datetime',
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    protected array $translatable = ['title', 'subtitle', 'button_text'];

    /** Active and inside its start / end dates. */
    public function scopeLive(Builder $q): Builder
    {
        return $q->where('is_active', true)
            ->where(fn ($w) => $w->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($w) => $w->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderBy('sort_order');
    }
}
