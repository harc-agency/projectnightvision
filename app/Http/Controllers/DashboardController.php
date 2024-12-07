<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $dreams = $user->dreams; // Assuming the User model has a 'dreams' relationship defined

        // Group dreams by 'overall_theme'
        $themes = $dreams->groupBy('overall_theme');

        // Calculate average intensity and sentiment per theme
        $radialData = $themes->map(function ($themeDreams, $themeName) {
            // Convert sentiment into numerical values
            $sentimentValues = $themeDreams->pluck('sentiment')->map(function ($sentiment) {
                if ($sentiment === 'Positive') return 1;
                if ($sentiment === 'Neutral') return 0;
                if ($sentiment === 'Negative') return -1;
                return 0; // Default if sentiment is missing
            });

            return [
                'theme' => $themeName,
                'average_intensity' => $themeDreams->avg('intensity'),
                'average_sentiment' => $sentimentValues->average(),
            ];
        });

        // Prepare radial chart data
        $radialChartData = [
            'labels' => $radialData->pluck('theme'), // Theme names as labels
            'intensityData' => $radialData->pluck('average_intensity'), // Average intensities
            'sentimentData' => $radialData->pluck('average_sentiment'), // Average sentiments
        ];

        // dd($radialChartData);

        

        return Inertia::render('Dashboard', [
            'radialChartData' => $radialChartData,
            'dreamsCount' => $dreams->count(), // Optional: Add additional metrics
        ]);
    }
}
