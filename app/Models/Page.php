<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableText;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasTranslatableText;

    protected $fillable = [
        'title', 'slug', 'content', 'meta_title', 'meta_description',
        'show_in_footer', 'sort_order', 'is_published',
    ];

    protected $casts = [
        'show_in_footer' => 'boolean',
        'is_published'   => 'boolean',
        'sort_order'     => 'integer',
    ];

    protected array $translatable = ['title'];
}
