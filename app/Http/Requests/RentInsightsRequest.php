<?php

namespace App\Http\Requests;

use App\Enums\ConstructionPeriodEnum;
use App\Models\District;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RentInsightsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'coordinate' => ['filled', 'exclude_with:postal_code', 'array', 'required_array_keys:longitude,latitude'],
            'postal_code' => ['required_without:coordinate', Rule::exists(District::class, 'postal_code')],
            'construction_period' => ['required', Rule::in(ConstructionPeriodEnum::stringOptions())],
            'number_of_rooms' => ['required', 'integer', 'min:1', 'max:5'],
            'furnished' => ['required', 'boolean'],
        ];
    }
}
