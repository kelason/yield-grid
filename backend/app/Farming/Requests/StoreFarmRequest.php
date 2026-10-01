<?php

namespace App\Farming\Requests;

use App\Constants\FarmingConstants;
use Illuminate\Foundation\Http\FormRequest;

class StoreFarmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role->value === 'farmer';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:'.FarmingConstants::FARM_NAME_MAX_LENGTH],
            'address' => ['nullable', 'string', 'max:'.FarmingConstants::FARM_ADDRESS_MAX_LENGTH],
            'city' => ['nullable', 'string', 'max:'.FarmingConstants::FARM_CITY_MAX_LENGTH],
            'state' => ['nullable', 'string', 'max:'.FarmingConstants::FARM_STATE_MAX_LENGTH],
            'country' => ['nullable', 'string', 'max:'.FarmingConstants::FARM_COUNTRY_MAX_LENGTH],
            'zip' => ['nullable', 'string', 'max:'.FarmingConstants::FARM_ZIP_MAX_LENGTH],
            'total_area' => ['nullable', 'numeric', 'min:'.FarmingConstants::TOTAL_AREA_MIN_HECTARES, 'max:'.FarmingConstants::TOTAL_AREA_MAX_HECTARES],
        ];
    }
}
