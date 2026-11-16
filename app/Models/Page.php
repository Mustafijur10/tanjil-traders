<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class Page extends Model
{
    /** Slugs that would shadow real routes. Add yours here. */
    public const RESERVED = [
        'admin', 'api', 'login', 'logout', 'register', 'cart', 'checkout', 'account', 'profile', 'wishlist',
        'products', 'product', 'category', 'categories', 'brand', 'brands', 'search', 'orders', 'order',
        'storage', 'sitemap', 'build', 'images', 'css', 'js', 'favicon', 'robots', 'sanctum',
    ];

    protected $fillable = [
        'title', 'slug', 'short_description', 'content', 'featured_image',
        'meta_title', 'meta_description', 'meta_keywords', 'canonical_url',
        'og_title', 'og_description', 'og_image', 'robots_index', 'robots_follow',
        'show_in_footer', 'show_in_navigation', 'show_in_sitemap', 'display_order',
        'visibility', 'status', 'published_at',
    ];

    protected $casts = [
        'robots_index' => 'boolean',
        'robots_follow' => 'boolean',
        'show_in_footer' => 'boolean',
        'show_in_navigation' => 'boolean',
        'show_in_sitemap' => 'boolean',
        'display_order' => 'integer',
        'published_at' => 'datetime',
    ];

    protected $appends = ['seo_status'];

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published');
    }

    /** good = title + description set, partial = one of them, missing = neither */
    public function getSeoStatusAttribute(): string
    {
        $t = filled($this->meta_title);
        $d = filled($this->meta_description);

        return $t && $d ? 'good' : ($t || $d ? 'partial' : 'missing');
    }

    /** Value for <meta name="robots">. Unlisted pages are always noindex. */
    public function robotsContent(): string
    {
        $index = $this->robots_index && $this->visibility === 'public';

        return ($index ? 'index' : 'noindex') . ',' . ($this->robots_follow ? 'follow' : 'nofollow');
    }

    /**
     * Allow-list sanitizer for editor HTML (HTMLPurifier). Scripts, event handlers,
     * iframes, data: URLs and unknown tags/attributes/CSS are removed.
     */
    public static function clean(?string $html): string
    {
        if (! filled($html)) {
            return '';
        }

        $dir = storage_path('app/purifier');
        File::ensureDirectoryExists($dir);

        $c = \HTMLPurifier_Config::createDefault();
        $c->set('Cache.SerializerPath', $dir);
        $c->set('HTML.Allowed',
            'h1,h2,h3,h4,p,br,hr,strong,b,em,i,u,s,blockquote,ul,ol,li,span,div,' .
            'a[href|title|target|rel],img[src|alt|title],' .
            'table,thead,tbody,tfoot,tr,th[colspan|rowspan],td[colspan|rowspan],*[style|class]'
        );
        $c->set('CSS.AllowedProperties', ['color', 'background-color', 'font-size', 'font-family', 'text-align', 'width']);
        $c->set('Attr.AllowedClasses', ['cms-align-left', 'cms-align-center', 'cms-align-right']);
        $c->set('Attr.AllowedFrameTargets', ['_blank', '_self']);
        $c->set('Attr.AllowedRel', ['noopener', 'noreferrer', 'nofollow']);
        $c->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
        $c->set('AutoFormat.RemoveEmpty', false);

        return (new \HTMLPurifier($c))->purify($html);
    }
}
