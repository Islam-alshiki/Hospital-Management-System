<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LabTestResource\Pages;
use App\Filament\Resources\LabTestResource\RelationManagers;
use App\Models\LabTest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LabTestResource extends Resource
{
    protected static ?string $model = LabTest::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = 'Laboratory';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Test Information')
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

                                Forms\Components\Select::make('doctor_id')
                                    ->relationship('doctor', 'id')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->user->name . ' (' . $record->specialization . ')')
                                    ->label('Ordered By')
                                    ->searchable()
                                    ->required()
                                    ->preload(),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('test_number')
                                    ->label('Test Number')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->default(fn () => 'LAB-' . now()->format('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT)),

                                Forms\Components\TextInput::make('test_name')
                                    ->label('Test Name')
                                    ->required(),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('test_category')
                                    ->options([
                                        'hematology' => 'Hematology',
                                        'biochemistry' => 'Biochemistry',
                                        'microbiology' => 'Microbiology',
                                        'immunology' => 'Immunology',
                                        'pathology' => 'Pathology',
                                        'radiology' => 'Radiology',
                                        'cardiology' => 'Cardiology',
                                        'endocrinology' => 'Endocrinology',
                                        'toxicology' => 'Toxicology',
                                        'genetics' => 'Genetics',
                                        'other' => 'Other',
                                    ])
                                    ->required(),

                                Forms\Components\Select::make('sample_type')
                                    ->options([
                                        'blood' => 'Blood',
                                        'urine' => 'Urine',
                                        'stool' => 'Stool',
                                        'saliva' => 'Saliva',
                                        'tissue' => 'Tissue',
                                        'swab' => 'Swab',
                                        'csf' => 'Cerebrospinal Fluid',
                                        'pleural_fluid' => 'Pleural Fluid',
                                        'other' => 'Other',
                                    ])
                                    ->required(),

                                Forms\Components\Select::make('priority')
                                    ->options([
                                        'routine' => 'Routine',
                                        'urgent' => 'Urgent',
                                        'stat' => 'STAT',
                                        'critical' => 'Critical',
                                    ])
                                    ->default('routine')
                                    ->required(),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Test Description')
                            ->rows(2),

                        Forms\Components\Textarea::make('notes')
                            ->label('Clinical Notes')
                            ->rows(2),
                    ])->columns(1),

                Forms\Components\Section::make('Sample Collection')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DateTimePicker::make('ordered_at')
                                    ->label('Ordered Date')
                                    ->required()
                                    ->default(now()),

                                Forms\Components\DateTimePicker::make('collected_at')
                                    ->label('Sample Collected Date'),

                                Forms\Components\Select::make('technician_id')
                                    ->relationship('technician', 'name')
                                    ->label('Collected By')
                                    ->searchable()
                                    ->preload(),
                            ]),

                        Forms\Components\Select::make('status')
                            ->options([
                                'ordered' => 'Ordered',
                                'collected' => 'Collected',
                                'processing' => 'Processing',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('ordered')
                            ->required(),
                    ])->columns(1),

                Forms\Components\Section::make('Results & Cost')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('cost')
                                    ->label('Test Cost')
                                    ->numeric()
                                    ->prefix('LYD'),

                                Forms\Components\DateTimePicker::make('completed_at')
                                    ->label('Completed Date'),
                            ]),

                        Forms\Components\Textarea::make('result')
                            ->label('Test Result')
                            ->rows(3),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('normal_range')
                                    ->label('Normal Range'),

                                Forms\Components\Select::make('result_status')
                                    ->options([
                                        'normal' => 'Normal',
                                        'abnormal' => 'Abnormal',
                                        'critical' => 'Critical',
                                    ]),
                            ]),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('test_number')
                    ->label('Test #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->searchable(['patient.first_name', 'patient.last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('patient.patient_id')
                    ->label('Patient ID')
                    ->searchable(),

                Tables\Columns\TextColumn::make('test_name')
                    ->label('Test Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('test_category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hematology' => 'danger',
                        'biochemistry' => 'primary',
                        'microbiology' => 'warning',
                        'immunology' => 'info',
                        'pathology' => 'success',
                        'radiology' => 'gray',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('sample_type')
                    ->badge(),

                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'routine' => 'success',
                        'urgent' => 'warning',
                        'stat' => 'danger',
                        'critical' => 'danger',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ordered' => 'warning',
                        'collected' => 'info',
                        'processing' => 'primary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('result_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'normal' => 'success',
                        'abnormal' => 'warning',
                        'critical' => 'danger',
                        default => 'gray',
                    })
                    ->default('Pending'),

                Tables\Columns\TextColumn::make('doctor.user.name')
                    ->label('Ordered By')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ordered_at')
                    ->label('Ordered Date')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('cost')
                    ->money('LYD')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'ordered' => 'Ordered',
                        'collected' => 'Collected',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('test_category')
                    ->options([
                        'hematology' => 'Hematology',
                        'biochemistry' => 'Biochemistry',
                        'microbiology' => 'Microbiology',
                        'immunology' => 'Immunology',
                        'pathology' => 'Pathology',
                        'radiology' => 'Radiology',
                        'cardiology' => 'Cardiology',
                        'endocrinology' => 'Endocrinology',
                        'toxicology' => 'Toxicology',
                        'genetics' => 'Genetics',
                        'other' => 'Other',
                    ]),

                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'routine' => 'Routine',
                        'urgent' => 'Urgent',
                        'stat' => 'STAT',
                        'critical' => 'Critical',
                    ]),

                Tables\Filters\SelectFilter::make('result_status')
                    ->options([
                        'normal' => 'Normal',
                        'abnormal' => 'Abnormal',
                        'critical' => 'Critical',
                    ]),

                Tables\Filters\Filter::make('ordered_at')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('ordered_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('ordered_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('collect_sample')
                    ->label('Collect Sample')
                    ->icon('heroicon-o-beaker')
                    ->color('info')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'collected',
                            'collected_at' => now(),
                            'technician_id' => auth()->id(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Sample Collected')
                            ->body('Sample has been collected successfully.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->status === 'ordered'),

                Tables\Actions\Action::make('start_processing')
                    ->label('Start Processing')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->action(function ($record) {
                        $record->update(['status' => 'processing']);

                        \Filament\Notifications\Notification::make()
                            ->title('Processing Started')
                            ->body('Test processing has been started.')
                            ->info()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->status === 'collected'),

                Tables\Actions\Action::make('complete_test')
                    ->label('Complete Test')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('result')
                            ->label('Test Result')
                            ->required()
                            ->rows(3),
                        Forms\Components\TextInput::make('normal_range')
                            ->label('Normal Range'),
                        Forms\Components\Select::make('result_status')
                            ->options([
                                'normal' => 'Normal',
                                'abnormal' => 'Abnormal',
                                'critical' => 'Critical',
                            ])
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                            'result' => $data['result'],
                            'normal_range' => $data['normal_range'],
                            'result_status' => $data['result_status'],
                            'technician_id' => auth()->id(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Test Completed')
                            ->body('Test has been completed successfully.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => in_array($record->status, ['collected', 'processing'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('collect_samples')
                        ->label('Collect Samples')
                        ->icon('heroicon-o-beaker')
                        ->color('info')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if ($record->status === 'ordered') {
                                    $record->update([
                                        'status' => 'collected',
                                        'collected_at' => now(),
                                        'technician_id' => auth()->id(),
                                    ]);
                                }
                            });
                        }),
                ]),
            ])
            ->defaultSort('ordered_at', 'desc');
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
            'index' => Pages\ListLabTests::route('/'),
            'create' => Pages\CreateLabTest::route('/create'),
            'edit' => Pages\EditLabTest::route('/{record}/edit'),
        ];
    }
}
