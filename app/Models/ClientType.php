<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class ClientType extends Model
{
    protected $fillable = [
        'label_en', 'label_ar', 'image', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value
                ? (str_starts_with($value, 'http') ? $value : asset('storage/' . $value))
                : null,
        );
    }
}
