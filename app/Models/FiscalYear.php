<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FiscalYear extends Model
{
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_current',
        'is_closed',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_current' => 'boolean',
        'is_closed'  => 'boolean',
        'closed_at'  => 'datetime',
    ];

    public function periods(): HasMany
    {
        return $this->hasMany(AccountingPeriod::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public static function current(): ?self
    {
        return static::where('is_current', true)->first() 
            ?? static::orderByDesc('start_date')->first();
    }
}
