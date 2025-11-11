<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DistrictBoundary Model
 * Represents individual coordinate points that form a district's geographic boundary polygon
 */
class DistrictBoundary extends Model
{
    protected $fillable = [
        'district_number',
        'latitude',
        'longitude',
        'sequence_order',
    ];

    protected $casts = [
        'district_number' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'sequence_order' => 'integer',
    ];

    /**
     * Get the district this boundary point belongs to
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_number', 'district_number');
    }

    /**
     * Scope to get boundary points ordered by sequence
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence_order');
    }
}
