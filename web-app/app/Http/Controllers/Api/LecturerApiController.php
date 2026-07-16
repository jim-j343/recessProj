<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupMembership;
use App\Models\Quiz;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LecturerApiController extends Controller
{
    /**
     * GET /api/lecturer/dashboard
     * Returns stats and recent quizzes for the lecturer dashboard,
     * mirroring the logic in the web route closure.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $userId = $request->user()->user_id;

        $quizzes = Quiz::where('lecturer_id', $userId)->latest()->get();
        $quizCount = $quizzes->count();
        $groupCount = GroupMembership::where('user_id', $userId)->where('status', 'active')->count();
        $topicCount = Topic::where('creator_id', $userId)->count();

        // Map quizzes to the same shape as QuizApiController
        $mappedQuizzes = $quizzes->map(fn($q) => [
            'quiz_id'          => (int) $q->quiz_id,
            'title'            => $q->title,
            'group_id'         => (int) $q->group_id,
            'start_time'       => $q->start_time?->toIso8601String(),
            'duration_minutes' => $q->duration_minutes,
            'is_published'     => (bool) $q->is_published,
            'target_category'  => $q->target_category,
        ]);

        return response()->json([
            'quiz_count'  => $quizCount,
            'group_count' => $groupCount,
            'topic_count' => $topicCount,
            'quizzes'     => $mappedQuizzes,
        ]);
    }
}
