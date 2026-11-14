<?php

namespace App\Models;

use App\Models\Concerns\RecordsWorkspaceActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invitation extends Model
{
    use RecordsWorkspaceActivity;
    protected $fillable = ['workspace_id', 'created_by', 'title', 'slug', 'theme_key', 'motion_preset', 'decorative_effects', 'cover_title', 'cover_image_path', 'greeting', 'story', 'quote', 'closing', 'published_at'];
    protected $appends = ['cover_image_url'];
    protected function casts(): array { return ['published_at' => 'datetime', 'decorative_effects' => 'boolean']; }
    public function getCoverImageUrlAttribute(): ?string { return $this->cover_image_path ? '/api/public/invitations/'.$this->slug.'/cover?v='.$this->updated_at?->timestamp : null; }
    public function workspace(): BelongsTo { return $this->belongsTo(Workspace::class); }
    public function guestTokens(): HasMany { return $this->hasMany(GuestInvitationToken::class); }
    public function galleryImages(): HasMany { return $this->hasMany(InvitationGalleryImage::class)->orderBy('sort_order')->orderBy('id'); }
}
