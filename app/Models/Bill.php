<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_number',
        'patient_id',
        'doctor_id',
        'bill_date',
        'consultation_fee',
        'lab_tests_total',
        'medicines_total',
        'procedures_total',
        'room_charges',
        'other_charges',
        'subtotal',
        'discount_percentage',
        'discount_amount',
        'tax_percentage',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'payment_status',
        'payment_method',
        'insurance_provider_id',
        'insurance_coverage',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'bill_date' => 'datetime',
        'consultation_fee' => 'decimal:2',
        'lab_tests_total' => 'decimal:2',
        'medicines_total' => 'decimal:2',
        'procedures_total' => 'decimal:2',
        'room_charges' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'insurance_coverage' => 'decimal:2',
    ];

    // Constants for payment status
    const STATUS_UNPAID = 'unpaid';
    const STATUS_PARTIAL = 'partial';
    const STATUS_PAID = 'paid';
    const STATUS_REFUNDED = 'refunded';

    // Constants for payment methods
    const METHOD_CASH = 'cash';
    const METHOD_CARD = 'card';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_INSURANCE = 'insurance';
    const METHOD_CHEQUE = 'cheque';

    // Relationships
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Accessors
    public function getPaymentStatusColorAttribute(): string
    {
        return match($this->payment_status) {
            self::STATUS_UNPAID => 'danger',
            self::STATUS_PARTIAL => 'warning',
            self::STATUS_PAID => 'success',
            self::STATUS_REFUNDED => 'info',
            default => 'secondary',
        };
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->payment_status === self::STATUS_PAID;
    }

    public function getIsUnpaidAttribute(): bool
    {
        return $this->payment_status === self::STATUS_UNPAID;
    }

    public function getIsPartiallyPaidAttribute(): bool
    {
        return $this->payment_status === self::STATUS_PARTIAL;
    }

    public function getOutstandingAmountAttribute(): float
    {
        return max(0, $this->total_amount - $this->paid_amount);
    }

    public function getPaymentProgressAttribute(): float
    {
        if ($this->total_amount <= 0) {
            return 100;
        }
        return min(100, ($this->paid_amount / $this->total_amount) * 100);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('payment_status', $status);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', self::STATUS_UNPAID);
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', self::STATUS_PAID);
    }

    public function scopePartiallyPaid($query)
    {
        return $query->where('payment_status', self::STATUS_PARTIAL);
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('bill_date', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('bill_date', [
            now()->startOfMonth(),
            now()->endOfMonth()
        ]);
    }

    public function scopeOverdue($query, $days = 30)
    {
        return $query->where('payment_status', '!=', self::STATUS_PAID)
                    ->where('bill_date', '<', now()->subDays($days));
    }

    // Methods
    public function calculateTotals(): void
    {
        $this->subtotal = $this->consultation_fee + 
                         $this->lab_tests_total + 
                         $this->medicines_total + 
                         $this->procedures_total + 
                         $this->room_charges + 
                         $this->other_charges;

        $this->discount_amount = $this->subtotal * ($this->discount_percentage / 100);
        $afterDiscount = $this->subtotal - $this->discount_amount;
        
        $this->tax_amount = $afterDiscount * ($this->tax_percentage / 100);
        $this->total_amount = $afterDiscount + $this->tax_amount - $this->insurance_coverage;
        
        $this->balance_amount = $this->total_amount - $this->paid_amount;
        
        $this->updatePaymentStatus();
    }

    public function addPayment(float $amount, string $method = self::METHOD_CASH, User $user = null): void
    {
        $this->paid_amount += $amount;
        $this->balance_amount = $this->total_amount - $this->paid_amount;
        
        $this->updatePaymentStatus();
        $this->save();

        // Create payment record
        Payment::create([
            'bill_id' => $this->id,
            'amount' => $amount,
            'payment_method' => $method,
            'payment_date' => now(),
            'received_by' => $user?->id,
            'notes' => "Payment of {$amount} received via {$method}",
        ]);
    }

    private function updatePaymentStatus(): void
    {
        if ($this->paid_amount <= 0) {
            $this->payment_status = self::STATUS_UNPAID;
        } elseif ($this->paid_amount >= $this->total_amount) {
            $this->payment_status = self::STATUS_PAID;
        } else {
            $this->payment_status = self::STATUS_PARTIAL;
        }
    }

    public function applyInsuranceCoverage(): void
    {
        if ($this->insuranceProvider && $this->insuranceProvider->is_active_contract) {
            $this->insurance_coverage = $this->insuranceProvider->calculateCoverage($this->subtotal);
            $this->calculateTotals();
        }
    }
}
