<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetCategory extends Model
{
    protected $fillable = ['workspace_id','event_id','name','planned_amount','color','notes'];
    protected function casts(): array { return ['planned_amount'=>'decimal:2']; }
    public function workspace(): BelongsTo { return $this->belongsTo(Workspace::class); }
    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
    public function obligations(): HasMany { return $this->hasMany(PaymentObligation::class); }
    public function needs(): HasMany { return $this->hasMany(Need::class); }
}
