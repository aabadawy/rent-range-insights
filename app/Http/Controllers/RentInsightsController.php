<?php

namespace App\Http\Controllers;

use App\Http\Requests\RentInsightsRequest;
use App\Queries\RentPriceInsightsQuery;

class RentInsightsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(RentInsightsRequest $request)
    {
        return response()->json([
            'data' => (new RentPriceInsightsQuery(
                $request->validated('coordinate'),
                $request->validated('postal_code'),
                $request->validated('construction_period'),
                $request->validated('number_of_rooms'),
                $request->validated('furnished'),
            ))->execute(),
        ]);
    }
}
