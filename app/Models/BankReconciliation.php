<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankReconciliation extends Model
{
    protected $fillable = [
        'bank_account_id',
        'statement_date',
        'bank_statement_balance',
        'cash_book_balance',
        'reconciled_balance',
        'difference',
        'status',
        'notes',
        'prepared_by_id',
        'reconciled_at',
    ];

    protected $casts = [
        'statement_date'         => 'date',
        'bank_statement_balance' => 'decimal:2',
        'cash_book_balance'      => 'decimal:2',
        'reconciled_balance'     => 'decimal:2',
        'difference'             => 'decimal:2',
        'reconciled_at'          => 'datetime',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BankReconciliationItem::class);
    }
}
