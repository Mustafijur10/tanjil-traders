<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Courier extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'phone', 'tracking_url', 'sort_order', 'is_active'];

    protected $casts = ['sort_order' => 'integer', 'is_active' => 'boolean'];

    /** Link to the courier's tracking page, with {code} replaced by the tracking code. */
    public function trackingLink(?string $code): ?string
    {
        if (! $code || ! $this->tracking_url) {
            return null;
        }

        return str_contains($this->tracking_url, '{code}')
            ? str_replace('{code}', rawurlencode($code), $this->tracking_url)
            : $this->tracking_url;
    }
}
