<?php

namespace App\Casts;

use App\ValueObjects\GeometryShape;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GeometryCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (! $value) {
            return null;
        }

        $geoJson = DB::selectOne('SELECT ST_AsGeoJSON(?) AS geojson', [$value]);

        return GeometryShape::fromJson($geoJson->geojson);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        $geometryShape = match (true) {
            is_string($value) => GeometryShape::fromJson($value),
            $value instanceof GeometryShape => $value,
            default => throw new \InvalidArgumentException('Invalid value for GeometryCast'),
        };

        $json = json_encode($geometryShape);

        return DB::raw("ST_GeomFromGeoJSON('$json')");
    }
}
