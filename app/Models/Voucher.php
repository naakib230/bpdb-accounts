<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    protected $fillable = [
        'voucher_number',
        'voucher_type',
        'cash_type',
        'date',
        'fiscal_year_id',
        'accounting_period_id',
        'cost_center_id',
        'bank_account_id',
        'cheque_number',
        'cheque_date',
        'payee_payer_name',
        'total_debit',
        'total_credit',
        'status',
        'narration',
        'prepared_by_id',
        'approved_by_id',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'date'         => 'date',
        'cheque_date'  => 'date',
        'total_debit'  => 'decimal:2',
        'total_credit' => 'decimal:2',
        'approved_at'  => 'datetime',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(VoucherEntry::class);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function isCashVoucher(): bool
    {
        return $this->voucher_type === 'cash_voucher';
    }

    public function isJournalVoucher(): bool
    {
        return $this->voucher_type === 'journal_voucher';
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function getBanglaVoucherTypeAttribute(): string
    {
        return $this->voucher_type === 'cash_voucher' ? 'ক্যাশ ভাউচার' : 'জার্নাল ভাউচার';
    }

    public function getBanglaStatusAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'খসড়া (Draft)',
            'submitted' => 'অনুমোদনের অপেক্ষায় (Submitted)',
            'posted'    => 'অনুমোদিত ও পোস্টকৃত (Posted)',
            'rejected'  => 'প্রত্যাখ্যাত (Rejected)',
            default     => $this->status,
        };
    }
}
