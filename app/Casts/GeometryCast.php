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

        dd($model->exists, $geoJson);

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

        $numericCoordinates = array_map(function ($ring) {
            return array_map(function ($point) {
                return [$point[0], $point[1]];
            }, $ring);
        }, $geometryShape->coordinates);

        $json = json_encode([
            'type' => 'Polygon',
            'coordinates' => $numericCoordinates,
        ], JSON_BIGINT_AS_STRING);

        return DB::raw("ST_GeomFromGeoJSON('$json')");
    }
}
