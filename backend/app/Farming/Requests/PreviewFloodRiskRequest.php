<?php

declare(strict_types=1);

namespace App\Farming\Requests;

use App\Constants\FarmingConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

class PreviewFloodRiskRequest extends FormRequest
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
            'coordinates' => ['required', 'array', 'min:'.FarmingConstants::POLYGON_MIN_POINTS],
            'coordinates.*' => ['required', 'array', 'size:'.FarmingConstants::COORD_PAIR_SIZE],
            'coordinates.*.*' => ['required', 'numeric'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $coordinates = $this->input('coordinates');

            if (! is_array($coordinates) || count($coordinates) < FarmingConstants::POLYGON_MIN_POINTS) {
                return;
            }

            if (! $this->isValidRing($coordinates)) {
                $validator->errors()->add('coordinates', 'Draw a single closed area without crossing edges.');
            }
        });
    }

    /**
     * @param  array<int, mixed>  $coordinates
     */
    private function isValidRing(array $coordinates): bool
    {
        $coords = array_values($coordinates);

        if ($coords[0] !== end($coords)) {
            $coords[] = $coords[0];
        }

        $points = [];

        foreach ($coords as $point) {
            if (! is_array($point)) {
                return false;
            }

            $points[] = (float) ($point[0] ?? 0).' '.(float) ($point[1] ?? 0);
        }

        $wkt = 'POLYGON(('.implode(', ', $points).'))';

        $row = DB::selectOne('SELECT ST_IsValid(ST_GeomFromText(?, 4326)) AS valid', [$wkt]);

        return (bool) ($row->valid ?? false);
    }
}
