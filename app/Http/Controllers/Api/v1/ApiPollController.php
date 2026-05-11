<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiPollController extends Controller
{
    /**
     * Display a listing of the authenticated user's polls.
     */
    public function index(Request $request)
    {
        $polls = $request->user()->polls()->orderBy('created_at', 'desc')->get();

        return $polls;
    }

    /**
     * Store a newly created poll for the authenticated user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'question' => 'required|string|max:255',
            'options' => 'required|array|min:2',
            'options.*' => 'required|string|max:255',
            'allow_multiple_choices' => 'sometimes|boolean',
            'allow_vote_change' => 'sometimes|boolean',
            'results_public' => 'sometimes|boolean',
            'duration' => 'nullable|integer|min:1',
            'start_now' => 'sometimes|boolean',
        ]);

        $startNow = $validated['start_now'] ?? false;
        $duration = $validated['duration'] ?? null;

        $poll = Poll::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'] ?? null,
            'question' => $validated['question'],
            'secret_token' => Str::random(40),
            'is_draft' => !$startNow,
            'allow_multiple_choices' => $validated['allow_multiple_choices'] ?? false,
            'allow_vote_change' => $validated['allow_vote_change'] ?? false,
            'results_public' => $validated['results_public'] ?? false,
            'duration' => $duration,
            'started_at' => $startNow ? now() : null,
            'ends_at' => ($startNow && $duration) ? now()->addSeconds($duration) : null,
        ]);

        foreach ($validated['options'] as $label) {
            $poll->options()->create(['label' => $label]);
        }

        return response()->json($poll->load('options'), 201);
    }

    /**
     * Display the specified poll by its secret token.
     */
    public function show(string $token)
    {
        $poll = Poll::with(['options' => function ($query) {
            $query->withCount('votes');
        }])->where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        return $poll;
    }

    /**
     * Remove the specified poll.
     */
    public function remove(Request $request, int $id)
    {
        $poll = Poll::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $poll->delete();

        return response()->json(['message' => 'success'], 200);
    }
}
