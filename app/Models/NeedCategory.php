<?php

namespace App\Models;

use App\Models\Concerns\RecordsWorkspaceActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NeedCategory extends Model
{
    use RecordsWorkspaceActivity;
    protected $fillable = ['workspace_id', 'name'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function needs(): HasMany
    {
        return $this->hasMany(Need::class);
    }
}
