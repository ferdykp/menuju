<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuestInvitationToken;
use App\Models\Invitation;
use App\Models\InvitationGalleryImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicInvitationController extends Controller
{
    public function cover(Request $request, Invitation $invitation)
    {
        if (! $invitation->published_at) {
            abort_unless($request->user(), 404);
            $this->authorize('view', $invitation->workspace);
        }

        abort_unless($invitation->cover_image_path && Storage::disk('local')->exists($invitation->cover_image_path), 404);

        return Storage::disk('local')->response($invitation->cover_image_path, basename($invitation->cover_image_path), [
            'Cache-Control' => $invitation->published_at ? 'public, max-age=3600' : 'private, no-store',
        ]);
    }

    public function galleryImage(Request $request, Invitation $invitation, InvitationGalleryImage $galleryImage)
    {
        abort_unless($galleryImage->invitation_id === $invitation->id, 404);
        if (! $invitation->published_at) {
            abort_unless($request->user(), 404);
            $this->authorize('view', $invitation->workspace);
        }
        abort_unless(Storage::disk('local')->exists($galleryImage->image_path), 404);

        return Storage::disk('local')->response($galleryImage->image_path, basename($galleryImage->image_path), [
            'Cache-Control' => $invitation->published_at ? 'public, max-age=3600' : 'private, no-store',
        ]);
    }

    public function show(Request $request, Invitation $invitation)
    {
        abort_unless($invitation->published_at, 404);
        $workspace = $invitation->workspace;
        $events = $workspace->events()->with('agendaItems')->orderBy('event_date')->get()->map(fn ($event) => [
            'title' => $event->title,
            'type' => $event->event_type,
            'date' => $event->event_date?->format('Y-m-d'),
            'start_time' => $event->start_time,
            'end_time' => $event->end_time,
            'venue_name' => $event->venue_name,
            'address' => $event->address,
            'agenda' => $event->agendaItems->map(fn ($item) => ['title' => $item->title, 'start_time' => $item->start_time, 'end_time' => $item->end_time, 'location' => $item->location]),
        ]);

        $guestName = null;
        $guestPlusOnes = null;
        if ($guestToken = $request->query('guest')) {
            $token = GuestInvitationToken::query()->where('invitation_id', $invitation->id)->where('token_hash', hash('sha256', $guestToken))->with('guest:id,name,plus_ones')->first();
            $guestName = $token?->guest?->name;
            $guestPlusOnes = $token?->guest?->plus_ones;
        }

        return response()->json(['invitation' => [
            'title' => $invitation->title,
            'theme_key' => $invitation->theme_key,
            'motion_preset' => $invitation->motion_preset ?: 'theme',
            'decorative_effects' => $invitation->decorative_effects ?? true,
            'cover_title' => $invitation->cover_title ?: trim($workspace->partner_one_name.' & '.$workspace->partner_two_name, ' &'),
            'cover_image_url' => $invitation->cover_image_url,
            'gallery_images' => $invitation->galleryImages()->get()->map(fn ($image) => ['url' => $image->image_url, 'alt' => $image->alt_text]),
            'wedding_date' => $workspace->wedding_date?->format('Y-m-d'),
            'timezone' => $workspace->timezone,
            'greeting' => $invitation->greeting,
            'story' => $invitation->story,
            'quote' => $invitation->quote,
            'closing' => $invitation->closing,
            'events' => $events,
            'guest_name' => $guestName,
            'guest_plus_ones' => $guestPlusOnes,
        ]]);
    }

    public function rsvp(Request $request, Invitation $invitation)
    {
        abort_unless($invitation->published_at, 404);
        $data = $request->validate([
            'guest_token' => ['required', 'string', 'min:32', 'max:100'],
            'rsvp_status' => ['required', 'in:attending,declined'],
            'plus_ones' => ['sometimes', 'integer', 'min:0', 'max:10'],
        ]);
        $token = GuestInvitationToken::query()
            ->where('invitation_id', $invitation->id)
            ->where('token_hash', hash('sha256', $data['guest_token']))
            ->with('guest:id,name,plus_ones')
            ->firstOrFail();

        $plusOnes = $data['rsvp_status'] === 'attending' ? (int) ($data['plus_ones'] ?? 0) : 0;
        abort_if($plusOnes > $token->guest->plus_ones, 422, 'Jumlah pendamping melebihi batas undangan.');
        $token->guest->update(['rsvp_status' => $data['rsvp_status'], 'rsvp_plus_ones' => $plusOnes]);

        return response()->json(['message' => 'Konfirmasi kehadiran berhasil dicatat.', 'rsvp_status' => $token->guest->rsvp_status]);
    }
}
