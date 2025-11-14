<?php

namespace App\ValueObjects;

use App\Casts\GeometryCast;
use Illuminate\Contracts\Database\Eloquent\Castable;

readonly class GeometryShape implements Castable
{
    public function __construct(
        public array $coordinates,
        public string $type = 'Polygon',
        public bool $longitudeFirst = true
    ) {
        $this->validate();
        //        dd($this->coordinates);
    }

    public static function fromJson(string $json): self
    {
        // Convert all numeric values to strings before decoding
        $json = preg_replace_callback('/([-]?\d+\.\d+)/', function ($match) {
            return '"'.$match[1].'"';
        }, $json);

        //        dd($json);
        $data = json_decode($json, true);
        if (! isset($data['coordinates']) || $data['type'] !== 'Polygon') {
            throw new \InvalidArgumentException('Invalid GeoJSON format.');
        }

        return new self($data['coordinates']);
    }

    private function validate(): void
    {
        if (! is_array($this->coordinates) || empty($this->coordinates[0])) {
            throw new \InvalidArgumentException('Coordinates array is invalid.');
        }

        // Each point must be [lon, lat]
        foreach ($this->coordinates[0] as $point) {
            if (! is_array($point) || count($point) !== 2) {
                throw new \InvalidArgumentException('Each point must have [lon, lat].');
            }

            [$lon, $lat] = $this->longitudeFirst ? $point : [$point[1], $point[0]];

            if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
                throw new \InvalidArgumentException("Invalid coordinate: [$lon, $lat]");
            }
        }

        // Polygon should have at least 4 points (closing point)
        if (count($this->coordinates[0]) < 4) {
            throw new \InvalidArgumentException('Polygon must contain at least 4 points.');
        }

        if (! $this->isInFrance()) {
            throw new \InvalidArgumentException('Polygon is not in France.');
        }
    }

    public function centroid(): array
    {
        $points = $this->coordinates[0];
        $lat = array_sum(array_column($points, 1)) / count($points);
        $lon = array_sum(array_column($points, 0)) / count($points);

        return ['lat' => $lat, 'lon' => $lon];
    }

    public function isInFrance(): bool
    {
        $centroid = $this->centroid();

        // Bounding box of France mainland (rough based on the data set)
        $franceBounds = [
            'min_lat' => 41.0,
            'max_lat' => 51.5,
            'min_lon' => -5.5,
            'max_lon' => 9.8,
        ];

        return $centroid['lat'] >= $franceBounds['min_lat']
            && $centroid['lat'] <= $franceBounds['max_lat']
            && $centroid['lon'] >= $franceBounds['min_lon']
            && $centroid['lon'] <= $franceBounds['max_lon'];
    }

    public static function castUsing(array $arguments): string
    {
        return GeometryCast::class;
    }
}
