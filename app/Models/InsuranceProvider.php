<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_ar',
        'code',
        'description',
        'contact_person',
        'phone',
        'email',
        'address',
        'coverage_percentage',
        'covered_services',
        'contract_start_date',
        'contract_end_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'coverage_percentage' => 'decimal:2',
        'covered_services' => 'array',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    // Accessors
    public function getDisplayNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' && $this->name_ar ? $this->name_ar : $this->name;
    }

    public function getIsActiveContractAttribute(): bool
    {
        if (!$this->contract_start_date || !$this->contract_end_date) {
            return $this->is_active;
        }

        $today = now()->toDateString();
        return $this->is_active && 
               $today >= $this->contract_start_date->toDateString() && 
               $today <= $this->contract_end_date->toDateString();
    }

    public function getContractStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        if (!$this->contract_start_date || !$this->contract_end_date) {
            return 'no_contract';
        }

        $today = now();
        
        if ($today < $this->contract_start_date) {
            return 'pending';
        } elseif ($today > $this->contract_end_date) {
            return 'expired';
        } else {
            return 'active';
        }
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithActiveContract($query)
    {
        $today = now()->toDateString();
        return $query->where('is_active', true)
                    ->where('contract_start_date', '<=', $today)
                    ->where('contract_end_date', '>=', $today);
    }

    public function scopeExpiringContracts($query, $days = 30)
    {
        $futureDate = now()->addDays($days)->toDateString();
        return $query->where('is_active', true)
                    ->where('contract_end_date', '<=', $futureDate)
                    ->where('contract_end_date', '>=', now()->toDateString());
    }

    // Methods
    public function calculateCoverage(float $amount): float
    {
        return $amount * ($this->coverage_percentage / 100);
    }

    public function isServiceCovered(string $service): bool
    {
        return in_array($service, $this->covered_services ?? []);
    }
}
