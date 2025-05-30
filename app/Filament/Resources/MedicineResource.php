<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MedicineResource\Pages;
use App\Filament\Resources\MedicineResource\RelationManagers;
use App\Models\Medicine;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MedicineResource extends Resource
{
    protected static ?string $model = Medicine::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = 'Pharmacy Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Medicine Information')
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

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->default(fn () => 'MED-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT)),
                                Forms\Components\TextInput::make('barcode')
                                    ->unique(ignoreRecord: true),
                                Forms\Components\Select::make('category')
                                    ->options([
                                        'analgesic' => 'Analgesic',
                                        'antibiotic' => 'Antibiotic',
                                        'antiviral' => 'Antiviral',
                                        'antifungal' => 'Antifungal',
                                        'antihistamine' => 'Antihistamine',
                                        'antidiabetic' => 'Antidiabetic',
                                        'antihypertensive' => 'Antihypertensive',
                                        'cardiovascular' => 'Cardiovascular',
                                        'respiratory' => 'Respiratory',
                                        'gastrointestinal' => 'Gastrointestinal',
                                        'neurological' => 'Neurological',
                                        'psychiatric' => 'Psychiatric',
                                        'dermatological' => 'Dermatological',
                                        'ophthalmological' => 'Ophthalmological',
                                        'vitamin' => 'Vitamin/Supplement',
                                        'vaccine' => 'Vaccine',
                                        'other' => 'Other',
                                    ])
                                    ->required(),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('dosage_form')
                                    ->options([
                                        'tablet' => 'Tablet',
                                        'capsule' => 'Capsule',
                                        'syrup' => 'Syrup',
                                        'injection' => 'Injection',
                                        'cream' => 'Cream',
                                        'ointment' => 'Ointment',
                                        'drops' => 'Drops',
                                        'inhaler' => 'Inhaler',
                                        'suppository' => 'Suppository',
                                        'patch' => 'Patch',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('strength')
                                    ->required(),
                                Forms\Components\Select::make('unit')
                                    ->options([
                                        'tablet' => 'Tablet',
                                        'capsule' => 'Capsule',
                                        'ml' => 'Milliliter',
                                        'mg' => 'Milligram',
                                        'g' => 'Gram',
                                        'vial' => 'Vial',
                                        'ampoule' => 'Ampoule',
                                        'bottle' => 'Bottle',
                                        'tube' => 'Tube',
                                        'box' => 'Box',
                                    ])
                                    ->required(),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->rows(2),

                        Forms\Components\Textarea::make('side_effects')
                            ->rows(2),

                        Forms\Components\Textarea::make('contraindications')
                            ->rows(2),
                    ])->columns(1),

                Forms\Components\Section::make('Stock Management')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('stock_quantity')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),
                                Forms\Components\TextInput::make('minimum_stock_level')
                                    ->numeric()
                                    ->default(10)
                                    ->required(),
                                Forms\Components\TextInput::make('maximum_stock_level')
                                    ->numeric(),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('expiry_date'),
                                Forms\Components\TextInput::make('batch_number'),
                            ]),
                    ])->columns(1),

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('purchase_price')
                                    ->numeric()
                                    ->prefix('LYD'),
                                Forms\Components\TextInput::make('selling_price')
                                    ->numeric()
                                    ->prefix('LYD')
                                    ->required(),
                                Forms\Components\TextInput::make('markup_percentage')
                                    ->numeric()
                                    ->suffix('%'),
                            ]),
                    ])->columns(1),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('requires_prescription')
                                    ->default(true),
                                Forms\Components\Toggle::make('is_controlled_substance')
                                    ->default(false),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->default(true),
                                Forms\Components\Toggle::make('track_expiry')
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
                    ->label('Medicine Code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name_ar')
                    ->label('Name (Arabic)')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'antibiotic' => 'danger',
                        'analgesic' => 'success',
                        'antidiabetic' => 'warning',
                        'cardiovascular' => 'info',
                        'vitamin' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('dosage_form')
                    ->label('Form')
                    ->badge(),

                Tables\Columns\TextColumn::make('strength')
                    ->label('Strength'),

                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->numeric()
                    ->color(fn ($record) => $record->stock_quantity <= $record->minimum_stock_level ? 'danger' : 'success')
                    ->weight(fn ($record) => $record->stock_quantity <= $record->minimum_stock_level ? 'bold' : 'normal'),

                Tables\Columns\TextColumn::make('minimum_stock_level')
                    ->label('Min Stock')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Price')
                    ->money('LYD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Expiry')
                    ->date()
                    ->color(fn ($record) => $record->expiry_date && $record->expiry_date->isPast() ? 'danger' :
                           ($record->expiry_date && $record->expiry_date->diffInDays() < 30 ? 'warning' : 'success'))
                    ->toggleable(),

                Tables\Columns\IconColumn::make('requires_prescription')
                    ->label('Rx Required')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_controlled_substance')
                    ->label('Controlled')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'analgesic' => 'Analgesic',
                        'antibiotic' => 'Antibiotic',
                        'antiviral' => 'Antiviral',
                        'antifungal' => 'Antifungal',
                        'antihistamine' => 'Antihistamine',
                        'antidiabetic' => 'Antidiabetic',
                        'antihypertensive' => 'Antihypertensive',
                        'cardiovascular' => 'Cardiovascular',
                        'respiratory' => 'Respiratory',
                        'gastrointestinal' => 'Gastrointestinal',
                        'neurological' => 'Neurological',
                        'psychiatric' => 'Psychiatric',
                        'dermatological' => 'Dermatological',
                        'ophthalmological' => 'Ophthalmological',
                        'vitamin' => 'Vitamin/Supplement',
                        'vaccine' => 'Vaccine',
                        'other' => 'Other',
                    ]),

                Tables\Filters\SelectFilter::make('dosage_form')
                    ->options([
                        'tablet' => 'Tablet',
                        'capsule' => 'Capsule',
                        'syrup' => 'Syrup',
                        'injection' => 'Injection',
                        'cream' => 'Cream',
                        'ointment' => 'Ointment',
                        'drops' => 'Drops',
                        'inhaler' => 'Inhaler',
                        'suppository' => 'Suppository',
                        'patch' => 'Patch',
                    ]),

                Tables\Filters\TernaryFilter::make('requires_prescription')
                    ->label('Requires Prescription'),

                Tables\Filters\TernaryFilter::make('is_controlled_substance')
                    ->label('Controlled Substance'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),

                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('stock_quantity', '<=', 'minimum_stock_level')),

                Tables\Filters\Filter::make('expired')
                    ->label('Expired')
                    ->query(fn (Builder $query): Builder => $query->where('expiry_date', '<', now())),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expiring Soon (30 days)')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('expiry_date', [now(), now()->addDays(30)])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('adjust_stock')
                    ->label('Adjust Stock')
                    ->icon('heroicon-o-plus-circle')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('adjustment')
                            ->label('Stock Adjustment')
                            ->numeric()
                            ->required()
                            ->helperText('Enter positive number to add stock, negative to reduce'),
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason for Adjustment')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'stock_quantity' => $record->stock_quantity + $data['adjustment']
                        ]);

                        // Here you could log the stock adjustment
                        \Filament\Notifications\Notification::make()
                            ->title('Stock Adjusted')
                            ->body("Stock adjusted by {$data['adjustment']} units. Reason: {$data['reason']}")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('mark_active')
                        ->label('Mark as Active')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('mark_inactive')
                        ->label('Mark as Inactive')
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
            'index' => Pages\ListMedicines::route('/'),
            'create' => Pages\CreateMedicine::route('/create'),
            'edit' => Pages\EditMedicine::route('/{record}/edit'),
        ];
    }
}
