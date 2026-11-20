<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['query', 'results_count', 'visitor_id'];

    protected $casts = ['results_count' => 'integer'];
}
