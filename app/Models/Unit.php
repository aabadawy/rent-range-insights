<?php

namespace App\Models;

use App\Enums\ConstructionPeriodEnum;
use App\ValueObjects\GeometryPoint;
use App\ValueObjects\GeometryShape;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Unit extends Model
{
    use HasFactory;

    const RADIUS_METERS = 1000;

    protected $fillable = [
        'geographic_sector',
        'district_number',
        'district_name',
        'number_of_rooms',
        'construction_period',
        'rental_type',
        'reference_rent',
        'maximum_rent',
        'minimum_rent',
        'year',
        'city',
        'geometry_shape',
        'geometry_point',
    ];

    protected static function booted(): void
    {
        parent::saving(function (self $rentData) {
            $rentData->forceFill([
                'unit_md5' => hex2bin(md5(
                    $rentData->district_number.$rentData->number_of_rooms.$rentData->construction_period->value.$rentData->rental_type.$rentData->year.$rentData->city.$rentData->geographic_sector.$rentData->latitude.$rentData->longitude
                )),
            ]);
        });
    }

    protected $casts = [
        'district_number' => 'integer',
        'number_of_rooms' => 'integer',
        'construction_period' => ConstructionPeriodEnum::class,
        'reference_rent' => Money::class,
        'maximum_rent' => Money::class,
        'minimum_rent' => Money::class,
        'year' => 'integer',
        'geometry_shape' => GeometryShape::class,
    ];

    /**
     * Get the district this rent data belongs to
     * Relation: RentData belongs to District
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_number', 'district_number');
    }

    public function geometryPoint(): Attribute
    {
        return Attribute::make(
            get: fn () => new GeometryPoint($this->longitude, $this->latitude),
            set: fn (GeometryPoint $value) => [
                'longitude' => $value->longitude,
                'latitude' => $value->latitude,
            ]
        );
    }

    public function scopeWhereGeometry(Builder $query, ...$args)
    {
        [$long, $lat] = match (true) {
            count($args) === 1 && is_array($args[0]) && count($args[0]) === 2 => [$args[0][0], $args[0][1]],
            count($args) === 2 => [$args[0], $args[1]],
        };

        return $query->whereRaw(
            'ST_DWithin(geometry_shape,ST_SetSRID(ST_MakePoint(?, ?), 4326),?)',
            [$long, $lat, config('app.radius_meters', self::RADIUS_METERS)]
        );
    }
}
