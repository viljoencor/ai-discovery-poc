<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PropertySearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AccommodationSearchController extends Controller
{
    public function search(
        Request $request,
        PropertySearchService $search
    ): JsonResponse {
        $input = $request->validate([
            'query' => [
                'required',
                'string',
                'min:5',
                'max:500',
            ],
        ]);

        $requestId = (string) Str::uuid();

        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => config('ai.token'),
                'X-Request-ID' => $requestId,
            ])
                ->acceptJson()
                ->timeout(config('ai.timeout_seconds'))
                ->post(
                    rtrim(config('ai.url'), '/') . '/v1/interpret',
                    $input
                );

        } catch (ConnectionException $e) {
            return response()->json([
                'message' => 'The AI search assistant is temporarily unavailable.',
                'request_id' => $requestId,
            ], 503);
        }

        if (
            !$response->successful()
            || !is_array($response->json('intent'))
        ) {
            return response()->json([
                'message' => 'The AI search assistant is temporarily unavailable.',
                'request_id' => $requestId,
            ], 503);
        }

        try {
            $intent = $search->validateIntent(
                $response->json('intent')
            );

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The AI returned invalid search criteria.',
                'request_id' => $requestId,
            ], 502);
        }

        $results = $search->search($intent)
            ->map(fn ($property) => [
                'id' => $property->id,
                'name' => $property->name,
                'city' => $property->city,
                'description' => $property->description,
                'guest_capacity' => $property->guest_capacity,
                'price_per_night_zar' => (float) $property->base_price_zar,
                'amenities' => $property->amenities,
                'pets_allowed' => $property->pets_allowed,
            ])
            ->values();

        return response()->json([
            'request_id' => $requestId,
            'mode' => $response->json('mode', 'unknown'),
            'intent' => $intent,
            'results' => $results,
            'result_count' => $results->count(),
            'availability_checked' => (
                !empty($intent['check_in'])
                && !empty($intent['check_out'])
            ),
            'disclaimer' => 'Synthetic demo properties and indicative nightly prices. Not actual listings or bookable offers.',
        ]);
    }
}