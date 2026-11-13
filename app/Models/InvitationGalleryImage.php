<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationGalleryImage extends Model
{
    protected $fillable = ['invitation_id', 'image_path', 'alt_text', 'sort_order'];
    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): string
    {
        return '/api/public/invitations/'.$this->invitation->slug.'/gallery/'.$this->id;
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }
}
