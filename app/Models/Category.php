<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'parent_id',
        'title',
        'slug',
        'description',
        'icon',
        'accent_color',
        'status',
        'show_in_nav',
        'featured',
        'allow_reviews',
        'order',
    ];

    protected $casts = [
        'show_in_nav'   => 'boolean',
        'featured'      => 'boolean',
        'allow_reviews' => 'boolean',
        'order'         => 'integer',
        'parent_id'     => 'integer',
    ];

    protected $appends = [
        'sub_count',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = static::generateUniqueSlug($category->title);
            }
        });
    }

    public static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        if (empty($base)) {
            $base = 'category';
        }
        $slug = $base;
        $counter = 1;

        while (static::where('slug', $slug)->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('order')->orderBy('title');
    }

    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    public function scopeTopLevel($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('parent_id')
              ->orWhere('parent_id', 0);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeInNav($query)
    {
        return $query->where('show_in_nav', true);
    }

    public function getSubCountAttribute(): int
    {
        if ($this->relationLoaded('children')) {
            return $this->children->count();
        }
        return $this->children()->count();
    }
}