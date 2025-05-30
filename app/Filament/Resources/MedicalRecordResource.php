<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MedicalRecordResource\Pages;
use App\Filament\Resources\MedicalRecordResource\RelationManagers;
use App\Models\MedicalRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MedicalRecordResource extends Resource
{
    protected static ?string $model = MedicalRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Medical Services';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Visit Information')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('patient_id')
                                    ->relationship('patient', 'first_name')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name . ' (' . $record->patient_id . ')')
                                    ->searchable(['first_name', 'last_name', 'patient_id'])
                                    ->required()
                                    ->preload(),

                                Forms\Components\Select::make('doctor_id')
                                    ->relationship('doctor.user', 'name')
                                    ->searchable()
                                    ->required()
                                    ->preload(),

                                Forms\Components\Select::make('appointment_id')
                                    ->relationship('appointment', 'appointment_number')
                                    ->searchable()
                                    ->preload(),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('visit_date')
                                    ->required()
                                    ->default(now()),

                                Forms\Components\Select::make('visit_type')
                                    ->options([
                                        'consultation' => 'Consultation',
                                        'follow_up' => 'Follow Up',
                                        'emergency' => 'Emergency',
                                        'routine_checkup' => 'Routine Checkup',
                                        'surgery' => 'Surgery',
                                        'procedure' => 'Procedure',
                                    ])
                                    ->required(),
                            ]),
                    ])->columns(1),

                Forms\Components\Section::make('Chief Complaint & History')
                    ->schema([
                        Forms\Components\Textarea::make('chief_complaint')
                            ->label('Chief Complaint')
                            ->required()
                            ->rows(2),

                        Forms\Components\Textarea::make('history_of_present_illness')
                            ->label('History of Present Illness')
                            ->rows(3),

                        Forms\Components\Textarea::make('past_medical_history')
                            ->label('Past Medical History')
                            ->rows(2),

                        Forms\Components\Textarea::make('family_history')
                            ->label('Family History')
                            ->rows(2),

                        Forms\Components\Textarea::make('social_history')
                            ->label('Social History')
                            ->rows(2),
                    ])->columns(1),

                Forms\Components\Section::make('Vital Signs')
                    ->schema([
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
                                    ->label('Heart Rate')
                                    ->numeric()
                                    ->suffix('bpm'),

                                Forms\Components\TextInput::make('temperature')
                                    ->label('Temperature')
                                    ->numeric()
                                    ->suffix('°C'),
                            ]),

                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('respiratory_rate')
                                    ->label('Respiratory Rate')
                                    ->numeric()
                                    ->suffix('/min'),

                                Forms\Components\TextInput::make('oxygen_saturation')
                                    ->label('O2 Saturation')
                                    ->numeric()
                                    ->suffix('%'),

                                Forms\Components\TextInput::make('weight')
                                    ->label('Weight')
                                    ->numeric()
                                    ->suffix('kg'),

                                Forms\Components\TextInput::make('height')
                                    ->label('Height')
                                    ->numeric()
                                    ->suffix('cm'),
                            ]),
                    ])->columns(1),

                Forms\Components\Section::make('Physical Examination')
                    ->schema([
                        Forms\Components\Textarea::make('general_appearance')
                            ->label('General Appearance')
                            ->rows(2),

                        Forms\Components\Textarea::make('physical_examination')
                            ->label('Physical Examination Findings')
                            ->rows(4),

                        Forms\Components\Textarea::make('systems_review')
                            ->label('Systems Review')
                            ->rows(3),
                    ])->columns(1),

                Forms\Components\Section::make('Assessment & Diagnosis')
                    ->schema([
                        Forms\Components\Textarea::make('assessment')
                            ->label('Clinical Assessment')
                            ->rows(3),

                        Forms\Components\Textarea::make('diagnosis')
                            ->label('Primary Diagnosis')
                            ->required()
                            ->rows(2),

                        Forms\Components\Textarea::make('differential_diagnosis')
                            ->label('Differential Diagnosis')
                            ->rows(2),

                        Forms\Components\TagsInput::make('icd_codes')
                            ->label('ICD-10 Codes')
                            ->placeholder('Add ICD-10 codes'),
                    ])->columns(1),

                Forms\Components\Section::make('Treatment Plan')
                    ->schema([
                        Forms\Components\Textarea::make('treatment_plan')
                            ->label('Treatment Plan')
                            ->required()
                            ->rows(4),

                        Forms\Components\Textarea::make('medications_prescribed')
                            ->label('Medications Prescribed')
                            ->rows(3),

                        Forms\Components\Textarea::make('procedures_performed')
                            ->label('Procedures Performed')
                            ->rows(2),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('follow_up_date')
                                    ->label('Follow-up Date'),

                                Forms\Components\TextInput::make('follow_up_instructions')
                                    ->label('Follow-up Instructions'),
                            ]),
                    ])->columns(1),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('lab_results')
                            ->label('Laboratory Results')
                            ->rows(3),

                        Forms\Components\Textarea::make('imaging_results')
                            ->label('Imaging Results')
                            ->rows(3),

                        Forms\Components\Textarea::make('notes')
                            ->label('Additional Notes')
                            ->rows(3),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'active' => 'Active Treatment',
                                        'completed' => 'Treatment Completed',
                                        'follow_up_required' => 'Follow-up Required',
                                        'referred' => 'Referred to Specialist',
                                        'discharged' => 'Discharged',
                                    ])
                                    ->default('active')
                                    ->required(),

                                Forms\Components\Hidden::make('created_by')
                                    ->default(auth()->id()),
                            ]),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->searchable(['patient.first_name', 'patient.last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('patient.patient_id')
                    ->label('Patient ID')
                    ->searchable(),

                Tables\Columns\TextColumn::make('doctor.user.name')
                    ->label('Doctor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('visit_date')
                    ->label('Visit Date')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('visit_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'emergency' => 'danger',
                        'surgery' => 'warning',
                        'consultation' => 'primary',
                        'follow_up' => 'info',
                        'routine_checkup' => 'success',
                        'procedure' => 'gray',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('chief_complaint')
                    ->label('Chief Complaint')
                    ->limit(50)
                    ->tooltip(function ($record) {
                        return $record->chief_complaint;
                    }),

                Tables\Columns\TextColumn::make('diagnosis')
                    ->label('Primary Diagnosis')
                    ->limit(50)
                    ->tooltip(function ($record) {
                        return $record->diagnosis;
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'warning',
                        'completed' => 'success',
                        'follow_up_required' => 'info',
                        'referred' => 'primary',
                        'discharged' => 'gray',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('follow_up_date')
                    ->label('Follow-up')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('visit_type')
                    ->options([
                        'consultation' => 'Consultation',
                        'follow_up' => 'Follow Up',
                        'emergency' => 'Emergency',
                        'routine_checkup' => 'Routine Checkup',
                        'surgery' => 'Surgery',
                        'procedure' => 'Procedure',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active Treatment',
                        'completed' => 'Treatment Completed',
                        'follow_up_required' => 'Follow-up Required',
                        'referred' => 'Referred to Specialist',
                        'discharged' => 'Discharged',
                    ]),

                Tables\Filters\SelectFilter::make('doctor_id')
                    ->relationship('doctor.user', 'name')
                    ->label('Doctor'),

                Tables\Filters\Filter::make('visit_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('visit_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('visit_date', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('follow_up_due')
                    ->label('Follow-up Due')
                    ->query(fn (Builder $query): Builder => $query->where('follow_up_date', '<=', now())->where('status', 'follow_up_required')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('create_prescription')
                    ->label('Prescribe')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('success')
                    ->url(fn ($record) => route('filament.admin.resources.prescriptions.create') . '?patient_id=' . $record->patient_id . '&medical_record_id=' . $record->id),

                Tables\Actions\Action::make('order_lab_test')
                    ->label('Order Lab Test')
                    ->icon('heroicon-o-beaker')
                    ->color('info')
                    ->url(fn ($record) => route('filament.admin.resources.lab-tests.create') . '?patient_id=' . $record->patient_id . '&medical_record_id=' . $record->id),

                Tables\Actions\Action::make('schedule_follow_up')
                    ->label('Schedule Follow-up')
                    ->icon('heroicon-o-calendar-plus')
                    ->color('warning')
                    ->form([
                        Forms\Components\DateTimePicker::make('follow_up_date')
                            ->required(),
                        Forms\Components\Textarea::make('follow_up_instructions')
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'follow_up_date' => $data['follow_up_date'],
                            'follow_up_instructions' => $data['follow_up_instructions'],
                            'status' => 'follow_up_required',
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Follow-up Scheduled')
                            ->body('Follow-up appointment has been scheduled.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('mark_completed')
                        ->label('Mark as Completed')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['status' => 'completed'])),
                ]),
            ])
            ->defaultSort('visit_date', 'desc');
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
            'index' => Pages\ListMedicalRecords::route('/'),
            'create' => Pages\CreateMedicalRecord::route('/create'),
            'edit' => Pages\EditMedicalRecord::route('/{record}/edit'),
        ];
    }
}
