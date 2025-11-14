<?php

namespace Database\Factories;

use App\Models\District;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\=District>
 */
class DistrictFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Example: Paris districts (01–20)
        $districtNumber = $this->faker->numberBetween(1, 20);

        return [
            'district_section_number' => $this->faker->numberBetween(1, 10),
            'district_number' => $districtNumber,
            'insee_code' => '751'.str_pad($districtNumber, 2, '0', STR_PAD_LEFT), // Paris INSEE codes like 75101
            'district_name' => 'Quartier '.$districtNumber,
            'borough_code' => 'PARIS',
            'borough_section_number' => $this->faker->numberBetween(1, 20),
            'postal_code' => '750'.str_pad($districtNumber, 2, '0', STR_PAD_LEFT),
            'latitude' => $this->faker->latitude(48.80, 48.90), // Around Paris
            'longitude' => $this->faker->longitude(2.25, 2.45),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
