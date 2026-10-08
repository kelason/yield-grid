<?php

declare(strict_types=1);

namespace App\Farming\Requests;

use App\Constants\FloodRiskConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class FloodZoneIndexRequest extends FormRequest
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
            'bbox' => ['required', 'string', 'regex:/^-?\d+(\.\d+)?,-?\d+(\.\d+)?,-?\d+(\.\d+)?,-?\d+(\.\d+)?$/'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $bbox = $this->input('bbox');

            if (! is_string($bbox)) {
                return;
            }

            $parts = array_map(fn (string $value): float => (float) $value, explode(',', $bbox));

            if (count($parts) !== 4) {
                return;
            }

            [$minx, $miny, $maxx, $maxy] = $parts;

            $inRange = $minx >= -180 && $maxx <= 180 && $miny >= -90 && $maxy <= 90;
            $ordered = $minx < $maxx && $miny < $maxy;
            $spanOk = ($maxx - $minx) <= FloodRiskConstants::BBOX_MAX_SPAN_DEGREES
                && ($maxy - $miny) <= FloodRiskConstants::BBOX_MAX_SPAN_DEGREES;

            if (! ($inRange && $ordered && $spanOk)) {
                $validator->errors()->add('bbox', 'Bounding box must use valid ranges with a span of 5 degrees or less.');
            }
        });
    }

    /**
     * @return array{float, float, float, float}
     */
    public function bbox(): array
    {
        $parts = array_map(fn (string $value): float => (float) $value, explode(',', (string) $this->input('bbox')));

        return [$parts[0] ?? 0.0, $parts[1] ?? 0.0, $parts[2] ?? 0.0, $parts[3] ?? 0.0];
    }
}
