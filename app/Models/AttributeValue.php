<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeValue extends Model
{
    public $timestamps = false;

    protected $fillable = ['attribute_id', 'value', 'slug', 'swatch', 'sort_order'];

    protected $casts = ['attribute_id' => 'integer', 'sort_order' => 'integer'];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
