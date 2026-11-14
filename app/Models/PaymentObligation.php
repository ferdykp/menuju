<?php

namespace App\Models;

use App\Models\Concerns\RecordsWorkspaceActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentObligation extends Model
{
    use SoftDeletes;
    use RecordsWorkspaceActivity;
    protected $fillable = ['workspace_id','budget_category_id','event_id','title','vendor_name','total_amount','due_at','status','notes'];
    protected function casts(): array { return ['total_amount'=>'decimal:2','due_at'=>'date']; }
    public function workspace(): BelongsTo { return $this->belongsTo(Workspace::class); }
    public function budgetCategory(): BelongsTo { return $this->belongsTo(BudgetCategory::class); }
    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
    public function records(): HasMany { return $this->hasMany(PaymentRecord::class); }
}
