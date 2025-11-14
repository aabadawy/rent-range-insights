<?php

namespace App\Http\Controllers;

use App\Enums\ConstructionPeriodEnum;
use App\Models\District;
use App\Models\RentData;
use App\ValueObjects\Money;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RentInsightsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'coordinate' => ['filled', 'exclude_with:postal_code', 'array', 'required_array_keys:longitude,latitude'],
            'postal_code' => ['required_without:coordinate', Rule::exists(District::class, 'postal_code')],
            'construction_period' => ['required', Rule::in(ConstructionPeriodEnum::stringOptions())],
            'number_of_rooms' => ['required', 'integer', 'min:1', 'max:5'],
            'furnished' => ['required', 'boolean'],
        ]);

        $result = RentData::query()
            ->when($request->filled('coordinate'),
                fn ($query) => $query->whereGeometry($request->input('coordinate.longitude'), $request->input('coordinate.latitude')),
                fn ($query) => $query->where('district_number', District::query()->where('postal_code', $request->input('postal_code'))->value('districts.district_number'))
            )
            ->where('construction_period', ConstructionPeriodEnum::fromString($request->input('construction_period')))
            ->where('number_of_rooms', $request->input('number_of_rooms'))
            ->where('rental_type', $request->boolean('furnished'))
            ->selectRaw('MAX(maximum_rent) as max_rent, MIN(minimum_rent) as min_rent, AVG(reference_rent)::NUMERIC(10,2) as average_rent')
            ->withCasts([
                'max_rent' => Money::class,
                'min_rent' => Money::class,
                'average_rent' => Money::class,
            ])
            ->first()
            ->only('max_rent', 'min_rent', 'average_rent');

        return response()->json([
            'data' => collect($result)->map(fn ($value) => $value->toEuro()),
        ]);
    }
}
