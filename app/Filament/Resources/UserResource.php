<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 1;

    protected static ?string $label = 'Staff Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('employee_id')
                            ->label('Employee ID')
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'EMP-' . now()->format('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT)),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('national_id')
                                    ->label('National ID')
                                    ->unique(ignoreRecord: true),
                                Forms\Components\TextInput::make('phone')
                                    ->tel(),
                                Forms\Components\Select::make('gender')
                                    ->options([
                                        'male' => 'Male',
                                        'female' => 'Female',
                                    ]),
                            ]),

                        Forms\Components\DatePicker::make('date_of_birth')
                            ->maxDate(now()->subYears(18)),

                        Forms\Components\Textarea::make('address')
                            ->rows(2),
                    ])->columns(1),

                Forms\Components\Section::make('Employment Information')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('role')
                                    ->options([
                                        'admin' => 'Administrator',
                                        'doctor' => 'Doctor',
                                        'nurse' => 'Nurse',
                                        'pharmacist' => 'Pharmacist',
                                        'lab_staff' => 'Laboratory Staff',
                                        'receptionist' => 'Receptionist',
                                    ])
                                    ->required(),

                                Forms\Components\Select::make('department_id')
                                    ->relationship('department', 'name')
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\Select::make('shift')
                                    ->options([
                                        'morning' => 'Morning (6 AM - 2 PM)',
                                        'evening' => 'Evening (2 PM - 10 PM)',
                                        'night' => 'Night (10 PM - 6 AM)',
                                    ]),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('hire_date')
                                    ->default(now()),
                                Forms\Components\TextInput::make('salary')
                                    ->numeric()
                                    ->prefix('LYD'),
                            ]),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])->columns(1),

                Forms\Components\Section::make('Emergency Contact')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('emergency_contact_name'),
                                Forms\Components\TextInput::make('emergency_contact_phone')
                                    ->tel(),
                            ]),
                    ])->columns(1),

                Forms\Components\Section::make('Security')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required(fn ($context) => $context === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state)),

                        Forms\Components\Textarea::make('notes')
                            ->rows(3),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee_id')
                    ->label('Employee ID')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Full Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'doctor' => 'primary',
                        'nurse' => 'success',
                        'pharmacist' => 'warning',
                        'lab_staff' => 'info',
                        'receptionist' => 'gray',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('shift')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'morning' => 'success',
                        'evening' => 'warning',
                        'night' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('hire_date')
                    ->label('Hire Date')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('salary')
                    ->money('LYD')
                    ->sortable()
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
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Administrator',
                        'doctor' => 'Doctor',
                        'nurse' => 'Nurse',
                        'pharmacist' => 'Pharmacist',
                        'lab_staff' => 'Laboratory Staff',
                        'receptionist' => 'Receptionist',
                    ]),

                Tables\Filters\SelectFilter::make('department_id')
                    ->relationship('department', 'name')
                    ->label('Department'),

                Tables\Filters\SelectFilter::make('shift')
                    ->options([
                        'morning' => 'Morning (6 AM - 2 PM)',
                        'evening' => 'Evening (2 PM - 10 PM)',
                        'night' => 'Night (10 PM - 6 AM)',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),

                Tables\Filters\Filter::make('hire_date')
                    ->form([
                        Forms\Components\DatePicker::make('hired_from')
                            ->label('Hired From'),
                        Forms\Components\DatePicker::make('hired_until')
                            ->label('Hired Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['hired_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('hire_date', '>=', $date),
                            )
                            ->when(
                                $data['hired_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('hire_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('reset_password')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('new_password')
                            ->label('New Password')
                            ->password()
                            ->required()
                            ->minLength(8),
                        Forms\Components\TextInput::make('confirm_password')
                            ->label('Confirm Password')
                            ->password()
                            ->required()
                            ->same('new_password'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'password' => Hash::make($data['new_password'])
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Password Reset')
                            ->body('Password has been reset successfully.')
                            ->success()
                            ->send();
                    }),

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
                    Tables\Actions\BulkAction::make('view_details') // New bulk action
                            ->label('View Selected Details')
                            ->icon('heroicon-o-eye')
                            ->color('info')
                            ->url(function ($records) {
                                if(!$records) {
                                    return null;
                                }
                                // Get the IDs of the selected records
                                $recordIds = $records->pluck('id')->toArray();
    
                                // Generate the URL to your custom view, passing IDs as a query parameter
                                // Assuming 'your.custom.route.name' is the name of your route
                                // and you have a route like: Route::get('/custom-view', [YourController::class, 'showCustomView'])->name('your.custom.route.name');
                                // or a Filament Page like: Route::get('/admin/custom-view', \App\Filament\Pages\YourCustomPage::class)->name('your.custom.route.name');
                                return route('your.custom.route.name', ['ids' => implode(',', $recordIds)]);
                            })
                            //->shouldOpenInNewTab(), // Optional: Opens the URL in a new tab
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
