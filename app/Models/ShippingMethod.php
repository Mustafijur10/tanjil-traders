<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableText;
use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    use HasTranslatableText;

    public $timestamps = false;

    protected $fillable = ['name', 'delivery_time', 'charge', 'free_over', 'sort_order', 'is_active'];

    protected $casts = [
        'charge'     => 'float',
        'free_over'  => 'float',
        'sort_order' => 'integer',
        'is_active'  => 'boolean',
    ];

    protected array $translatable = ['name', 'delivery_time'];

    public function chargeFor(float $subtotal): float
    {
        return $this->free_over !== null && $subtotal >= $this->free_over ? 0.0 : $this->charge;
    }
}
