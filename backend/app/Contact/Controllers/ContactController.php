<?php

namespace App\Contact\Controllers;

use App\Contact\Requests\StoreContactRequest;
use App\Http\Controllers\Controller;
use Domain\Contact\Actions\CreateContactMessageAction;
use Domain\Contact\DTOs\CreateContactMessageDTO;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function __invoke(StoreContactRequest $request, CreateContactMessageAction $action): JsonResponse
    {
        $dto = CreateContactMessageDTO::fromRequest($request->validated());
        $action($dto);

        return response()->json(['message' => 'Your message has been sent successfully.']);
    }
}
