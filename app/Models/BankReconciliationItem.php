<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliationItem extends Model
{
    protected $fillable = [
        'bank_reconciliation_id',
        'schedule',
        'type',
        'item_date',
        'reference_no',
        'description',
        'amount',
        'effect',
    ];

    protected $casts = [
        'item_date' => 'date',
        'amount'    => 'decimal:2',
    ];

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'bank_reconciliation_id');
    }
}
