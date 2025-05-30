<?php

namespace App\Filament\Resources\EmergencyVisitResource\Pages;

use App\Filament\Resources\EmergencyVisitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmergencyVisits extends ListRecords
{
    protected static string $resource = EmergencyVisitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
