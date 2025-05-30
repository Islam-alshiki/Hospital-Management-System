<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InsuranceProviderResource\Pages;
use App\Filament\Resources\InsuranceProviderResource\RelationManagers;
use App\Models\InsuranceProvider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InsuranceProviderResource extends Resource
{
    protected static ?string $model = InsuranceProvider::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Insurance Provider Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('name_ar')
                                    ->label('Name (Arabic)')
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(10)
                                    ->uppercase(),

                                Forms\Components\Select::make('type')
                                    ->options([
                                        'government' => 'Government',
                                        'private' => 'Private',
                                        'international' => 'International',
                                        'corporate' => 'Corporate',
                                    ])
                                    ->required(),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('phone')
                                    ->tel(),

                                Forms\Components\TextInput::make('email')
                                    ->email(),

                                Forms\Components\TextInput::make('website')
                                    ->url(),
                            ]),

                        Forms\Components\Textarea::make('address')
                            ->rows(2),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('contact_person')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('contact_phone')
                                    ->tel(),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('coverage_percentage')
                                    ->numeric()
                                    ->suffix('%')
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(80),

                                Forms\Components\TextInput::make('max_coverage_amount')
                                    ->numeric()
                                    ->prefix('LYD'),

                                Forms\Components\TextInput::make('deductible_amount')
                                    ->numeric()
                                    ->prefix('LYD')
                                    ->default(0),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('requires_pre_approval')
                                    ->label('Requires Pre-approval')
                                    ->default(false),

                                Forms\Components\Toggle::make('is_active')
                                    ->default(true),
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->rows(3),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name_ar')
                    ->label('Name (Arabic)')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'government' => 'success',
                        'private' => 'primary',
                        'international' => 'info',
                        'corporate' => 'warning',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('coverage_percentage')
                    ->label('Coverage %')
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_coverage_amount')
                    ->label('Max Coverage')
                    ->money('LYD')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('contact_person')
                    ->label('Contact Person')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('requires_pre_approval')
                    ->label('Pre-approval Required')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('patients_count')
                    ->label('Patients')
                    ->getStateUsing(fn ($record) => $record->patients()->count()),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'government' => 'Government',
                        'private' => 'Private',
                        'international' => 'International',
                        'corporate' => 'Corporate',
                    ]),

                Tables\Filters\TernaryFilter::make('requires_pre_approval')
                    ->label('Requires Pre-approval'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),

                Tables\Filters\Filter::make('coverage_percentage')
                    ->form([
                        Forms\Components\TextInput::make('min_coverage')
                            ->label('Minimum Coverage %')
                            ->numeric(),
                        Forms\Components\TextInput::make('max_coverage')
                            ->label('Maximum Coverage %')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_coverage'],
                                fn (Builder $query, $coverage): Builder => $query->where('coverage_percentage', '>=', $coverage),
                            )
                            ->when(
                                $data['max_coverage'],
                                fn (Builder $query, $coverage): Builder => $query->where('coverage_percentage', '<=', $coverage),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view_patients')
                    ->label('View Patients')
                    ->icon('heroicon-o-users')
                    ->color('info')
                    ->url(fn ($record) => route('filament.admin.resources.patients.index', ['insurance_provider' => $record->id])),

                Tables\Actions\Action::make('toggle_status')
                    ->label(fn ($record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->action(fn ($record) => $record->update(['is_active' => !$record->is_active])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                ]),
            ])
            ->defaultSort('name', 'asc');
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
            'index' => Pages\ListInsuranceProviders::route('/'),
            'create' => Pages\CreateInsuranceProvider::route('/create'),
            'edit' => Pages\EditInsuranceProvider::route('/{record}/edit'),
        ];
    }
}
