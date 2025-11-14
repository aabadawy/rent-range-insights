<?php

use App\Enums\ConstructionPeriodEnum;
use App\Models\District;
use App\Models\RentData;
use App\ValueObjects\GeometryPoint;
use App\ValueObjects\GeometryShape;
use App\ValueObjects\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;

uses(LazilyRefreshDatabase::class);

test('it should return the rent insights when filter by postal code', function () {
    $district = District::factory()->create(['postal_code' => '75001']);

    $rentData = RentData::factory()
        ->for($district)
        ->state([
            'construction_period' => ConstructionPeriodEnum::fromString('1946-1970'),
            'number_of_rooms' => 2,
            'rental_type' => 1,
            'district_number' => $district->district_number,
        ])
        ->createMany([
            [
                'maximum_rent' => $expectedMaxRent = Money::make(1000),
                'minimum_rent' => Money::make(500),
                'geometry_shape' => $coordinates = GeometryShape::fromJson(
                    file_get_contents(base_path('tests/Fixtures/Coordinates/1.json'))
                ),
                'geometry_point' => GeometryPoint::make($coordinates->coordinates[0][0]),
            ],
            [
                'maximum_rent' => Money::make(999),
                'minimum_rent' => $expectedMinRent = Money::make(10),
                'geometry_shape' => $coordinates = GeometryShape::fromJson(
                    file_get_contents(base_path('tests/Fixtures/Coordinates/2.json'))
                ),
                'geometry_point' => GeometryPoint::make($coordinates->coordinates[0][0]),
            ],
        ]);

    $response = $this
        ->getJson(route('rent-insights', [
            'postal_code' => $district->postal_code,
            'construction_period' => '1946-1970',
            'number_of_rooms' => 2,
            'furnished' => true,
        ]))
        ->assertOk()
        ->assertJson(function (AssertableJson $json) use ($expectedMaxRent, $expectedMinRent) {
            return $json->hasAll('data.max_rent', 'data.min_rent', 'data.average_rent')
                ->where('data.max_rent', $expectedMaxRent->amount())
                ->where('data.min_rent', $expectedMinRent->amount())
                ->etc();
        });

    $response->assertStatus(200);
});
