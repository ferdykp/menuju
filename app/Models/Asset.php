<?php

namespace App\Models;

use App\Models\Concerns\RecordsWorkspaceActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use SoftDeletes;
    use RecordsWorkspaceActivity;

    protected $fillable = ['workspace_id', 'created_by', 'name', 'asset_type', 'owner_name', 'quantity', 'unit', 'purity', 'estimated_value', 'valuation_date', 'acquired_at', 'status', 'notes'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'purity' => 'decimal:3', 'estimated_value' => 'decimal:2', 'valuation_date' => 'date', 'acquired_at' => 'date'];
    }

    public function workspace(): BelongsTo { return $this->belongsTo(Workspace::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function allocations(): HasMany { return $this->hasMany(AssetAllocation::class); }
    public function valuations(): HasMany { return $this->hasMany(AssetValuation::class)->orderByDesc('valuation_date')->orderByDesc('id'); }
    public function needs(): BelongsToMany { return $this->belongsToMany(Need::class, 'asset_allocations')->withPivot(['id', 'quantity', 'notes'])->withTimestamps(); }
}
