<?php

namespace Database\Seeders;

use App\Models\Dream;
use App\Models\User;
use Illuminate\Database\Seeder;

class PublicDreamGlobeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userIds = User::query()->pluck('id')->values();

        if ($userIds->isEmpty()) {
            return;
        }

        collect($this->seedDreams())->each(function (array $seed, int $index) use ($userIds): void {
            $userId = (int) $userIds[$index % $userIds->count()];

            Dream::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'title' => $seed['title'],
                ],
                [
                    'dream_content' => $seed['dream_content'],
                    'is_public' => true,
                    'dream_date' => now()->subDays($index + 1)->setTime(3, 0),
                    'overall_theme' => $seed['overall_theme'],
                    'sentiment' => $seed['sentiment'],
                    'analysis' => null,
                    'location' => [
                        'lat' => $seed['lat'],
                        'lng' => $seed['lng'],
                        'label' => $seed['label'],
                    ],
                    'weather' => null,
                ],
            );
        });
    }

    /**
     * @return array<int, array{
     *   title: string,
     *   dream_content: string,
     *   overall_theme: string,
     *   sentiment: string,
     *   lat: float,
     *   lng: float,
     *   label: string
     * }>
     */
    protected function seedDreams(): array
    {
        return [
            [
                'title' => 'Aurora Over Anchorage',
                'dream_content' => 'I walked across fresh snow while green lights moved like rivers above me.',
                'overall_theme' => 'Wonder',
                'sentiment' => 'positive',
                'lat' => 61.2181,
                'lng' => -149.9003,
                'label' => 'Anchorage, US',
            ],
            [
                'title' => 'Neon Rain in Tokyo',
                'dream_content' => 'Shibuya crossing was empty except for glowing umbrellas floating at shoulder height.',
                'overall_theme' => 'Transformation',
                'sentiment' => 'neutral',
                'lat' => 35.6762,
                'lng' => 139.6503,
                'label' => 'Tokyo, JP',
            ],
            [
                'title' => 'Subway of Mirrors',
                'dream_content' => 'The train doors opened to endless reflections of people carrying the same red suitcase.',
                'overall_theme' => 'Identity',
                'sentiment' => 'negative',
                'lat' => 40.7128,
                'lng' => -74.0060,
                'label' => 'New York, US',
            ],
            [
                'title' => 'Desert Radio Signals',
                'dream_content' => 'A radio in the dunes translated wind into voices from old friends.',
                'overall_theme' => 'Connection',
                'sentiment' => 'positive',
                'lat' => 24.7136,
                'lng' => 46.6753,
                'label' => 'Riyadh, SA',
            ],
            [
                'title' => 'Canals at Dawn',
                'dream_content' => 'Bicycles moved across bridges by themselves while the sky changed from gray to gold.',
                'overall_theme' => 'Momentum',
                'sentiment' => 'positive',
                'lat' => 52.3676,
                'lng' => 4.9041,
                'label' => 'Amsterdam, NL',
            ],
            [
                'title' => 'Library Beneath the Metro',
                'dream_content' => 'Every train platform had shelves of journals written in my own handwriting.',
                'overall_theme' => 'Memory',
                'sentiment' => 'neutral',
                'lat' => 48.8566,
                'lng' => 2.3522,
                'label' => 'Paris, FR',
            ],
            [
                'title' => 'Storm on the Cape',
                'dream_content' => 'Waves lifted small fishing boats onto city streets without breaking a single window.',
                'overall_theme' => 'Uncertainty',
                'sentiment' => 'negative',
                'lat' => -33.9249,
                'lng' => 18.4241,
                'label' => 'Cape Town, ZA',
            ],
            [
                'title' => 'Paper Lantern Migration',
                'dream_content' => 'Thousands of lanterns drifted north and arranged themselves into constellations.',
                'overall_theme' => 'Guidance',
                'sentiment' => 'positive',
                'lat' => 13.7563,
                'lng' => 100.5018,
                'label' => 'Bangkok, TH',
            ],
            [
                'title' => 'Silent Carnival',
                'dream_content' => 'The carnival lights flashed in perfect rhythm with my heartbeat but no music played.',
                'overall_theme' => 'Isolation',
                'sentiment' => 'negative',
                'lat' => -34.6037,
                'lng' => -58.3816,
                'label' => 'Buenos Aires, AR',
            ],
            [
                'title' => 'Skyline of Bells',
                'dream_content' => 'Glass towers chimed softly as clouds passed through them.',
                'overall_theme' => 'Harmony',
                'sentiment' => 'positive',
                'lat' => 1.3521,
                'lng' => 103.8198,
                'label' => 'Singapore, SG',
            ],
            [
                'title' => 'Moonlit Farm Road',
                'dream_content' => 'I followed tire tracks that glowed blue and led to a field of floating seeds.',
                'overall_theme' => 'Growth',
                'sentiment' => 'positive',
                'lat' => 41.8781,
                'lng' => -87.6298,
                'label' => 'Chicago, US',
            ],
            [
                'title' => 'Harbor of Clocks',
                'dream_content' => 'Ships came in carrying clocks set to different years.',
                'overall_theme' => 'Time',
                'sentiment' => 'neutral',
                'lat' => -33.8688,
                'lng' => 151.2093,
                'label' => 'Sydney, AU',
            ],
            [
                'title' => 'Stone Steps to the Clouds',
                'dream_content' => 'A stairway from an old market climbed directly into moving fog.',
                'overall_theme' => 'Ascent',
                'sentiment' => 'positive',
                'lat' => 19.4326,
                'lng' => -99.1332,
                'label' => 'Mexico City, MX',
            ],
            [
                'title' => 'Ice Station Broadcast',
                'dream_content' => 'An abandoned station transmitted one sentence in every language at once.',
                'overall_theme' => 'Signal',
                'sentiment' => 'neutral',
                'lat' => 64.1466,
                'lng' => -21.9426,
                'label' => 'Reykjavik, IS',
            ],
            [
                'title' => 'Monsoon Theater',
                'dream_content' => 'Rain became a curtain and a crowd waited for me to step onto the stage.',
                'overall_theme' => 'Exposure',
                'sentiment' => 'negative',
                'lat' => 28.6139,
                'lng' => 77.2090,
                'label' => 'New Delhi, IN',
            ],
            [
                'title' => 'Red River Lanterns',
                'dream_content' => 'Lanterns floated down the river and each one whispered a different promise.',
                'overall_theme' => 'Hope',
                'sentiment' => 'positive',
                'lat' => 21.0278,
                'lng' => 105.8342,
                'label' => 'Hanoi, VN',
            ],
            [
                'title' => 'Midnight Tram to Nowhere',
                'dream_content' => 'The tram map kept redrawing itself and no stop names stayed the same.',
                'overall_theme' => 'Disorientation',
                'sentiment' => 'negative',
                'lat' => 50.0755,
                'lng' => 14.4378,
                'label' => 'Prague, CZ',
            ],
            [
                'title' => 'Garden on the Rooftop',
                'dream_content' => 'A rooftop greenhouse opened and butterflies escaped into city traffic.',
                'overall_theme' => 'Renewal',
                'sentiment' => 'positive',
                'lat' => 34.0522,
                'lng' => -118.2437,
                'label' => 'Los Angeles, US',
            ],
        ];
    }
}
