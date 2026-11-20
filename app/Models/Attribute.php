<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    use HasTranslatableText;

    public const TYPES = ['select', 'text'];

    protected $fillable = [
        'name', 'slug', 'type', 'unit', 'group',
        'is_filterable', 'is_comparable', 'is_variant', 'sort_order',
    ];

    protected $casts = [
        'is_filterable' => 'boolean',
        'is_comparable' => 'boolean',
        'is_variant'    => 'boolean',
        'sort_order'    => 'integer',
    ];

    protected array $translatable = ['name', 'group'];

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)->orderBy('sort_order')->orderBy('value');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withPivot('sort_order');
    }
}
