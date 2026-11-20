<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visit extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'visitor_id', 'session_id', 'user_id', 'path', 'page_type', 'product_id',
        'referrer', 'source', 'source_name', 'device', 'browser', 'os',
        'lcp', 'inp', 'cls', 'ttfb', 'last_seen_at',
    ];

    protected $casts = [
        'user_id'      => 'integer',
        'product_id'   => 'integer',
        'lcp'          => 'integer',
        'inp'          => 'integer',
        'cls'          => 'float',
        'ttfb'         => 'integer',
        'last_seen_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
