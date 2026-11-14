<?php

namespace App\Models;

use App\Models\Concerns\RecordsWorkspaceActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Need extends Model
{
    use SoftDeletes;
    use RecordsWorkspaceActivity;

    protected $fillable = ['workspace_id', 'event_id', 'created_by', 'assignee_id', 'title', 'category', 'need_category_id', 'vendor_name', 'budget_category', 'budget_category_id', 'quantity', 'estimated_amount', 'purchased_amount', 'needed_by', 'purchased_at', 'status', 'priority', 'notes'];
    protected function casts(): array { return ['quantity' => 'integer', 'estimated_amount' => 'decimal:2', 'purchased_amount' => 'decimal:2', 'needed_by' => 'date', 'purchased_at' => 'date']; }
    public function workspace(): BelongsTo { return $this->belongsTo(Workspace::class); }
    public function needCategory(): BelongsTo { return $this->belongsTo(NeedCategory::class); }
    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assignee_id'); }
    public function budgetCategory(): BelongsTo { return $this->belongsTo(BudgetCategory::class); }
    public function assets(): BelongsToMany { return $this->belongsToMany(Asset::class, 'asset_allocations')->withPivot(['id', 'quantity', 'notes'])->withTimestamps(); }
}
