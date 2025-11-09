<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use function Pest\Laravel\json;

class RentInsightsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        return response()->json([
            'data' => [
                'max_rent' => 0,
                'min_rent' => 0,
                'average_rent' => 0,
            ]
        ]);
    }
}
