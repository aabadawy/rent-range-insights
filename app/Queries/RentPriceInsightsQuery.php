<?php

namespace App\Queries;

use App\Enums\ConstructionPeriodEnum;
use App\Models\District;
use App\Models\Unit;
use App\ValueObjects\Money;

readonly class RentPriceInsightsQuery
{
    public function __construct(
        private ?array $coordinate,
        private ?string $postalCode,
        private string $constructionPeriod,
        private int $rooms,
        private bool $furnished,
    ) {}

    public function execute(): array
    {
        $rawData = Unit::query()
            ->when(
                $this->resolveDistrictNumber(),
                fn ($q) => $q->where('district_number', $this->resolveDistrictNumber()),
                fn ($q) => $this->applyGeometryFallback($q)
            )
            ->where('construction_period', ConstructionPeriodEnum::fromString($this->constructionPeriod))
            ->where('number_of_rooms', $this->rooms)
            ->where('rental_type', $this->furnished)
            ->selectRaw('
                MAX(maximum_rent) as max_rent,
                MIN(minimum_rent) as min_rent,
                AVG(reference_rent) as average_rent
            ')
            ->withCasts([
                'max_rent' => Money::class,
                'min_rent' => Money::class,
                'average_rent' => Money::class,
            ])
            ->first()
            ->only('max_rent', 'min_rent', 'average_rent');

        return array_map(fn ($value) => $value->toEuro(), $rawData);
    }

    private function resolveDistrictNumber(): ?int
    {
        return once(function () {
            if ($this->coordinate) {
                return District::query()
                    ->byCoordinates($this->coordinate['longitude'], $this->coordinate['latitude'])
                    ->value('district_number');
            }

            return District::query()
                ->where('postal_code', $this->postalCode)
                ->value('district_number');
        });
    }

    private function applyGeometryFallback($query)
    {
        if (! $this->coordinate) {
            return $query;
        }

        return $query->whereGeometry(
            $this->coordinate['longitude'],
            $this->coordinate['latitude']
        );
    }
}
