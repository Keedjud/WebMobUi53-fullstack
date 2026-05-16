# Implementation Plan (Poll App)

Purpose
- Provide a single source of truth for what must be implemented and in what order.
- Keep context aligned with [TP.md](TP.md) and the frontend integration notes in [README_FRONT.md](README_FRONT.md).

Technical Stack (per TP)
- Backend: Laravel 12+ (PHP)
- Frontend: Vue 3.4+ (Vite)
- Auth: Laravel Sanctum (session cookie for SPA)
- DB: Relational (SQLite/MySQL/PostgreSQL)
- Styling: Tailwind via Vite
- API: JSON, versioned under /api/v1

Current State Summary (high level)
- Dashboard SPA exists for listing polls, creating polls, deleting polls.
- Poll edit UI is a placeholder.
- API: list polls, create poll, show poll by token, delete poll.
- No vote endpoint, no update endpoint, no results polling endpoint.
- Poll models and migrations exist (polls, poll_options, poll_votes).

Architecture Decision
- Two SPAs:
  1) Authenticated dashboard SPA for poll management (create/edit/delete).
  2) Public poll SPA for voting + live results view.
- Blade hosts each SPA and injects one Vite entrypoint per page.

Phase 0 Decisions (locked)
- Timing: use started_at + ends_at only (duration is not used in the UI/API).
- Results visibility: owner always sees results; everyone else only if results_public is true.
- Vote change: implement now, gated by allow_vote_change.
- Public poll page route: /polls/{token}.
- Results polling endpoint: GET /api/v1/polls/{token}/results.
- Share link: API returns share_url for owner endpoints.
- Options update: CRUD fine (add/update/remove by id).
- Edit scope: only when poll is draft (is_draft=true).

Plan (ordered)

Phase 0 - Contract and data model alignment
- Define API contracts for:
  - Poll CRUD (index, show, create, update, delete)
  - Poll vote (submit, optional update if allow_vote_change)
  - Poll results (public or owner-only)
- Define a shared poll state model used by both SPAs:
  - is_draft, allow_multiple_choices, results_public, allow_vote_change
  - started_at, ends_at
  - options with vote counts

Phase 0 Output (API contracts + shared model)

Shared Poll Model (base fields)
- id, title, question
- share_url (owner endpoints only)
- is_draft, allow_multiple_choices, results_public, allow_vote_change
- started_at, ends_at
- created_at, updated_at

Option Model (base fields)
- id, label
- votes_count (only when results are requested)

Computed Flags (API convenience)
- is_active: !is_draft and (started_at is null or started_at <= now) and (ends_at is null or now <= ends_at)
- is_ended: ends_at is not null and now > ends_at
- can_vote: auth and is_active and not is_ended

API Contracts (v1)

GET /api/v1/polls (auth, owner list)
- Response: array of polls (no vote counts), includes share_url

POST /api/v1/polls (auth)
- Request body:
  {
    title: string|null,
    question: string,
    is_draft: boolean,
    allow_multiple_choices: boolean,
    results_public: boolean,
    allow_vote_change: boolean,
    started_at: string|null (ISO datetime),
    ends_at: string|null (ISO datetime),
    options: string[] (min 2, max 20)
  }
- Response: created poll with options + share_url

PATCH /api/v1/polls/{id} (auth, owner)
- Same body as POST but options are edited via CRUD fine by id:
  - options_create: [{ label }]
  - options_update: [{ id, label }]
  - options_delete: [id]
- Response: updated poll with options + share_url

DELETE /api/v1/polls/{id} (auth, owner)
- Response: { message: "success" }

GET /api/v1/polls/{token} (public)
- Response: poll with options (no vote counts), plus flags (is_active, is_ended)

POST /api/v1/polls/{token}/votes (auth)
- Request body:
  { option_ids: number[] }
- Rules:
  - if allow_multiple_choices=false then only one id allowed
  - if allow_vote_change=false then user cannot vote twice
- Response: { message: "ok" }

GET /api/v1/polls/{token}/results (public if results_public or owner)
- Response: poll + options with votes_count, total_votes, is_ended

Phase 1 - Backend endpoints and rules
- Add update endpoint for polls (PATCH/PUT)
- Add endpoint(s) for voting
- Add endpoint(s) for results polling (return counts only)
- Enforce access rules:
  - Only owner can edit/delete
  - Vote requires auth
  - Public results visible only if results_public is true
  - If end date passed, voting blocked and reported in API
- Enforce uniqueness rules:
  - For single-choice, one vote per user per poll
  - For multiple-choice, ensure unique options per user

Phase 2 - Dashboard SPA completion
- Replace edit placeholder with full edit form (reuse create form logic)
- Add settings fields:
  - allow_multiple_choices
  - results_public
  - allow_vote_change (bonus)
  - started_at, ends_at or duration
  - is_draft / publish now
- Add link copy for share token
- Improve error handling and validation display

Phase 3 - Public vote SPA
- Create a public poll page (Blade + Vue entrypoint)
- Fetch poll by token and show:
  - question, options, settings, status
  - vote form (single/multiple)
  - results section
- Implement polling for results (usePolling)
- Render a simple chart (bar or donut)
- Show clear "poll ended" state when voting is closed

Phase 4 - UX and robustness
- Consistent loading/error states
- Mobile-first layout and clear actions
- API errors surfaced to user
- Defensive UI for forbidden / not found / ended

Phase 5 - Documentation
- Update README with setup, routes, and usage
- Add a short "how to test" section

API Design Checklist (proposed)
- GET /api/v1/polls (auth)
- POST /api/v1/polls (auth)
- PATCH /api/v1/polls/{id} (auth, owner)
- DELETE /api/v1/polls/{id} (auth, owner)
- GET /api/v1/polls/{token} (public)
- POST /api/v1/polls/{token}/votes (auth)
- GET /api/v1/polls/{token}/results (public only if results_public)

Frontend Entry Points (planned)
- resources/js/poll-dashboard.js (auth dashboard)
- resources/js/poll-vote.js (public vote/results)

Definition of Done
- All required TP features implemented and demoable.
- API and UI behavior consistent across dashboard and public vote page.
- Live results polling works, with a visible chart.
- README updated with install/run instructions.

Notes
- Keep all API base URLs under /api/v1 to match bootstrap.js.
- Use shared composables (useFetchApi, usePolling) across SPAs.
