<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemoOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'date',
        'order',
        'pi',
        'customer',
        'supplier',
        'item',
        'status',
        
    ];
}
