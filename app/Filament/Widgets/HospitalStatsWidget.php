<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HospitalStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Patients', \App\Models\Patient::count())
                ->description('Registered patients')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Today\'s Appointments', \App\Models\Appointment::whereDate('appointment_date', today())->count())
                ->description('Scheduled for today')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make('Active Doctors', \App\Models\Doctor::where('is_active', true)->count())
                ->description('Available doctors')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary'),

            Stat::make('Available Beds', \App\Models\Room::sum('available_beds') ?? 0)
                ->description('Hospital capacity')
                ->descriptionIcon('heroicon-m-home')
                ->color('warning'),

            Stat::make('Pending Lab Tests', \App\Models\LabTest::whereIn('status', ['ordered', 'collected', 'processing'])->count())
                ->description('Tests in progress')
                ->descriptionIcon('heroicon-m-beaker')
                ->color('danger'),

            Stat::make('Emergency Visits Today', \App\Models\EmergencyVisit::whereDate('arrival_time', today())->count())
                ->description('Emergency department')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Revenue This Month', 'LYD ' . number_format(\App\Models\Bill::whereMonth('bill_date', now()->month)->sum('total_amount') ?? 0, 2))
                ->description('Monthly revenue')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Medicines Low Stock', \App\Models\Medicine::whereColumn('stock_quantity', '<=', 'minimum_stock_level')->count())
                ->description('Need restocking')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('warning'),
        ];
    }
}
