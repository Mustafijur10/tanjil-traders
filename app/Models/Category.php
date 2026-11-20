<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasTranslatableText;

    protected $fillable = [
        'parent_id', 'name', 'slug', 'icon', 'image', 'description',
        'meta_title', 'meta_description', 'show_in_nav', 'is_featured', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'parent_id'   => 'integer',
        'show_in_nav' => 'boolean',
        'is_featured' => 'boolean',
        'is_active'   => 'boolean',
        'sort_order'  => 'integer',
    ];

    protected array $translatable = ['name'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeRoots(Builder $q): Builder
    {
        return $q->whereNull('parent_id');
    }

    /** This category's id plus its subcategory ids, for "show everything under Laptops". */
    public function selfAndChildIds(): array
    {
        return array_merge([$this->id], $this->children()->pluck('id')->all());
    }
}
