<?php

namespace App\Services;

use App\Models\Property;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PropertySearchService
{
    private const ALLOWED_AMENITIES = [
        'parking', 'wifi', 'pool', 'braai', 'sea_view', 'beachfront',
    ];

    /**
     * The model returns untrusted suggestions. Validate all fields before query construction.
     * No model-generated SQL, model-generated prices or autonomous booking operations.
     */
    public function validateIntent(array $raw): array
    {
        $validator = Validator::make($raw, [
            'destination' => ['nullable', 'string', 'max:60'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:20'],
            'max_price_zar' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'amenities' => ['present', 'array', 'max:6'],
            'amenities.*' => ['string', 'in:'.implode(',', self::ALLOWED_AMENITIES)],
            'pets_allowed' => ['nullable', 'boolean'],
            'check_in' => ['nullable', 'date_format:Y-m-d', 'required_with:check_out'],
            'check_out' => ['nullable', 'date_format:Y-m-d', 'required_with:check_in', 'after:check_in'],
        ]);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $clean = $validator->validated();
        // No silent defaulting of guests, dates or budgets.
        $clean['amenities'] = array_values(array_unique($clean['amenities'] ?? []));
        return $clean;
    }

    public function search(array $intent): Collection
    {
        $query = Property::query()->where('active', true);
        if (!empty($intent['destination'])) {
            $city = $intent['destination'];
            $query->where('city', '=', $city);
        }
        if (isset($intent['guests'])) {
            $query->where('guest_capacity', '>=', $intent['guests']);
        }
        if (isset($intent['max_price_zar'])) {
            $query->where('base_price_zar', '<=', $intent['max_price_zar']);
        }
        if (array_key_exists('pets_allowed', $intent) && $intent['pets_allowed'] !== null) {
            $query->where('pets_allowed', $intent['pets_allowed']);
        }
        foreach ($intent['amenities'] ?? [] as $amenity) {
            $query->whereJsonContains('amenities', $amenity);
        }

        // Nightly exclusion [check_in, check_out): checkout day itself is not booked.
        if (!empty($intent['check_in']) && !empty($intent['check_out'])) {
            $from = CarbonImmutable::parse($intent['check_in']);
            $to = CarbonImmutable::parse($intent['check_out']);
            $query->whereDoesntHave('blocks', function ($q) use ($from, $to) {
                $q->whereDate('date', '>=', $from->toDateString())
                    ->whereDate('date', '<', $to->toDateString());
            });
        }

        // DEMO: stable property metadata can be cached, but live availability must not be
        // represented as guaranteed bookable or trusted to a potentially stale cache.
        return $query->orderBy('base_price_zar')->limit(8)->get();
    }
}
