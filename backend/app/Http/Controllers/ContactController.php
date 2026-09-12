<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Jobs\SendContactNotificationJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        $message = ContactMessage::create($validated);

        SendContactNotificationJob::dispatch($message);

        return response()->json(['message' => 'Your message has been sent successfully.']);
    }
}
