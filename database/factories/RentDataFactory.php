<?php

namespace Database\Factories;

use App\Models\RentData;
use Illuminate\Database\Eloquent\Factories\Factory;

class RentDataFactory extends Factory
{
    protected $model = RentData::class;

    public function definition(): array
    {
        $districtNumber = $this->faker->numberBetween(1, 80);
        $rooms = $this->faker->randomElement([1, 2, 3, 4]);
        $constructionPeriods = [
            'Avant 1946',
            '1946-1970',
            '1971-1990',
            'Apres 1990',
        ];
        $rentalTypes = ['meublé', 'non meublé'];

        // base rent €/m² depending on rental type and rooms
        $baseRent = match ($rentalTypes[array_rand($rentalTypes)]) {
            'meublé' => $this->faker->randomFloat(1, 25, 40),
            default => $this->faker->randomFloat(1, 18, 30),
        };

        return [
            'geographic_sector' => $this->faker->numberBetween(1, 20),
            'district_number' => $districtNumber,
            'district_name' => 'Quartier '.$districtNumber,
            'number_of_rooms' => $rooms,
            'construction_period' => $this->faker->randomElement($constructionPeriods),
            'rental_type' => $this->faker->randomElement($rentalTypes) === 'meublé',
            'reference_rent' => $baseRent,
            'maximum_rent' => round($baseRent * 1.2, 1),
            'minimum_rent' => round($baseRent * 0.7, 1),
            'year' => $this->faker->numberBetween(2018, 2024),
            'city' => 'PARIS',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
