<?php

declare(strict_types=1);

namespace App\Marketplace\Requests;

use App\Constants\MarketplaceConstants;
use Illuminate\Foundation\Http\FormRequest;

class PriceGuideRequest extends FormRequest
{
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
            'crop' => ['required', 'string', 'max:'.MarketplaceConstants::PRICE_GUIDE_CROP_MAX_LENGTH],
            'region' => ['nullable', 'string', 'max:'.MarketplaceConstants::PRICE_GUIDE_REGION_MAX_LENGTH],
        ];
    }
}
