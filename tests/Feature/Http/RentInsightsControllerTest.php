<?php


use Illuminate\Testing\Fluent\AssertableJson;

test('it should return the rent insights keys', function () {
    $response = $this
        ->getJson(route('rent-insights'))
        ->assertOk()
        ->assertJson(function (AssertableJson $json) {
            return $json->hasAll('data.max_rent', 'data.min_rent', 'data.average_rent')
                ->etc();
        });

    $response->assertStatus(200);
});
