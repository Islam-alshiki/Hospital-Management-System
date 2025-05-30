<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_number',
        'patient_id',
        'doctor_id',
        'medical_record_id',
        'prescription_date',
        'status',
        'total_amount',
        'dispensed_by',
        'dispensed_at',
        'notes',
        'special_instructions',
    ];

    protected $casts = [
        'prescription_date' => 'datetime',
        'dispensed_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    // Constants for status
    const STATUS_PENDING = 'pending';
    const STATUS_DISPENSED = 'dispensed';
    const STATUS_PARTIALLY_DISPENSED = 'partially_dispensed';
    const STATUS_CANCELLED = 'cancelled';

    // Relationships
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function dispensedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispensed_by');
    }

    public function medicines(): BelongsToMany
    {
        return $this->belongsToMany(Medicine::class, 'prescription_medicines')
                    ->withPivot([
                        'quantity',
                        'dosage',
                        'frequency',
                        'duration',
                        'instructions',
                        'dispensed_quantity',
                        'unit_price',
                        'total_price'
                    ])
                    ->withTimestamps();
    }

    // Accessors
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_DISPENSED => 'success',
            self::STATUS_PARTIALLY_DISPENSED => 'info',
            self::STATUS_CANCELLED => 'danger',
            default => 'primary',
        };
    }

    public function getIsDispensedAttribute(): bool
    {
        return $this->status === self::STATUS_DISPENSED;
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getTotalMedicinesAttribute(): int
    {
        return $this->medicines()->count();
    }

    public function getDispensedMedicinesCountAttribute(): int
    {
        return $this->medicines()
                    ->wherePivot('dispensed_quantity', '>', 0)
                    ->count();
    }

    public function getIsPartiallyDispensedAttribute(): bool
    {
        $totalMedicines = $this->total_medicines;
        $dispensedCount = $this->dispensed_medicines_count;
        
        return $dispensedCount > 0 && $dispensedCount < $totalMedicines;
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeDispensed($query)
    {
        return $query->where('status', self::STATUS_DISPENSED);
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
        return $query->whereDate('prescription_date', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('prescription_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('prescription_date', [
            now()->startOfMonth(),
            now()->endOfMonth()
        ]);
    }

    // Methods
    public function calculateTotal(): float
    {
        return $this->medicines->sum(function ($medicine) {
            return $medicine->pivot->quantity * $medicine->selling_price;
        });
    }

    public function markAsDispensed(User $user): void
    {
        $this->update([
            'status' => self::STATUS_DISPENSED,
            'dispensed_by' => $user->id,
            'dispensed_at' => now(),
        ]);
    }

    public function canBeDispensed(): bool
    {
        return $this->status === self::STATUS_PENDING || 
               $this->status === self::STATUS_PARTIALLY_DISPENSED;
    }
}
