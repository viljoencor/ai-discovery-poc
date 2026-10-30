<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    protected $fillable = [
        'name', 'city', 'description', 'guest_capacity', 'base_price_zar',
        'amenities', 'pets_allowed', 'active',
    ];

    protected function casts(): array
    {
        return [
            'amenities' => 'array',
            'pets_allowed' => 'boolean',
            'active' => 'boolean',
            'base_price_zar' => 'decimal:2',
        ];
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(AvailabilityBlock::class);
    }
}
