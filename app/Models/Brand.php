<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Brand extends Model
{
    use HasFactory;

    protected $table = 'brands';

    protected $fillable = [
        'title',
        'slug',
        'tagline',
        'description',
        'tier',
        'sort_order',
        'logo',
        'banner',
        'country',
        'headquarters',
        'founded_year',
        'website',
        'category_ids',
        'warranty_months',
        'warranty_type',
        'supply_type',
        'return_days',
        'warranty_terms',
        'service_centers',
        'support_email',
        'support_phone',
        'support_url',
        'certifications',
        'social',
        'meta_title',
        'meta_description',
        'keywords',
        'status',
        'is_official',
        'feature_homepage',
        'is_popular',
        'show_in_nav',
        'show_in_filters',
        'allow_reviews',
    ];

    protected $casts = [
        'category_ids'     => 'array',
        'certifications'   => 'array',
        'social'           => 'array',
        'keywords'         => 'array',
        'is_official'      => 'boolean',
        'feature_homepage' => 'boolean',
        'is_popular'       => 'boolean',
        'show_in_nav'      => 'boolean',
        'show_in_filters'  => 'boolean',
        'allow_reviews'    => 'boolean',
        'sort_order'       => 'integer',
        'founded_year'     => 'integer',
        'warranty_months'  => 'integer',
        'return_days'      => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($brand) {
            if (empty($brand->slug)) {
                $brand->slug = static::generateUniqueSlug($brand->title);
            }
        });
    }

    public static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        if (empty($base)) {
            $base = 'brand';
        }
        $slug = $base;
        $counter = 1;

        while (static::where('slug', $slug)->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('feature_homepage', true);
    }
}