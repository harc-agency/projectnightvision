<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

test('location predictor returns mapped suggestions for authenticated users', function () {
    Http::fake([
        'https://photon.komoot.io/*' => Http::response([
            'features' => [
                [
                    'geometry' => [
                        'coordinates' => [-111.833836, 41.73698],
                    ],
                    'properties' => [
                        'name' => 'Logan',
                        'city' => 'Logan',
                        'state' => 'Utah',
                        'country' => 'United States',
                    ],
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->getJson(route('locations.predict', ['q' => 'Logan, UT']));

    $response
        ->assertOk()
        ->assertJson([
            'data' => [
                [
                    'label' => 'Logan, Utah, United States',
                    'lat' => 41.73698,
                    'lng' => -111.833836,
                    'source' => 'location_predictor',
                ],
            ],
        ]);
});
