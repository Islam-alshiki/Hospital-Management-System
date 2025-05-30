<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'bill_id',
        'amount',
        'payment_method',
        'payment_date',
        'transaction_reference',
        'received_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    // Constants for payment methods
    const METHOD_CASH = 'cash';
    const METHOD_CARD = 'card';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_INSURANCE = 'insurance';
    const METHOD_CHEQUE = 'cheque';

    // Relationships
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    // Accessors
    public function getPaymentMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            self::METHOD_CASH => 'Cash',
            self::METHOD_CARD => 'Credit/Debit Card',
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
            self::METHOD_INSURANCE => 'Insurance',
            self::METHOD_CHEQUE => 'Cheque',
            default => ucfirst($this->payment_method),
        };
    }

    public function getPaymentMethodColorAttribute(): string
    {
        return match($this->payment_method) {
            self::METHOD_CASH => 'success',
            self::METHOD_CARD => 'primary',
            self::METHOD_BANK_TRANSFER => 'info',
            self::METHOD_INSURANCE => 'warning',
            self::METHOD_CHEQUE => 'secondary',
            default => 'dark',
        };
    }

    // Scopes
    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('payment_date', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('payment_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('payment_date', [
            now()->startOfMonth(),
            now()->endOfMonth()
        ]);
    }

    public function scopeByBill($query, $billId)
    {
        return $query->where('bill_id', $billId);
    }

    public function scopeByReceiver($query, $userId)
    {
        return $query->where('received_by', $userId);
    }

    // Boot method to auto-generate payment number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = 'PAY-' . now()->format('Ymd') . '-' . str_pad(
                    Payment::whereDate('created_at', today())->count() + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }
}
