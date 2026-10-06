<?php

declare(strict_types=1);

namespace App\CropRecommendation\Requests;

use App\CropRecommendation\Rules\KnownCrop;
use Illuminate\Foundation\Http\FormRequest;

class CheckCompatibilityRequest extends FormRequest
{
    private const CROP_INPUT_MAX_LENGTH = 100;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'crop_a' => ['required', 'string', 'max:'.self::CROP_INPUT_MAX_LENGTH, new KnownCrop],
            'crop_b' => ['required', 'string', 'max:'.self::CROP_INPUT_MAX_LENGTH, new KnownCrop],
        ];
    }
}
