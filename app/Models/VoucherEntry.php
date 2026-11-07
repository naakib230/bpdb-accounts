<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherEntry extends Model
{
    protected $fillable = [
        'voucher_id',
        'chart_of_account_id',
        'cost_center_id',
        'debit_amount',
        'credit_amount',
        'line_narration',
    ];

    protected $casts = [
        'debit_amount'  => 'decimal:2',
        'credit_amount' => 'decimal:2',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }
}
