<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DoctorResource\Pages;
use App\Filament\Resources\DoctorResource\RelationManagers;
use App\Models\Doctor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DoctorResource extends Resource
{
    protected static ?string $model = Doctor::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Doctor Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->required()
                                    ->preload(),

                                Forms\Components\TextInput::make('doctor_id')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->default(fn () => 'DOC-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT)),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('specialty')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('license_number')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('years_of_experience')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),

                                Forms\Components\TextInput::make('consultation_fee')
                                    ->numeric()
                                    ->prefix('LYD')
                                    ->default(0),

                                Forms\Components\TextInput::make('room_number')
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Textarea::make('education')
                            ->rows(3),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TimePicker::make('available_hours_start')
                                    ->label('Available From'),

                                Forms\Components\TimePicker::make('available_hours_end')
                                    ->label('Available Until'),
                            ]),

                        Forms\Components\CheckboxList::make('available_days')
                            ->options([
                                'sunday' => 'Sunday',
                                'monday' => 'Monday',
                                'tuesday' => 'Tuesday',
                                'wednesday' => 'Wednesday',
                                'thursday' => 'Thursday',
                                'friday' => 'Friday',
                                'saturday' => 'Saturday',
                            ])
                            ->columns(4),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('phone_extension')
                                    ->maxLength(255),

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
                Tables\Columns\TextColumn::make('doctor_id')
                    ->label('Doctor ID')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('specialty')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('license_number')
                    ->label('License #')
                    ->searchable(),

                Tables\Columns\TextColumn::make('years_of_experience')
                    ->label('Experience')
                    ->suffix(' years')
                    ->sortable(),

                Tables\Columns\TextColumn::make('consultation_fee')
                    ->money('LYD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('room_number')
                    ->label('Room')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('available_hours')
                    ->label('Available Hours')
                    ->getStateUsing(fn ($record) =>
                        $record->available_hours_start && $record->available_hours_end
                            ? $record->available_hours_start . ' - ' . $record->available_hours_end
                            : 'Not Set'
                    )
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('user.phone')
                    ->label('Phone')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('specialty')
                    ->options(function () {
                        return \App\Models\Doctor::distinct()->pluck('specialty', 'specialty')->toArray();
                    }),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),

                Tables\Filters\Filter::make('consultation_fee')
                    ->form([
                        Forms\Components\TextInput::make('min_fee')
                            ->label('Minimum Fee')
                            ->numeric()
                            ->prefix('LYD'),
                        Forms\Components\TextInput::make('max_fee')
                            ->label('Maximum Fee')
                            ->numeric()
                            ->prefix('LYD'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_fee'],
                                fn (Builder $query, $fee): Builder => $query->where('consultation_fee', '>=', $fee),
                            )
                            ->when(
                                $data['max_fee'],
                                fn (Builder $query, $fee): Builder => $query->where('consultation_fee', '<=', $fee),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view_schedule')
                    ->label('View Schedule')
                    ->icon('heroicon-o-calendar')
                    ->color('info')
                    ->url(fn ($record) => route('filament.admin.resources.appointments.index', ['doctor' => $record->id])),

                Tables\Actions\Action::make('toggle_availability')
                    ->label(fn ($record) => $record->is_active ? 'Mark Unavailable' : 'Mark Available')
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
            ->defaultSort('user.name', 'asc');
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
            'index' => Pages\ListDoctors::route('/'),
            'create' => Pages\CreateDoctor::route('/create'),
            'edit' => Pages\EditDoctor::route('/{record}/edit'),
        ];
    }
}
