<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    protected $fillable = [
        'project_category_id', 'slug', 'title', 'title_ar', 'category', 'category_ar',
        'description', 'description_ar', 'image', 'tags',
        'sort_order', 'is_active',
    ];

    protected $casts = [
        'tags'      => 'array',
        'is_active' => 'boolean',
    ];

    public function projectCategory(): BelongsTo
    {
        return $this->belongsTo(ProjectCategory::class);
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value
                ? (str_starts_with($value, 'http') ? $value : asset('storage/' . $value))
                : null,
        );
    }
}
