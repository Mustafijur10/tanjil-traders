<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeOption extends Model
{
    protected $table = 'attribute_options';

    protected $fillable = [
        'title',
        'attribute_id',
        'parent_attribute_option_id',
    ];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }

    public function parent()
    {
        return $this->belongsTo(AttributeOption::class, 'parent_attribute_option_id');
    }

    public function children()
    {
        return $this->hasMany(AttributeOption::class, 'parent_attribute_option_id');
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