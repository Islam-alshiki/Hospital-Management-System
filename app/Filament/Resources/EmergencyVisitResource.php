<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmergencyVisitResource\Pages;
use App\Filament\Resources\EmergencyVisitResource\RelationManagers;
use App\Models\EmergencyVisit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmergencyVisitResource extends Resource
{
    protected static ?string $model = EmergencyVisit::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = 'Emergency Services';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Emergency Visit Information')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('patient_id')
                                    ->relationship('patient', 'first_name')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name . ' (' . $record->patient_id . ')')
                                    ->searchable(['first_name', 'last_name', 'patient_id'])
                                    ->required()
                                    ->preload(),

                                Forms\Components\TextInput::make('emergency_number')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->default(fn () => 'ER-' . now()->format('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT)),

                                Forms\Components\DateTimePicker::make('arrival_time')
                                    ->required()
                                    ->default(now()),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('arrival_method')
                                    ->options([
                                        'walk_in' => 'Walk In',
                                        'ambulance' => 'Ambulance',
                                        'police' => 'Police',
                                        'referral' => 'Referral',
                                        'helicopter' => 'Helicopter',
                                        'private_vehicle' => 'Private Vehicle',
                                    ])
                                    ->required(),

                                Forms\Components\Select::make('triage_level')
                                    ->options([
                                        'critical' => 'Critical (Red)',
                                        'urgent' => 'Urgent (Orange)',
                                        'less_urgent' => 'Less Urgent (Yellow)',
                                        'non_urgent' => 'Non-Urgent (Green)',
                                        'dead' => 'Dead (Black)',
                                    ])
                                    ->required(),
                            ]),
                    ])->columns(1),

                Forms\Components\Section::make('Medical Assessment')
                    ->schema([
                        Forms\Components\Textarea::make('chief_complaint')
                            ->required()
                            ->rows(2),

                        Forms\Components\Textarea::make('presenting_symptoms')
                            ->rows(3),

                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('blood_pressure_systolic')
                                    ->label('BP Systolic')
                                    ->numeric()
                                    ->suffix('mmHg'),

                                Forms\Components\TextInput::make('blood_pressure_diastolic')
                                    ->label('BP Diastolic')
                                    ->numeric()
                                    ->suffix('mmHg'),

                                Forms\Components\TextInput::make('heart_rate')
                                    ->numeric()
                                    ->suffix('bpm'),

                                Forms\Components\TextInput::make('temperature')
                                    ->numeric()
                                    ->suffix('°C'),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('respiratory_rate')
                                    ->numeric()
                                    ->suffix('/min'),

                                Forms\Components\TextInput::make('oxygen_saturation')
                                    ->numeric()
                                    ->suffix('%'),

                                Forms\Components\TextInput::make('glasgow_coma_scale')
                                    ->label('GCS')
                                    ->numeric()
                                    ->minValue(3)
                                    ->maxValue(15),
                            ]),
                    ])->columns(1),

                Forms\Components\Section::make('Treatment & Disposition')
                    ->schema([
                        Forms\Components\Select::make('attending_doctor_id')
                            ->relationship('attendingDoctor.user', 'name')
                            ->label('Attending Doctor')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Textarea::make('treatment_provided')
                            ->rows(3),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('disposition')
                                    ->options([
                                        'discharged' => 'Discharged',
                                        'admitted' => 'Admitted',
                                        'transferred' => 'Transferred',
                                        'left_ama' => 'Left Against Medical Advice',
                                        'deceased' => 'Deceased',
                                        'observation' => 'Under Observation',
                                    ])
                                    ->required(),

                                Forms\Components\DateTimePicker::make('discharge_time')
                                    ->label('Discharge/Disposition Time'),
                            ]),

                        Forms\Components\Textarea::make('discharge_instructions')
                            ->rows(3),

                        Forms\Components\Textarea::make('notes')
                            ->rows(3),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('emergency_number')
                    ->label('Emergency #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->searchable(['patient.first_name', 'patient.last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('triage_level')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'urgent' => 'warning',
                        'less_urgent' => 'info',
                        'non_urgent' => 'success',
                        'dead' => 'gray',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('arrival_method')
                    ->badge(),

                Tables\Columns\TextColumn::make('chief_complaint')
                    ->limit(50)
                    ->tooltip(function ($record) {
                        return $record->chief_complaint;
                    }),

                Tables\Columns\TextColumn::make('arrival_time')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('disposition')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'discharged' => 'success',
                        'admitted' => 'info',
                        'transferred' => 'warning',
                        'left_ama' => 'danger',
                        'deceased' => 'gray',
                        'observation' => 'primary',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('attendingDoctor.user.name')
                    ->label('Attending Doctor')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('wait_time')
                    ->label('Wait Time')
                    ->getStateUsing(function ($record) {
                        if ($record->discharge_time) {
                            return $record->arrival_time->diffForHumans($record->discharge_time, true);
                        }
                        return $record->arrival_time->diffForHumans(now(), true);
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('discharge_time')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('triage_level')
                    ->options([
                        'critical' => 'Critical (Red)',
                        'urgent' => 'Urgent (Orange)',
                        'less_urgent' => 'Less Urgent (Yellow)',
                        'non_urgent' => 'Non-Urgent (Green)',
                        'dead' => 'Dead (Black)',
                    ]),

                Tables\Filters\SelectFilter::make('arrival_method')
                    ->options([
                        'walk_in' => 'Walk In',
                        'ambulance' => 'Ambulance',
                        'police' => 'Police',
                        'referral' => 'Referral',
                        'helicopter' => 'Helicopter',
                        'private_vehicle' => 'Private Vehicle',
                    ]),

                Tables\Filters\SelectFilter::make('disposition')
                    ->options([
                        'discharged' => 'Discharged',
                        'admitted' => 'Admitted',
                        'transferred' => 'Transferred',
                        'left_ama' => 'Left Against Medical Advice',
                        'deceased' => 'Deceased',
                        'observation' => 'Under Observation',
                    ]),

                Tables\Filters\Filter::make('arrival_time')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('arrival_time', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('arrival_time', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('active_cases')
                    ->label('Active Cases')
                    ->query(fn (Builder $query): Builder => $query->whereNull('discharge_time')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('discharge')
                    ->label('Discharge')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('disposition')
                            ->options([
                                'discharged' => 'Discharged',
                                'admitted' => 'Admitted',
                                'transferred' => 'Transferred',
                                'left_ama' => 'Left Against Medical Advice',
                            ])
                            ->required(),
                        Forms\Components\DateTimePicker::make('discharge_time')
                            ->default(now())
                            ->required(),
                        Forms\Components\Textarea::make('discharge_instructions')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update($data);

                        \Filament\Notifications\Notification::make()
                            ->title('Patient Discharged')
                            ->body('Emergency visit has been completed.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => !$record->discharge_time),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('arrival_time', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmergencyVisits::route('/'),
            'create' => Pages\CreateEmergencyVisit::route('/create'),
            'edit' => Pages\EditEmergencyVisit::route('/{record}/edit'),
        ];
    }
}
