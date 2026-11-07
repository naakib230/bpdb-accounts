<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    protected $fillable = [
        'fiscal_year_id',
        'cost_center_id',
        'chart_of_account_id',
        'original_budget',
        'revised_budget',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'original_budget' => 'decimal:2',
        'revised_budget'  => 'decimal:2',
    ];

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getActiveBudgetAmountAttribute(): float
    {
        return (float) ($this->revised_budget > 0 ? $this->revised_budget : $this->original_budget);
    }

    public function getUtilizedAmountAttribute(): float
    {
        return (float) VoucherEntry::whereHas('voucher', function ($q) {
            $q->where('fiscal_year_id', $this->fiscal_year_id)
              ->where('status', 'posted');
        })
        ->where('chart_of_account_id', $this->chart_of_account_id)
        ->where(function ($q) {
            $q->where('cost_center_id', $this->cost_center_id)
              ->orWhereHas('voucher', fn($v) => $v->where('cost_center_id', $this->cost_center_id));
        })
        ->sum('debit_amount');
    }

    public function getRemainingBudgetAttribute(): float
    {
        return $this->active_budget_amount - $this->utilized_amount;
    }

    public function getVariancePercentageAttribute(): float
    {
        if ($this->active_budget_amount == 0) return 0.0;
        return round(($this->utilized_amount / $this->active_budget_amount) * 100, 2);
    }
}
