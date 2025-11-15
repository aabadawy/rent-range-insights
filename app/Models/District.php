<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * District Model (Quartier)
 * Represents Paris districts with their geographic information
 */
class District extends Model
{
    use HasFactory;

    protected $fillable = [
        'district_section_number',  // N_SQ_QU - Numéro de section de quartier
        'district_number',          // Numéro du quartier / C_QU
        'insee_code',              // C_QUINSEE - Code INSEE du quartier
        'district_name',           // L_QU - Nom du quartier
        'borough_code',            // C_AR - Code de l'arrondissement
        'borough_section_number',  // N_SQ_AR - Numéro de section de l'arrondissement
        'perimeter',               // PERIMETRE - Périmètre
        'surface_area',            // SURFACE - Surface
        'latitude',                // Latitude
        'longitude',               // Longitude
        'postal_code',             // ZIP CODE - Code postal
    ];

    protected $casts = [
        'district_number' => 'integer',
        'perimeter' => 'decimal:8',
        'surface_area' => 'decimal:8',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get all rent data for this district
     * Relation: District has many RentData
     */
    public function rentData(): HasMany
    {
        return $this->hasMany(Unit::class, 'district_number', 'district_number');
    }

    /**
     * Get all boundary points for this district
     * Relation: District has many DistrictBoundary points
     */
    public function boundaries(): HasMany
    {
        return $this->hasMany(DistrictBoundary::class, 'district_number', 'district_number')
            ->orderBy('sequence_order');
    }

    /**
     * Find district by postal code
     */
    public function scopeByPostalCode($query, string $postalCode)
    {
        return $query->where('postal_code', $postalCode);
    }

    /**
     * Find district by coordinates (within a small range)
     * Uses bounding box for performance
     */
    public function scopeByCoordinates($query, float $longitude, float $latitude, float $radius = 0.01)
    {
        return $query
            ->whereBetween('longitude', [$longitude - $radius, $longitude + $radius])
            ->whereBetween('latitude', [$latitude - $radius, $latitude + $radius]);
    }
}
