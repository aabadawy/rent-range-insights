<?php

namespace App\ValueObjects;

readonly class GeometryPoint implements \Stringable
{
    public float $longitude;

    public float $latitude;

    public function __construct(float $longitude, float $latitude)
    {
        $this->setLongitude($longitude);

        $this->setLatitude($latitude);
    }

    private function setLongitude(float $longitude): void
    {
        if (! $longitude >= -180 && ! $longitude <= 180) {
            throw new \InvalidArgumentException('Invalid longitude');
        }
        $this->longitude = $longitude;
    }

    private function setLatitude(float $latitude): void
    {
        if (! $latitude >= -90 && ! $latitude <= 90) {
            throw new \InvalidArgumentException('Invalid latitude');
        }
        $this->latitude = $latitude;
    }

    public static function make(): self
    {
        $args = func_get_args();

        if (count($args) === 2) {
            return new self(...$args);
        }

        if (count($args) === 1 && is_array($args[0]) && count($args[0]) === 2) {
            return new self($args[0][0], $args[0][1]);
        }

        if (count($args) === 1 && is_string($args[0])) {
            return new self(...explode(',', $args[0]));
        }

        throw new \InvalidArgumentException('Invalid arguments');
    }

    public function __toString(): string
    {
        return sprintf('%s,%s', $this->longitude, $this->latitude);
    }
}
