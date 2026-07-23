<?php

namespace App\Http\Controllers;

use App\Models\TopicRecommendation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class RecommendationController extends Controller
{
    // On-demand refresh for whoever's logged in — hits GET /recommendations/refresh
    public function refresh()
    {
        $this->updateRecommendations(Auth::id());

        return back()->with('success', 'Recommendations updated!');
    }

    // Calls the Flask ML service and refreshes one user's recommendations.
    // Used both by refresh() above (one user, on demand) and by the
    // recommendations:generate console command (every user, scheduled).
    public function updateRecommendations($userId)
    {
        try {
            $response = Http::timeout(5)->get("http://localhost:5001/recommend/{$userId}");
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Flask isn't running — leave existing recommendations alone
            // rather than wiping them out with nothing
            return;
        }

        if (! $response->successful()) {
            return;
        }

        $recommendations = $response->json('recommendations') ?? [];

        // Clear old recommendations for this user
        TopicRecommendation::where('user_id', $userId)->delete();

        // Save new ones
        foreach ($recommendations as $rec) {
            TopicRecommendation::create([
                'user_id'      => $userId,
                'topic_id'     => $rec['topic_id'],
                'score'        => $rec['score'],
                'generated_at' => now(),
                'is_dismissed' => false,
            ]);
        }
    }
}
