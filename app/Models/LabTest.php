<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_number',
        'patient_id',
        'doctor_id',
        'medical_record_id',
        'test_name',
        'test_category',
        'description',
        'sample_type',
        'ordered_at',
        'collected_at',
        'completed_at',
        'status',
        'result',
        'normal_range',
        'result_status',
        'technician_id',
        'notes',
        'attachments',
        'cost',
    ];

    protected $casts = [
        'ordered_at' => 'datetime',
        'collected_at' => 'datetime',
        'completed_at' => 'datetime',
        'attachments' => 'array',
        'cost' => 'decimal:2',
    ];

    // Constants for status
    const STATUS_ORDERED = 'ordered';
    const STATUS_COLLECTED = 'collected';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // Constants for result status
    const RESULT_NORMAL = 'normal';
    const RESULT_ABNORMAL = 'abnormal';
    const RESULT_CRITICAL = 'critical';

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

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    // Accessors
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ORDERED => 'warning',
            self::STATUS_COLLECTED => 'info',
            self::STATUS_PROCESSING => 'primary',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default => 'secondary',
        };
    }

    public function getResultStatusColorAttribute(): string
    {
        return match($this->result_status) {
            self::RESULT_NORMAL => 'success',
            self::RESULT_ABNORMAL => 'warning',
            self::RESULT_CRITICAL => 'danger',
            default => 'secondary',
        };
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getIsPendingAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_ORDERED, self::STATUS_COLLECTED, self::STATUS_PROCESSING]);
    }

    public function getTurnaroundTimeAttribute(): ?int
    {
        if ($this->ordered_at && $this->completed_at) {
            return $this->ordered_at->diffInHours($this->completed_at);
        }
        return null;
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_ORDERED, self::STATUS_COLLECTED, self::STATUS_PROCESSING]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('test_category', $category);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('ordered_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('ordered_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeCriticalResults($query)
    {
        return $query->where('result_status', self::RESULT_CRITICAL);
    }

    public function scopeAbnormalResults($query)
    {
        return $query->whereIn('result_status', [self::RESULT_ABNORMAL, self::RESULT_CRITICAL]);
    }

    // Methods
    public function markAsCollected(User $technician = null): void
    {
        $this->update([
            'status' => self::STATUS_COLLECTED,
            'collected_at' => now(),
            'technician_id' => $technician?->id,
        ]);
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
        ]);
    }

    public function markAsCompleted(string $result, string $resultStatus = null, User $technician = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'result' => $result,
            'result_status' => $resultStatus,
            'technician_id' => $technician?->id ?? $this->technician_id,
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }
}
