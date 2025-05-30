<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrescriptionResource\Pages;
use App\Filament\Resources\PrescriptionResource\RelationManagers;
use App\Models\Prescription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PrescriptionResource extends Resource
{
    protected static ?string $model = Prescription::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Pharmacy Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Prescription Information')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('patient_id')
                                    ->relationship('patient', 'first_name')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name . ' (' . $record->patient_id . ')')
                                    ->searchable(['first_name', 'last_name', 'patient_id'])
                                    ->required()
                                    ->preload(),

                                Forms\Components\Select::make('medical_record_id')
                                    ->relationship('medicalRecord', 'id')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => 'Visit: ' . $record->visit_date->format('Y-m-d') . ' - ' . $record->diagnosis)
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\Select::make('prescribed_by')
                                    ->relationship('prescribedByDoctor.user', 'name')
                                    ->label('Prescribed By')
                                    ->searchable()
                                    ->required()
                                    ->preload(),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('prescription_number')
                                    ->label('Prescription Number')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->default(fn () => 'RX-' . now()->format('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT)),

                                Forms\Components\DateTimePicker::make('prescribed_date')
                                    ->label('Prescribed Date')
                                    ->required()
                                    ->default(now()),
                            ]),
                    ])->columns(1),

                Forms\Components\Section::make('Medications')
                    ->schema([
                        Forms\Components\Repeater::make('prescriptionMedicines')
                            ->relationship()
                            ->schema([
                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\Select::make('medicine_id')
                                            ->relationship('medicine', 'name')
                                            ->searchable()
                                            ->required()
                                            ->preload()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state) {
                                                    $medicine = \App\Models\Medicine::find($state);
                                                    if ($medicine) {
                                                        $set('unit_price', $medicine->selling_price);
                                                    }
                                                }
                                            }),

                                        Forms\Components\TextInput::make('dosage')
                                            ->required()
                                            ->placeholder('e.g., 500mg'),

                                        Forms\Components\TextInput::make('frequency')
                                            ->required()
                                            ->placeholder('e.g., 3 times daily'),

                                        Forms\Components\TextInput::make('duration')
                                            ->required()
                                            ->placeholder('e.g., 7 days'),
                                    ]),

                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('quantity')
                                            ->numeric()
                                            ->required()
                                            ->default(1),

                                        Forms\Components\TextInput::make('unit_price')
                                            ->numeric()
                                            ->prefix('LYD')
                                            ->required(),

                                        Forms\Components\TextInput::make('total_price')
                                            ->numeric()
                                            ->prefix('LYD')
                                            ->disabled()
                                            ->dehydrated(false),
                                    ]),

                                Forms\Components\Textarea::make('instructions')
                                    ->label('Special Instructions')
                                    ->rows(2)
                                    ->placeholder('e.g., Take with food, Avoid alcohol'),
                            ])
                            ->columns(1)
                            ->defaultItems(1)
                            ->addActionLabel('Add Medicine')
                            ->deleteActionLabel('Remove Medicine'),
                    ])->columns(1),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'dispensed' => 'Dispensed',
                                        'partially_dispensed' => 'Partially Dispensed',
                                        'cancelled' => 'Cancelled',
                                        'expired' => 'Expired',
                                    ])
                                    ->default('pending')
                                    ->required(),

                                Forms\Components\DateTimePicker::make('dispensed_date')
                                    ->label('Dispensed Date'),
                            ]),

                        Forms\Components\Select::make('dispensed_by')
                            ->relationship('dispensedByUser', 'name')
                            ->label('Dispensed By')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Prescription Notes')
                            ->rows(3),

                        Forms\Components\Textarea::make('pharmacist_notes')
                            ->label('Pharmacist Notes')
                            ->rows(2),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('prescription_number')
                    ->label('Prescription #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->searchable(['patient.first_name', 'patient.last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('patient.patient_id')
                    ->label('Patient ID')
                    ->searchable(),

                Tables\Columns\TextColumn::make('prescribedByDoctor.user.name')
                    ->label('Prescribed By')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('prescribed_date')
                    ->label('Prescribed Date')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('medicines_count')
                    ->label('Medicines')
                    ->getStateUsing(fn ($record) => $record->prescriptionMedicines()->count() . ' items'),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('LYD')
                    ->getStateUsing(fn ($record) => $record->prescriptionMedicines()->sum(\DB::raw('quantity * unit_price'))),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'dispensed' => 'success',
                        'partially_dispensed' => 'info',
                        'cancelled' => 'danger',
                        'expired' => 'gray',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('dispensed_date')
                    ->label('Dispensed Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('dispensedByUser.name')
                    ->label('Dispensed By')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'dispensed' => 'Dispensed',
                        'partially_dispensed' => 'Partially Dispensed',
                        'cancelled' => 'Cancelled',
                        'expired' => 'Expired',
                    ]),

                Tables\Filters\SelectFilter::make('prescribed_by')
                    ->relationship('prescribedByDoctor.user', 'name')
                    ->label('Prescribed By'),

                Tables\Filters\SelectFilter::make('dispensed_by')
                    ->relationship('dispensedByUser', 'name')
                    ->label('Dispensed By'),

                Tables\Filters\Filter::make('prescribed_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('prescribed_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('prescribed_date', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('pending_prescriptions')
                    ->label('Pending Prescriptions')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'pending')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('dispense')
                    ->label('Dispense')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\DateTimePicker::make('dispensed_date')
                            ->label('Dispensed Date')
                            ->default(now())
                            ->required(),
                        Forms\Components\Textarea::make('pharmacist_notes')
                            ->label('Pharmacist Notes')
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'dispensed',
                            'dispensed_date' => $data['dispensed_date'],
                            'dispensed_by' => auth()->id(),
                            'pharmacist_notes' => $data['pharmacist_notes'],
                        ]);

                        // Update medicine stock
                        foreach ($record->prescriptionMedicines as $prescriptionMedicine) {
                            $medicine = $prescriptionMedicine->medicine;
                            $medicine->decrement('stock_quantity', $prescriptionMedicine->quantity);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Prescription Dispensed')
                            ->body('Prescription has been dispensed successfully.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->status === 'pending'),

                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'cancelled',
                            'pharmacist_notes' => 'Cancelled: ' . $data['cancellation_reason'],
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Prescription Cancelled')
                            ->body('Prescription has been cancelled.')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'partially_dispensed'])),

                Tables\Actions\Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->action(function ($record) {
                        // Here you would implement prescription printing logic
                        \Filament\Notifications\Notification::make()
                            ->title('Print Prescription')
                            ->body('Prescription printing feature will be implemented.')
                            ->info()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('mark_dispensed')
                        ->label('Mark as Dispensed')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'status' => 'dispensed',
                                    'dispensed_date' => now(),
                                    'dispensed_by' => auth()->id(),
                                ]);
                            });
                        }),
                ]),
            ])
            ->defaultSort('prescribed_date', 'desc');
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
            'index' => Pages\ListPrescriptions::route('/'),
            'create' => Pages\CreatePrescription::route('/create'),
            'edit' => Pages\EditPrescription::route('/{record}/edit'),
        ];
    }
}
