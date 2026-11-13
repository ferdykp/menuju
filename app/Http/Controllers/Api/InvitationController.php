<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\GuestInvitationToken;
use App\Models\Invitation;
use App\Models\InvitationGalleryImage;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InvitationController extends Controller
{
    public function index(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);
        $invitation = $workspace->invitation?->load('galleryImages');

        return response()->json(['invitation' => $invitation]);
    }

    public function store(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);
        if ($workspace->invitation()->exists()) return response()->json(['message' => 'Workspace ini sudah memiliki undangan.'], 409);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'], 'theme_key' => ['required', Rule::in(['botanical', 'classic', 'modern', 'terracotta'])],
        ]);
        $invitation = $workspace->invitation()->create($data + [
            'created_by' => $request->user()->id,
            'slug' => Str::lower(Str::random(12)),
            'motion_preset' => 'theme',
            'decorative_effects' => true,
            'cover_title' => $workspace->partner_one_name.' & '.$workspace->partner_two_name,
            'greeting' => 'Dengan penuh rasa syukur, kami mengundang Anda untuk merayakan hari istimewa kami.',
            'closing' => 'Merupakan kebahagiaan bagi kami apabila Anda berkenan hadir.',
        ]);

        return response()->json(['invitation' => $invitation], 201);
    }

    public function update(Request $request, Workspace $workspace, Invitation $invitation)
    {
        $this->authorize('view', $workspace);
        abort_unless($invitation->workspace_id === $workspace->id, 404);
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:160'],
            'theme_key' => ['sometimes', Rule::in(['botanical', 'classic', 'modern', 'terracotta'])],
            'motion_preset' => ['sometimes', Rule::in(['theme', 'editorial', 'garden', 'cinematic', 'minimal'])],
            'decorative_effects' => ['sometimes', 'boolean'],
            'cover_title' => ['sometimes', 'nullable', 'string', 'max:160'],
            'greeting' => ['sometimes', 'nullable', 'string', 'max:240'],
            'story' => ['sometimes', 'nullable', 'string', 'max:3000'],
            'quote' => ['sometimes', 'nullable', 'string', 'max:300'],
            'closing' => ['sometimes', 'nullable', 'string', 'max:240'],
        ]);
        $invitation->update($data);

        return response()->json(['invitation' => $invitation->fresh()]);
    }

    public function uploadCover(Request $request, Workspace $workspace, Invitation $invitation)
    {
        $this->authorize('view', $workspace);
        abort_unless($invitation->workspace_id === $workspace->id, 404);
        $data = $request->validate([
            'cover_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:min_width=480,min_height=640,max_width=8000,max_height=10000'],
        ]);

        $oldPath = $invitation->cover_image_path;
        $path = $data['cover_image']->store("invitations/{$workspace->id}", 'local');
        $invitation->update(['cover_image_path' => $path]);
        if ($oldPath) Storage::disk('local')->delete($oldPath);

        return response()->json(['invitation' => $invitation->fresh()]);
    }

    public function removeCover(Request $request, Workspace $workspace, Invitation $invitation)
    {
        $this->authorize('view', $workspace);
        abort_unless($invitation->workspace_id === $workspace->id, 404);
        $path = $invitation->cover_image_path;
        $invitation->update(['cover_image_path' => null]);
        if ($path) Storage::disk('local')->delete($path);

        return response()->json(['invitation' => $invitation->fresh()]);
    }

    public function uploadGallery(Request $request, Workspace $workspace, Invitation $invitation)
    {
        $this->authorize('view', $workspace);
        abort_unless($invitation->workspace_id === $workspace->id, 404);
        $data = $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:8'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:min_width=480,min_height=640,max_width=8000,max_height=10000'],
        ]);
        $currentCount = $invitation->galleryImages()->count();
        abort_if($currentCount + count($data['images']) > 12, 422, 'Galeri maksimal berisi 12 foto.');

        foreach ($data['images'] as $index => $image) {
            $path = $image->store("invitations/{$workspace->id}/gallery", 'local');
            $invitation->galleryImages()->create([
                'image_path' => $path,
                'alt_text' => 'Foto cerita pasangan',
                'sort_order' => $currentCount + $index,
            ]);
        }

        return response()->json(['images' => $invitation->galleryImages()->get()], 201);
    }

    public function removeGalleryImage(Request $request, Workspace $workspace, Invitation $invitation, InvitationGalleryImage $galleryImage)
    {
        $this->authorize('view', $workspace);
        abort_unless($invitation->workspace_id === $workspace->id && $galleryImage->invitation_id === $invitation->id, 404);
        Storage::disk('local')->delete($galleryImage->image_path);
        $galleryImage->delete();
        $invitation->galleryImages()->orderBy('id')->get()->each(fn ($image, $index) => $image->update(['sort_order' => $index]));

        return response()->json(['images' => $invitation->galleryImages()->get()]);
    }

    public function publish(Request $request, Workspace $workspace, Invitation $invitation)
    {
        $this->authorize('view', $workspace);
        abort_unless($invitation->workspace_id === $workspace->id, 404);
        $request->validate(['published' => ['required', 'boolean']]);
        $invitation->update(['published_at' => $request->boolean('published') ? ($invitation->published_at ?? now()) : null]);

        return response()->json(['invitation' => $invitation->fresh()]);
    }

    public function guestLink(Request $request, Invitation $invitation, Guest $guest)
    {
        $this->authorize('view', $invitation->workspace);
        abort_unless($invitation->published_at, 409, 'Terbitkan undangan sebelum membuat tautan RSVP.');
        abort_unless($guest->workspace_id === $invitation->workspace_id, 404);
        $token = Str::random(48);
        GuestInvitationToken::updateOrCreate(
            ['invitation_id' => $invitation->id, 'guest_id' => $guest->id],
            ['token_hash' => hash('sha256', $token)],
        );

        return response()->json(['url' => url('/w/'.$invitation->slug.'?guest='.$token)]);
    }
}
