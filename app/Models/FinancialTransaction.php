<?php

namespace App\Models;

use App\Models\Concerns\RecordsWorkspaceActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialTransaction extends Model
{
    use RecordsWorkspaceActivity;
    protected $fillable = ['workspace_id','created_by','account_id','destination_account_id','savings_goal_id','type','category','description','amount','occurred_at','reference','notes','voided_at'];
    protected function casts(): array { return ['amount'=>'decimal:2','occurred_at'=>'date','voided_at'=>'datetime']; }
    public function workspace(): BelongsTo { return $this->belongsTo(Workspace::class); }
    public function account(): BelongsTo { return $this->belongsTo(FinancialAccount::class,'account_id'); }
    public function destinationAccount(): BelongsTo { return $this->belongsTo(FinancialAccount::class,'destination_account_id'); }
    public function savingsGoal(): BelongsTo { return $this->belongsTo(SavingsGoal::class); }
    public function paymentRecords(): HasMany { return $this->hasMany(PaymentRecord::class); }
}
