<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeOption extends Model
{
    protected $table = 'attribute_options';

    protected $fillable = [
        'title',
        'slug',
        'attribute_id',
        'parent_attribute_option_id',
        'status',
    ];

    // ─── RELATIONSHIPS ───────────────────────────────────────────────

    // Belongs to an Attribute (maker, model, year, category, etc.)
    public function attribute()
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }

    // Parent option (e.g. a Model belongs to a Maker)
    public function parent()
    {
        return $this->belongsTo(AttributeOption::class, 'parent_attribute_option_id');
    }

    // Direct children (e.g. Models under a Maker, Years under a Model)
    public function children()
    {
        return $this->hasMany(AttributeOption::class, 'parent_attribute_option_id');
    }

    // ─── SCOPES ──────────────────────────────────────────────────────

    public function scopeBySlug($query, string $slug)
    {
        return $query->whereHas('attribute', fn($q) => $q->where('slug', $slug));
    }

    public function scopeTopLevel($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('parent_attribute_option_id')
              ->orWhere('parent_attribute_option_id', 0);
        });
    }

    public function scopeChildrenOf($query, int $parentId)
    {
        return $query->where('parent_attribute_option_id', $parentId);
    }
}