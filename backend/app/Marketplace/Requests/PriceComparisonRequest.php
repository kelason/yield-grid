<?php

declare(strict_types=1);

namespace App\Marketplace\Requests;

use App\Constants\MarketplaceConstants;
use App\Domain\Marketplace\Enums\PriceCheckSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PriceComparisonRequest extends FormRequest
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
            'sources' => ['sometimes', 'array', 'min:1'],
            'sources.*' => ['string', Rule::in(array_map(
                fn (PriceCheckSource $source): string => $source->value,
                PriceCheckSource::cases()
            ))],
        ];
    }
}
