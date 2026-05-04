<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

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
     * Store a newly created poll in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'question' => 'required|string|max:255',
            'is_draft' => 'sometimes|boolean',
            'started_at' => 'nullable|date|required_with:ends_at|after:now',
            'ends_at' => 'nullable|date|after:started_at',
            'options' => 'required|array|min:2|max:20',
            'options.*' => 'required|string|max:255|distinct',
        ]);

        $user = $request->user();

        $poll = DB::transaction(function () use ($validated, $user) {
            $poll = new Poll();
            $poll->title = $validated['title'] ?? null;
            $poll->question = $validated['question'];
            $poll->secret_token = $this->generateUniqueToken();
            $poll->is_draft = $validated['is_draft'] ?? true;
            $poll->allow_multiple_choices = false;
            $poll->allow_vote_change = false;
            $poll->results_public = false;
            $poll->duration = null;
            $poll->started_at = $validated['started_at'] ?? null;
            $poll->ends_at = $validated['ends_at'] ?? null;
            $poll->user()->associate($user);
            $poll->save();

            foreach ($validated['options'] as $label) {
                $poll->options()->create(['label' => $label]);
            }

            return $poll;
        });

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

    /**
     * Generate a unique token for poll sharing links.
     */
    private function generateUniqueToken(): string
    {
        do {
            $token = Str::random(32);
        } while (Poll::where('secret_token', $token)->exists());

        return $token;
    }
}
