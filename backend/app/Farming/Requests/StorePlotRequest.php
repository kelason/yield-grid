<?php

namespace App\Farming\Requests;

use Domain\Farming\Enums\SoilType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlotRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'soil_type' => ['nullable', 'string', Rule::enum(SoilType::class)],
            'coordinates' => ['required', 'array', 'min:3'],
            'coordinates.*' => ['required', 'array', 'size:2'],
            'coordinates.*.*' => ['required', 'numeric'], // [lng, lat]
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            $farm = $this->route('farm');
            $coordinates = $this->input('coordinates');

            if ($farm && $farm->city && is_array($coordinates) && count($coordinates) >= 3) {
                // Calculate centroid of coordinates
                $latSum = 0;
                $lonSum = 0;
                $count = count($coordinates);
                foreach ($coordinates as $coord) {
                    $lonSum += (float) ($coord[0] ?? 0);
                    $latSum += (float) ($coord[1] ?? 0);
                }
                $lat = $latSum / $count;
                $lon = $lonSum / $count;

                $geo = \Domain\Farming\Models\Plot::reverseGeocodeCoordinates($lat, $lon);
                if ($geo && (!empty($geo['city']) || !empty($geo['state']))) {
                    $farmCityClean = strtolower(trim(str_replace(['city', 'municipality', 'town'], '', $farm->city)));
                    $resolvedCityClean = strtolower(trim(str_replace(['city', 'municipality', 'town'], '', $geo['city'] ?? '')));
                    
                    $matched = false;
                    if (!empty($farmCityClean) && !empty($resolvedCityClean)) {
                        if (str_contains($farmCityClean, $resolvedCityClean) || str_contains($resolvedCityClean, $farmCityClean)) {
                            $matched = true;
                        }
                    }

                    if (!$matched && !empty($farm->state) && !empty($geo['state'])) {
                        $farmStateClean = strtolower(trim($farm->state));
                        $resolvedStateClean = strtolower(trim($geo['state']));
                        if (str_contains($farmStateClean, $resolvedStateClean) || str_contains($resolvedStateClean, $farmStateClean)) {
                            $matched = true;
                        }
                    }

                    if (!$matched) {
                        $detected = array_filter([$geo['city'] ?? null, $geo['state'] ?? null]);
                        $detectedStr = implode(', ', $detected);
                        $validator->errors()->add('coordinates', "This plot is located in {$detectedStr}. Plots for this farm must be located within {$farm->city}.");
                    }
                }
            }
        });
    }
}
