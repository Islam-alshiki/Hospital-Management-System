<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'record_number',
        'patient_id',
        'doctor_id',
        'appointment_id',
        'visit_date',
        'visit_reason',
        'symptoms',
        'diagnosis',
        'treatment_plan',
        'vital_signs',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'heart_rate',
        'temperature',
        'weight',
        'height',
        'oxygen_saturation',
        'respiratory_rate',
        'notes',
        'follow_up_required',
        'follow_up_date',
        'attachments',
    ];

    protected $casts = [
        'visit_date' => 'datetime',
        'follow_up_date' => 'date',
        'vital_signs' => 'array',
        'symptoms' => 'array',
        'attachments' => 'array',
        'follow_up_required' => 'boolean',
        'blood_pressure_systolic' => 'integer',
        'blood_pressure_diastolic' => 'integer',
        'heart_rate' => 'integer',
        'temperature' => 'decimal:1',
        'weight' => 'decimal:1',
        'height' => 'decimal:1',
        'oxygen_saturation' => 'integer',
        'respiratory_rate' => 'integer',
    ];

    // Relationships
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function labTests(): HasMany
    {
        return $this->hasMany(LabTest::class);
    }

    // Accessors
    public function getBloodPressureAttribute(): string
    {
        if ($this->blood_pressure_systolic && $this->blood_pressure_diastolic) {
            return $this->blood_pressure_systolic . '/' . $this->blood_pressure_diastolic;
        }
        return 'N/A';
    }

    public function getBmiAttribute(): ?float
    {
        if ($this->weight && $this->height) {
            $heightInMeters = $this->height / 100;
            return round($this->weight / ($heightInMeters * $heightInMeters), 1);
        }
        return null;
    }

    public function getBmiCategoryAttribute(): string
    {
        $bmi = $this->bmi;
        if (!$bmi) return 'N/A';

        return match(true) {
            $bmi < 18.5 => 'Underweight',
            $bmi < 25 => 'Normal',
            $bmi < 30 => 'Overweight',
            default => 'Obese'
        };
    }

    public function getVitalSignsStatusAttribute(): array
    {
        $status = [];
        
        // Blood Pressure
        if ($this->blood_pressure_systolic && $this->blood_pressure_diastolic) {
            $systolic = $this->blood_pressure_systolic;
            $diastolic = $this->blood_pressure_diastolic;
            
            if ($systolic >= 140 || $diastolic >= 90) {
                $status['blood_pressure'] = 'High';
            } elseif ($systolic < 90 || $diastolic < 60) {
                $status['blood_pressure'] = 'Low';
            } else {
                $status['blood_pressure'] = 'Normal';
            }
        }
        
        // Heart Rate
        if ($this->heart_rate) {
            if ($this->heart_rate > 100) {
                $status['heart_rate'] = 'High';
            } elseif ($this->heart_rate < 60) {
                $status['heart_rate'] = 'Low';
            } else {
                $status['heart_rate'] = 'Normal';
            }
        }
        
        // Temperature
        if ($this->temperature) {
            if ($this->temperature > 37.5) {
                $status['temperature'] = 'Fever';
            } elseif ($this->temperature < 36) {
                $status['temperature'] = 'Low';
            } else {
                $status['temperature'] = 'Normal';
            }
        }
        
        return $status;
    }

    // Scopes
    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('visit_date', '>=', now()->subDays($days));
    }

    public function scopeRequiringFollowUp($query)
    {
        return $query->where('follow_up_required', true)
                    ->where('follow_up_date', '>=', today());
    }
}
