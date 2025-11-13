<?php

namespace App\Http\Controllers;

use App\Enums\ConstructionPeriodEnum;
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
            // todo add validation for corodinates or postal code
            'construction_period' => ['required', Rule::in(ConstructionPeriodEnum::stringOptions())],
            'number_of_rooms' => ['required', 'integer', 'min:1', 'max:5'],
            'furnished' => ['required', 'boolean'],
        ]);

        return response()->json([
            'data' => [
                'max_rent' => 0,
                'min_rent' => 0,
                'average_rent' => 0,
            ],
        ]);
    }
}
