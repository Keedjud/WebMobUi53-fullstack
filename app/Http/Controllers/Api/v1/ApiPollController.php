<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApiPollController extends Controller
{
    /**
     * Display a listing of the authenticated user's polls.
     */
    public function index(Request $request)
    {
        $polls = $request->user()->polls()->orderBy('created_at', 'desc')->get();

        foreach ($polls as $poll) {
            $this->appendOwnerFields($poll);
            $this->appendStatusFlags($poll, $request->user()->id);
        }

        return $polls;
    }

    /**
     * Display a listing of public active polls.
     */
    public function publicIndex()
    {
        $now = now();

        $polls = Poll::with('user')
            ->where('is_draft', false)
            ->where(function ($query) use ($now) {
                $query->whereNull('started_at')->orWhere('started_at', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('ends_at', 'asc')
            ->get();

        foreach ($polls as $poll) {
            $this->appendPublicFields($poll);
            $this->appendStatusFlags($poll, null);
        }

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
            'allow_multiple_choices' => 'sometimes|boolean',
            'results_public' => 'sometimes|boolean',
            'allow_vote_change' => 'sometimes|boolean',
            'started_at' => 'nullable|date|required_with:ends_at|after_or_equal:now',
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
            $poll->allow_multiple_choices = $validated['allow_multiple_choices'] ?? false;
            $poll->allow_vote_change = $validated['allow_vote_change'] ?? false;
            $poll->results_public = $validated['results_public'] ?? false;
            $poll->started_at = $validated['started_at'] ?? null;
            $poll->ends_at = $validated['ends_at'] ?? null;
            $poll->user()->associate($user);
            $poll->save();

            foreach ($validated['options'] as $label) {
                $option = new PollOption();
                $option->label = $label;
                $poll->options()->save($option);
            }

            return $poll;
        });

        $poll->load('options');
        $this->appendOwnerFields($poll);
        $this->appendStatusFlags($poll, $user->id);

        return response()->json($poll, 201);
    }

    /**
     * Display the specified poll by its secret token.
     */
    public function show(Request $request, string $token)
    {
        $poll = Poll::with(['options', 'user'])->where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $user = $request->user();

        $this->appendPublicFields($poll);
        $this->appendStatusFlags($poll, $user ? $user->id : null);

        if ($user) {
            $optionIds = PollVote::where('poll_id', $poll->id)
                ->where('user_id', $user->id)
                ->pluck('poll_option_id')
                ->all();
            $poll->setAttribute('user_option_ids', $optionIds);
        }

        return $poll;
    }

    /**
     * Update the specified poll.
     */
    public function update(Request $request, int $id)
    {
        $poll = Poll::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        if (!$poll->is_draft) {
            return response()->json(['message' => 'Poll is not editable once published.'], 403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'question' => 'required|string|max:255',
            'is_draft' => 'required|boolean',
            'allow_multiple_choices' => 'required|boolean',
            'results_public' => 'required|boolean',
            'allow_vote_change' => 'required|boolean',
            'started_at' => 'nullable|date|required_with:ends_at|after_or_equal:now',
            'ends_at' => 'nullable|date|after:started_at',
            'options_create' => 'sometimes|array',
            'options_create.*.label' => 'required|string|max:255',
            'options_update' => 'sometimes|array',
            'options_update.*.id' => 'required|integer|exists:poll_options,id',
            'options_update.*.label' => 'required|string|max:255',
            'options_delete' => 'sometimes|array',
            'options_delete.*' => 'integer|exists:poll_options,id',
        ]);

        $optionsCreate = $validated['options_create'] ?? [];
        $optionsUpdate = $validated['options_update'] ?? [];
        $optionsDelete = $validated['options_delete'] ?? [];

        $updateIds = array_values(array_map(fn ($item) => $item['id'], $optionsUpdate));
        $deleteIds = array_values($optionsDelete);
        $overlap = array_intersect($updateIds, $deleteIds);

        if (count($overlap) > 0) {
            return response()->json([
                'message' => 'Invalid options payload.',
                'errors' => ['options' => ['Cannot update and delete the same option.']],
            ], 422);
        }

        $existingOptions = $poll->options()->pluck('label', 'id')->all();
        $existingIds = array_keys($existingOptions);

        if (count(array_diff($updateIds, $existingIds)) > 0 || count(array_diff($deleteIds, $existingIds)) > 0) {
            return response()->json([
                'message' => 'Invalid options payload.',
                'errors' => ['options' => ['Options must belong to the poll.']],
            ], 422);
        }

        $finalCount = count($existingIds) - count($deleteIds) + count($optionsCreate);
        if ($finalCount < 2 || $finalCount > 20) {
            return response()->json([
                'message' => 'Invalid options payload.',
                'errors' => ['options' => ['A poll must have between 2 and 20 options.']],
            ], 422);
        }

        foreach ($deleteIds as $optionId) {
            unset($existingOptions[$optionId]);
        }

        foreach ($optionsUpdate as $item) {
            $existingOptions[$item['id']] = $item['label'];
        }

        $finalLabels = array_values($existingOptions);
        foreach ($optionsCreate as $item) {
            $finalLabels[] = $item['label'];
        }

        if (count(array_unique($finalLabels)) !== count($finalLabels)) {
            return response()->json([
                'message' => 'Invalid options payload.',
                'errors' => ['options' => ['Option labels must be unique.']],
            ], 422);
        }

        DB::transaction(function () use ($poll, $validated, $optionsCreate, $optionsUpdate, $deleteIds) {
            $poll->title = $validated['title'] ?? null;
            $poll->question = $validated['question'];
            $poll->is_draft = $validated['is_draft'];
            $poll->allow_multiple_choices = $validated['allow_multiple_choices'];
            $poll->results_public = $validated['results_public'];
            $poll->allow_vote_change = $validated['allow_vote_change'];
            $poll->started_at = $validated['started_at'] ?? null;
            $poll->ends_at = $validated['ends_at'] ?? null;
            $poll->save();

            if (count($deleteIds) > 0) {
                $poll->options()->whereIn('id', $deleteIds)->delete();
            }

            foreach ($optionsUpdate as $item) {
                $poll->options()->where('id', $item['id'])->update(['label' => $item['label']]);
            }

            foreach ($optionsCreate as $item) {
                $option = new PollOption();
                $option->label = $item['label'];
                $poll->options()->save($option);
            }
        });

        $poll->load('options');
        $this->appendOwnerFields($poll);
        $this->appendStatusFlags($poll, $request->user()->id);

        return response()->json($poll, 200);
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
     * Submit a vote for a poll.
     */
    public function vote(Request $request, string $token)
    {
        $poll = Poll::where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $flags = $this->computeStatusFlags($poll);
        if ($poll->is_draft || !$flags['is_active']) {
            return response()->json(['message' => 'Poll is not open for voting.'], 403);
        }

        $validated = $request->validate([
            'option_ids' => 'required|array|min:1',
            'option_ids.*' => 'required|integer',
        ]);

        $optionIds = array_values(array_unique($validated['option_ids']));

        if (!$poll->allow_multiple_choices && count($optionIds) !== 1) {
            return response()->json([
                'message' => 'Invalid vote payload.',
                'errors' => ['option_ids' => ['Only one option is allowed.']],
            ], 422);
        }

        $existingOptionIds = $poll->options()->whereIn('id', $optionIds)->pluck('id')->all();
        if (count($existingOptionIds) !== count($optionIds)) {
            return response()->json([
                'message' => 'Invalid vote payload.',
                'errors' => ['option_ids' => ['Options must belong to the poll.']],
            ], 422);
        }

        $user = $request->user();
        $hasVoted = PollVote::where('poll_id', $poll->id)->where('user_id', $user->id)->exists();

        if ($hasVoted && !$poll->allow_vote_change) {
            return response()->json(['message' => 'Vote already submitted.'], 409);
        }

        DB::transaction(function () use ($poll, $user, $optionIds) {
            PollVote::where('poll_id', $poll->id)->where('user_id', $user->id)->delete();

            foreach ($optionIds as $optionId) {
                $vote = new PollVote();
                $vote->poll_id = $poll->id;
                $vote->user_id = $user->id;
                $vote->poll_option_id = $optionId;
                $vote->save();
            }
        });

        return response()->json(['message' => 'ok'], 200);
    }

    /**
     * Get poll results by token.
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

        $this->appendStatusFlags($poll, $user ? $user->id : null);
        $poll->setAttribute('total_votes', $poll->options->sum('votes_count'));
        $poll->makeHidden(['secret_token']);

        return $poll;
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

    private function shareUrl(Poll $poll): string
    {
        return url('/polls/' . $poll->secret_token);
    }

    private function appendOwnerFields(Poll $poll): void
    {
        $poll->setAttribute('share_url', $this->shareUrl($poll));
        $poll->makeHidden(['secret_token']);
    }

    private function appendPublicFields(Poll $poll): void
    {
        $poll->setAttribute('share_url', $this->shareUrl($poll));
        $poll->makeHidden(['secret_token']);

        if ($poll->relationLoaded('user') && $poll->user) {
            $authorName = trim(sprintf('%s %s', $poll->user->first_name, $poll->user->last_name));
            $poll->setAttribute('author_name', $authorName !== '' ? $authorName : $poll->user->username);
            $poll->setAttribute('author_username', $poll->user->username);
        }

        $poll->makeHidden(['user']);
    }

    private function appendStatusFlags(Poll $poll, ?int $userId): void
    {
        $flags = $this->computeStatusFlags($poll);
        $poll->setAttribute('is_active', $flags['is_active']);
        $poll->setAttribute('is_ended', $flags['is_ended']);
        $poll->setAttribute('can_vote', $userId ? $flags['is_active'] : false);
    }

    private function computeStatusFlags(Poll $poll): array
    {
        $now = now();
        $started = $poll->started_at ? $poll->started_at->lte($now) : true;
        $ended = $poll->ends_at ? $poll->ends_at->lt($now) : false;
        $isActive = !$poll->is_draft && $started && !$ended;

        return [
            'is_active' => $isActive,
            'is_ended' => $ended,
        ];
    }
}
