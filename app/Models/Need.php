<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Need extends Model
{
    use SoftDeletes;

    protected $fillable = ['workspace_id', 'event_id', 'created_by', 'title', 'category', 'need_category_id', 'vendor_name', 'budget_category', 'budget_category_id', 'quantity', 'estimated_amount', 'purchased_amount', 'needed_by', 'purchased_at', 'status', 'priority', 'notes'];
    protected function casts(): array { return ['quantity' => 'integer', 'estimated_amount' => 'decimal:2', 'purchased_amount' => 'decimal:2', 'needed_by' => 'date', 'purchased_at' => 'date']; }
    public function workspace(): BelongsTo { return $this->belongsTo(Workspace::class); }
    public function needCategory(): BelongsTo { return $this->belongsTo(NeedCategory::class); }
    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
    public function budgetCategory(): BelongsTo { return $this->belongsTo(BudgetCategory::class); }
}
