<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RentData Model (Données de loyer)
 * Represents rent control data for Paris districts
 */
class RentData extends Model
{
    protected $table = 'rent_data';

    protected $fillable = [
        'geographic_sector',        // Secteurs géographiques
        'district_number',          // Numéro du quartier
        'district_name',            // Nom du quartier
        'number_of_rooms',          // Nombre de pièces principales
        'construction_period',      // Epoque de construction
        'rental_type',              // Type de location (meublé/non meublé)
        'reference_rent',           // Loyers de référence (average)
        'maximum_rent',             // Loyers de référence majorés (max)
        'minimum_rent',             // Loyers de référence minorés (min)
        'year',                     // Année
        'city',                     // Ville
    ];

    protected $casts = [
        'district_number' => 'integer',
        'number_of_rooms' => 'integer',
        'reference_rent' => 'decimal:2',
        'maximum_rent' => 'decimal:2',
        'minimum_rent' => 'decimal:2',
        'year' => 'integer',
    ];

    /**
     * Get the district this rent data belongs to
     * Relation: RentData belongs to District
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_number', 'district_number');
    }

    /**
     * Scope to filter by number of rooms
     */
    public function scopeByRooms($query, int $rooms)
    {
        return $query->where('number_of_rooms', $rooms);
    }

    /**
     * Scope to filter by construction period
     */
    public function scopeByConstructionPeriod($query, string $period)
    {
        return $query->where('construction_period', $period);
    }

    /**
     * Scope to filter by rental type
     * @param bool $furnished true for "meublé", false for "non meublé"
     */
    public function scopeByFurnished($query, bool $furnished)
    {
        $rentalType = $furnished ? 'meublé' : 'non meublé';
        return $query->where('rental_type', $rentalType);
    }

    /**
     * Scope to get latest year data
     */
    public function scopeLatestYear($query)
    {
        $latestYear = self::max('year');
        return $query->where('year', $latestYear);
    }
}
