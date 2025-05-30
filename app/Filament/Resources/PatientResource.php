<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PatientResource\Pages;
use App\Filament\Resources\PatientResource\RelationManagers;
use App\Models\Patient;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Patient Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Patient Information')
                    ->schema([
                        Forms\Components\TextInput::make('patient_id')
                            ->label('Patient ID')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'P-' . now()->format('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT)),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('first_name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('last_name')
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('date_of_birth')
                                    ->required()
                                    ->maxDate(now()),
                                Forms\Components\Select::make('gender')
                                    ->options([
                                        'male' => 'Male',
                                        'female' => 'Female',
                                    ])
                                    ->required(),
                                Forms\Components\Select::make('blood_type')
                                    ->options([
                                        'A+' => 'A+', 'A-' => 'A-',
                                        'B+' => 'B+', 'B-' => 'B-',
                                        'AB+' => 'AB+', 'AB-' => 'AB-',
                                        'O+' => 'O+', 'O-' => 'O-',
                                    ]),
                            ]),
                    ])->columns(1),

                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('national_id')
                                    ->label('National ID')
                                    ->unique(ignoreRecord: true),
                                Forms\Components\TextInput::make('passport_number')
                                    ->label('Passport Number'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->email(),
                                Forms\Components\TextInput::make('phone')
                                    ->tel()
                                    ->required(),
                            ]),

                        Forms\Components\Textarea::make('address')
                            ->required()
                            ->rows(2),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('city')
                                    ->required(),
                                Forms\Components\TextInput::make('state'),
                                Forms\Components\TextInput::make('postal_code'),
                            ]),
                    ])->columns(1),

                Forms\Components\Section::make('Emergency Contact')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('emergency_contact_name')
                                    ->required(),
                                Forms\Components\TextInput::make('emergency_contact_phone')
                                    ->tel()
                                    ->required(),
                            ]),
                    ])->columns(1),

                Forms\Components\Section::make('Medical Information')
                    ->schema([
                        Forms\Components\Select::make('marital_status')
                            ->options([
                                'single' => 'Single',
                                'married' => 'Married',
                                'divorced' => 'Divorced',
                                'widowed' => 'Widowed',
                            ]),

                        Forms\Components\TagsInput::make('chronic_diseases')
                            ->placeholder('Add chronic diseases'),

                        Forms\Components\TagsInput::make('allergies')
                            ->placeholder('Add allergies'),

                        Forms\Components\Textarea::make('notes')
                            ->rows(3),
                    ])->columns(1),

                Forms\Components\Section::make('Insurance Information')
                    ->schema([
                        Forms\Components\Select::make('insurance_provider_id')
                            ->relationship('insuranceProvider', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('insurance_number'),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('patient_id')
                    ->label('Patient ID')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Full Name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('date_of_birth')
                    ->label('Date of Birth')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('age')
                    ->label('Age')
                    ->getStateUsing(fn ($record) => $record->date_of_birth->age . ' years'),

                Tables\Columns\TextColumn::make('gender')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'male' => 'blue',
                        'female' => 'pink',
                    }),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),

                Tables\Columns\TextColumn::make('blood_type')
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('insuranceProvider.name')
                    ->label('Insurance')
                    ->default('No Insurance'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gender')
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                    ]),

                Tables\Filters\SelectFilter::make('blood_type')
                    ->options([
                        'A+' => 'A+', 'A-' => 'A-',
                        'B+' => 'B+', 'B-' => 'B-',
                        'AB+' => 'AB+', 'AB-' => 'AB-',
                        'O+' => 'O+', 'O-' => 'O-',
                    ]),

                Tables\Filters\SelectFilter::make('insurance_provider_id')
                    ->relationship('insuranceProvider', 'name')
                    ->label('Insurance Provider'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('medical_records')
                    ->label('Medical Records')
                    ->icon('heroicon-o-document-text')
                    ->url(fn ($record) => route('filament.admin.resources.medical-records.index', ['patient' => $record->id])),
                Tables\Actions\Action::make('appointments')
                    ->label('Appointments')
                    ->icon('heroicon-o-calendar')
                    ->url(fn ($record) => route('filament.admin.resources.appointments.index', ['patient' => $record->id])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListPatients::route('/'),
            'create' => Pages\CreatePatient::route('/create'),
            'edit' => Pages\EditPatient::route('/{record}/edit'),
        ];
    }
}
