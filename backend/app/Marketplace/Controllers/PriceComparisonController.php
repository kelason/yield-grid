<?php

declare(strict_types=1);

namespace App\Marketplace\Controllers;

use App\Domain\Marketplace\Actions\GetPriceComparisonAction;
use App\Marketplace\Requests\PriceComparisonRequest;
use App\Marketplace\Resources\PriceComparisonResource;
use App\Shared\Controllers\Controller;

final class PriceComparisonController extends Controller
{
    public function compare(PriceComparisonRequest $request, GetPriceComparisonAction $action): PriceComparisonResource
    {
        $validated = $request->validated();

        $sources = null;

        if (isset($validated['sources']) && is_array($validated['sources'])) {
            $sources = array_values(array_map(strval(...), $validated['sources']));
        }

        return new PriceComparisonResource($action->execute(
            (string) $validated['crop'],
            isset($validated['region']) ? (string) $validated['region'] : null,
            $sources,
        ));
    }
}
