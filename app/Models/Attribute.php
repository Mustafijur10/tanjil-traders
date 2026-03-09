<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $table = 'attributes';

    protected $fillable = [
        'title',
        'slug',
        'type',
        'status',
        'parent_attribute_id',
    ];

    // ─── RELATIONSHIPS ───────────────────────────────────────────────

    public function options()
    {
        return $this->hasMany(AttributeOption::class, 'attribute_id');
    }

    public function parent()
    {
        return $this->belongsTo(Attribute::class, 'parent_attribute_id');
    }

    public function children()
    {
        return $this->hasMany(Attribute::class, 'parent_attribute_id');
    }
}