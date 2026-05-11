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
    public function show(Request $request, string $token)
    {
        $poll = Poll::with(['options' => function ($query) {
            $query->withCount('votes');
        }])->where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $user = $request->user();
        $payload = $poll->toArray();
        $payload['is_owner'] = $user && $user->id === $poll->user_id;
        $payload['user_has_voted'] = $user
            ? $poll->votes()->where('user_id', $user->id)->exists()
            : false;

        return $payload;
    }

    /**
     * Update an existing poll (only allowed while it is still a draft).
     */
    public function update(Request $request, int $id)
    {
        $poll = Poll::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        if (!$poll->is_draft) {
            return response()->json(['message' => 'Cannot edit a poll that has been started.'], 422);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'question' => 'sometimes|required|string|max:255',
            'allow_multiple_choices' => 'sometimes|boolean',
            'allow_vote_change' => 'sometimes|boolean',
            'results_public' => 'sometimes|boolean',
            'duration' => 'nullable|integer|min:1',
        ]);

        $poll->update($validated);

        return $poll->load('options');
    }

    /**
     * Start a draft poll: set is_draft=false, started_at=now, ends_at if duration set.
     */
    public function start(Request $request, int $id)
    {
        $poll = Poll::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        if (!$poll->is_draft) {
            return response()->json(['message' => 'Poll already started.'], 422);
        }

        $poll->update([
            'is_draft' => false,
            'started_at' => now(),
            'ends_at' => $poll->duration ? now()->addSeconds($poll->duration) : null,
        ]);

        return $poll->load('options');
    }

    /**
     * Add a new option to a draft poll.
     */
    public function addOption(Request $request, int $id)
    {
        $poll = Poll::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        if (!$poll->is_draft) {
            return response()->json(['message' => 'Cannot edit options of a started poll.'], 422);
        }

        $validated = $request->validate([
            'label' => 'required|string|max:255',
        ]);

        $option = $poll->options()->create(['label' => $validated['label']]);

        return response()->json($option, 201);
    }

    /**
     * Update an option label on a draft poll.
     */
    public function updateOption(Request $request, int $id, int $optionId)
    {
        $poll = Poll::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        if (!$poll->is_draft) {
            return response()->json(['message' => 'Cannot edit options of a started poll.'], 422);
        }

        $option = $poll->options()->where('id', $optionId)->first();

        if (!$option) {
            return response()->json(['message' => 'Option not found.'], 404);
        }

        $validated = $request->validate([
            'label' => 'required|string|max:255',
        ]);

        $option->update(['label' => $validated['label']]);

        return $option;
    }

    /**
     * Delete an option from a draft poll (must keep at least 2 options).
     */
    public function deleteOption(Request $request, int $id, int $optionId)
    {
        $poll = Poll::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        if (!$poll->is_draft) {
            return response()->json(['message' => 'Cannot edit options of a started poll.'], 422);
        }

        if ($poll->options()->count() <= 2) {
            return response()->json(['message' => 'A poll must keep at least 2 options.'], 422);
        }

        $option = $poll->options()->where('id', $optionId)->first();

        if (!$option) {
            return response()->json(['message' => 'Option not found.'], 404);
        }

        $option->delete();

        return response()->json(['message' => 'success'], 200);
    }

    /**
     * Record a vote for the authenticated user on a poll identified by its token.
     */
    public function vote(Request $request, string $token)
    {
        $poll = Poll::where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        if ($poll->is_draft) {
            return response()->json(['message' => 'Poll has not started.'], 422);
        }

        if ($poll->ends_at && $poll->ends_at->isPast()) {
            return response()->json(['message' => 'Poll has ended.'], 422);
        }

        $validated = $request->validate([
            'option_ids' => 'required|array|min:1',
            'option_ids.*' => 'integer|distinct',
        ]);

        if (!$poll->allow_multiple_choices && count($validated['option_ids']) > 1) {
            return response()->json(['message' => 'This poll only accepts a single choice.'], 422);
        }

        $validOptionIds = $poll->options()->pluck('id')->all();
        $invalid = array_diff($validated['option_ids'], $validOptionIds);

        if (!empty($invalid)) {
            return response()->json(['message' => 'Invalid option(s) submitted.'], 422);
        }

        $alreadyVoted = $poll->votes()->where('user_id', $request->user()->id)->exists();
        if ($alreadyVoted) {
            return response()->json(['message' => 'You have already voted.'], 409);
        }

        foreach ($validated['option_ids'] as $optionId) {
            $poll->votes()->create([
                'user_id' => $request->user()->id,
                'poll_option_id' => $optionId,
            ]);
        }

        return response()->json(['message' => 'Vote recorded.'], 201);
    }

    /**
     * Return live results for a poll, gated by results_public flag or ownership.
     */
    public function results(Request $request, string $token)
    {
        $poll = Poll::with(['options' => function ($query) {
            $query->withCount('votes');
        }])->where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $user = $request->user();
        $isOwner = $user && $user->id === $poll->user_id;

        if (!$poll->results_public && !$isOwner) {
            return response()->json(['message' => 'Results are not public.'], 403);
        }

        return [
            'poll_id' => $poll->id,
            'question' => $poll->question,
            'allow_multiple_choices' => $poll->allow_multiple_choices,
            'started_at' => $poll->started_at,
            'ends_at' => $poll->ends_at,
            'is_ended' => $poll->ends_at && $poll->ends_at->isPast(),
            'total_votes' => $poll->votes()->count(),
            'options' => $poll->options->map(fn ($o) => [
                'id' => $o->id,
                'label' => $o->label,
                'votes_count' => $o->votes_count,
            ]),
        ];
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
