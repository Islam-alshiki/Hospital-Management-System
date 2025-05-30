<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoomResource\Pages;
use App\Filament\Resources\RoomResource\RelationManagers;
use App\Models\Room;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationGroup = 'Ward & Room Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Room Information')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('room_number')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),

                                Forms\Components\Select::make('ward_id')
                                    ->relationship('ward', 'name')
                                    ->searchable()
                                    ->required()
                                    ->preload(),

                                Forms\Components\Select::make('department_id')
                                    ->relationship('department', 'name')
                                    ->searchable()
                                    ->required()
                                    ->preload(),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('room_type')
                                    ->options([
                                        'general' => 'General Ward',
                                        'private' => 'Private Room',
                                        'semi_private' => 'Semi-Private',
                                        'icu' => 'ICU',
                                        'emergency' => 'Emergency',
                                        'surgery' => 'Surgery',
                                        'maternity' => 'Maternity',
                                        'pediatric' => 'Pediatric',
                                        'isolation' => 'Isolation',
                                        'observation' => 'Observation',
                                    ])
                                    ->required(),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'available' => 'Available',
                                        'occupied' => 'Occupied',
                                        'maintenance' => 'Under Maintenance',
                                        'cleaning' => 'Being Cleaned',
                                        'reserved' => 'Reserved',
                                        'out_of_order' => 'Out of Order',
                                    ])
                                    ->default('available')
                                    ->required(),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('bed_count')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required(),

                                Forms\Components\TextInput::make('floor_number')
                                    ->numeric()
                                    ->minValue(1),

                                Forms\Components\TextInput::make('daily_rate')
                                    ->numeric()
                                    ->prefix('LYD')
                                    ->default(0),
                            ]),

                        Forms\Components\CheckboxList::make('amenities')
                            ->options([
                                'air_conditioning' => 'Air Conditioning',
                                'private_bathroom' => 'Private Bathroom',
                                'tv' => 'Television',
                                'wifi' => 'WiFi',
                                'phone' => 'Phone',
                                'refrigerator' => 'Refrigerator',
                                'oxygen_supply' => 'Oxygen Supply',
                                'cardiac_monitor' => 'Cardiac Monitor',
                                'ventilator' => 'Ventilator',
                                'suction' => 'Suction',
                            ])
                            ->columns(3),

                        Forms\Components\Textarea::make('description')
                            ->rows(2),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('last_cleaned')
                                    ->label('Last Cleaned'),

                                Forms\Components\DateTimePicker::make('next_maintenance')
                                    ->label('Next Maintenance'),
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
                Tables\Columns\TextColumn::make('room_number')
                    ->label('Room #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ward.name')
                    ->label('Ward')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('room_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'icu' => 'danger',
                        'emergency' => 'warning',
                        'surgery' => 'primary',
                        'private' => 'success',
                        'isolation' => 'gray',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'occupied' => 'warning',
                        'maintenance' => 'danger',
                        'cleaning' => 'info',
                        'reserved' => 'primary',
                        'out_of_order' => 'gray',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('bed_count')
                    ->label('Beds')
                    ->sortable(),

                Tables\Columns\TextColumn::make('floor_number')
                    ->label('Floor')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('daily_rate')
                    ->money('LYD')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('current_occupancy')
                    ->label('Occupancy')
                    ->getStateUsing(fn ($record) => $record->roomAssignments()->where('status', 'active')->count() . '/' . $record->bed_count),

                Tables\Columns\TextColumn::make('amenities_count')
                    ->label('Amenities')
                    ->getStateUsing(fn ($record) => is_array($record->amenities) ? count($record->amenities) : 0),

                Tables\Columns\TextColumn::make('last_cleaned')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'available' => 'Available',
                        'occupied' => 'Occupied',
                        'maintenance' => 'Under Maintenance',
                        'cleaning' => 'Being Cleaned',
                        'reserved' => 'Reserved',
                        'out_of_order' => 'Out of Order',
                    ]),

                Tables\Filters\SelectFilter::make('room_type')
                    ->options([
                        'general' => 'General Ward',
                        'private' => 'Private Room',
                        'semi_private' => 'Semi-Private',
                        'icu' => 'ICU',
                        'emergency' => 'Emergency',
                        'surgery' => 'Surgery',
                        'maternity' => 'Maternity',
                        'pediatric' => 'Pediatric',
                        'isolation' => 'Isolation',
                        'observation' => 'Observation',
                    ]),

                Tables\Filters\SelectFilter::make('ward_id')
                    ->relationship('ward', 'name')
                    ->label('Ward'),

                Tables\Filters\SelectFilter::make('department_id')
                    ->relationship('department', 'name')
                    ->label('Department'),

                Tables\Filters\Filter::make('available_rooms')
                    ->label('Available Rooms')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'available')),

                Tables\Filters\Filter::make('needs_maintenance')
                    ->label('Needs Maintenance')
                    ->query(fn (Builder $query): Builder => $query->where('next_maintenance', '<=', now())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('assign_patient')
                    ->label('Assign Patient')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('patient_id')
                            ->relationship('patient', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name . ' (' . $record->patient_id . ')')
                            ->searchable(['first_name', 'last_name', 'patient_id'])
                            ->required()
                            ->preload(),
                        Forms\Components\DateTimePicker::make('assigned_at')
                            ->default(now())
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        // Create room assignment logic here
                        \Filament\Notifications\Notification::make()
                            ->title('Patient Assigned')
                            ->body('Patient has been assigned to room ' . $record->room_number)
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->status === 'available'),

                Tables\Actions\Action::make('change_status')
                    ->label('Change Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options([
                                'available' => 'Available',
                                'occupied' => 'Occupied',
                                'maintenance' => 'Under Maintenance',
                                'cleaning' => 'Being Cleaned',
                                'reserved' => 'Reserved',
                                'out_of_order' => 'Out of Order',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Status Change Notes')
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update(['status' => $data['status']]);

                        \Filament\Notifications\Notification::make()
                            ->title('Status Updated')
                            ->body('Room status has been changed to ' . $data['status'])
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('mark_available')
                        ->label('Mark as Available')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['status' => 'available'])),
                    Tables\Actions\BulkAction::make('mark_maintenance')
                        ->label('Mark for Maintenance')
                        ->icon('heroicon-o-wrench')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['status' => 'maintenance'])),
                ]),
            ])
            ->defaultSort('room_number', 'asc');
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
            'index' => Pages\ListRooms::route('/'),
            'create' => Pages\CreateRoom::route('/create'),
            'edit' => Pages\EditRoom::route('/{record}/edit'),
        ];
    }
}
