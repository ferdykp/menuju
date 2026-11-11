<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GuestController extends Controller
{
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);
        $query = $workspace->guests();

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(fn ($builder) => $builder->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('group_name', 'like', "%{$search}%"));
        }
        foreach (['side', 'rsvp_status', 'invitation_status', 'group_name'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->query($filter));
            }
        }

        $guests = $query->orderBy('name')->paginate(min(max((int) $request->query('per_page', 30), 1), 100))->withQueryString();
        $base = $workspace->guests();

        return response()->json([
            'data' => $guests->items(),
            'meta' => [
                'current_page' => $guests->currentPage(), 'last_page' => $guests->lastPage(),
                'per_page' => $guests->perPage(), 'total' => $guests->total(),
                'summary' => [
                    'total' => $base->count(),
                    'attending' => (clone $base)->where('rsvp_status', 'attending')->sum(DB::raw('1 + plus_ones')),
                    'pending' => (clone $base)->where('rsvp_status', 'pending')->count(),
                    'declined' => (clone $base)->where('rsvp_status', 'declined')->count(),
                ],
            ],
        ]);
    }

    public function store(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);
        $data = $this->validated($request);
        $this->ensureUniqueEmail($workspace, $data['email'] ?? null);
        if (isset($data['email'])) {
            $data['email'] = mb_strtolower(trim($data['email']));
        }
        $guest = $workspace->guests()->create($data + [
            'created_by' => $request->user()->id,
            'side' => 'shared',
            'invitation_status' => 'not_sent',
            'rsvp_status' => 'pending',
            'plus_ones' => 0,
        ]);

        return response()->json(['guest' => $guest], 201);
    }

    public function show(Guest $guest): JsonResponse
    {
        $this->authorize('view', $guest);

        return response()->json(['guest' => $guest]);
    }

    public function update(Request $request, Guest $guest): JsonResponse
    {
        $this->authorize('update', $guest);
        $data = $this->validated($request, true);
        $email = array_key_exists('email', $data) ? $data['email'] : $guest->email;
        $this->ensureUniqueEmail($guest->workspace, $email, $guest->id);
        if (isset($data['email'])) {
            $data['email'] = mb_strtolower(trim($data['email']));
        }
        $guest->update($data);

        return response()->json(['guest' => $guest->fresh()]);
    }

    public function destroy(Guest $guest): JsonResponse
    {
        $this->authorize('delete', $guest);
        $guest->delete();

        return response()->json(['message' => 'Tamu dihapus.']);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:160'],
            'email' => [$partial ? 'sometimes' : 'nullable', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'group_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'side' => ['sometimes', Rule::in(['bride', 'groom', 'shared'])],
            'invitation_status' => ['sometimes', Rule::in(['not_sent', 'sent'])],
            'rsvp_status' => ['sometimes', Rule::in(['pending', 'attending', 'declined'])],
            'plus_ones' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);
    }

    private function ensureUniqueEmail(Workspace $workspace, ?string $email, ?int $except = null): void
    {
        if (! $email) {
            return;
        }
        $query = $workspace->guests()->whereRaw('lower(email) = ?', [mb_strtolower(trim($email))]);
        if ($except) {
            $query->whereKeyNot($except);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages(['email' => 'Email ini sudah tercatat dalam daftar tamu workspace ini.']);
        }
    }
}
