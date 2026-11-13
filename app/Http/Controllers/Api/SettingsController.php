<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)],
        ]);
        $request->user()->fill($data)->save();

        return response()->json(['user' => $request->user()->only('id', 'name', 'email')]);
    }

    public function updateWorkspace(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('update', $workspace);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'partner_one_name' => ['required', 'string', 'max:100'],
            'partner_two_name' => ['required', 'string', 'max:100'],
            'wedding_date' => ['nullable', 'date'],
            'timezone' => ['required', 'timezone'],
        ]);
        $workspace->fill($data)->save();

        return response()->json(['workspace' => [
            'id' => $workspace->id, 'name' => $workspace->name,
            'partner_one_name' => $workspace->partner_one_name, 'partner_two_name' => $workspace->partner_two_name,
            'wedding_date' => $workspace->wedding_date?->toDateString(), 'timezone' => $workspace->timezone,
            'onboarding_completed_at' => $workspace->onboarding_completed_at?->toIso8601String(),
        ]]);
    }

    public function revokePartner(Request $request, Workspace $workspace, int $member): JsonResponse
    {
        $this->authorize('update', $workspace);
        $pivot = $workspace->members()->where('users.id', $member)->first()?->pivot;
        if (! $pivot || $pivot->role !== 'partner') {
            throw ValidationException::withMessages(['member' => 'Partner aktif tidak ditemukan.']);
        }
        $workspace->members()->updateExistingPivot($member, ['status' => 'revoked']);

        return response()->json(['message' => 'Akses partner telah dicabut.']);
    }

    public function cancelInvitation(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('update', $workspace);
        $deleted = $workspace->invitations()->whereNull('accepted_at')->delete();
        return response()->json(['cancelled' => $deleted > 0]);
    }
}
