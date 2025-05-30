<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillResource\Pages;
use App\Filament\Resources\BillResource\RelationManagers;
use App\Models\Bill;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BillResource extends Resource
{
    protected static ?string $model = Bill::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Billing & Payments';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Bill Information')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('patient_id')
                                    ->relationship('patient', 'first_name')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name . ' (' . $record->patient_id . ')')
                                    ->searchable(['first_name', 'last_name', 'patient_id'])
                                    ->required()
                                    ->preload(),

                                Forms\Components\TextInput::make('bill_number')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->default(fn () => 'BILL-' . now()->format('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT)),

                                Forms\Components\DatePicker::make('bill_date')
                                    ->required()
                                    ->default(now()),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('bill_type')
                                    ->options([
                                        'consultation' => 'Consultation',
                                        'procedure' => 'Procedure',
                                        'surgery' => 'Surgery',
                                        'lab_test' => 'Laboratory Test',
                                        'medicine' => 'Medicine',
                                        'room_charge' => 'Room Charge',
                                        'emergency' => 'Emergency',
                                        'other' => 'Other',
                                    ])
                                    ->required(),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'paid' => 'Paid',
                                        'partially_paid' => 'Partially Paid',
                                        'overdue' => 'Overdue',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default('pending')
                                    ->required(),
                            ]),
                    ])->columns(1),

                Forms\Components\Section::make('Bill Items')
                    ->schema([
                        Forms\Components\Repeater::make('billItems')
                            ->relationship()
                            ->schema([
                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('description')
                                            ->required()
                                            ->placeholder('Service description'),

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
                            ])
                            ->columns(1)
                            ->defaultItems(1)
                            ->addActionLabel('Add Item')
                            ->deleteActionLabel('Remove Item'),
                    ])->columns(1),

                Forms\Components\Section::make('Payment Information')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('subtotal')
                                    ->numeric()
                                    ->prefix('LYD')
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('tax_amount')
                                    ->numeric()
                                    ->prefix('LYD')
                                    ->default(0),

                                Forms\Components\TextInput::make('discount_amount')
                                    ->numeric()
                                    ->prefix('LYD')
                                    ->default(0),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('total_amount')
                                    ->numeric()
                                    ->prefix('LYD')
                                    ->required(),

                                Forms\Components\TextInput::make('paid_amount')
                                    ->numeric()
                                    ->prefix('LYD')
                                    ->default(0),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('insurance_provider_id')
                                    ->relationship('insuranceProvider', 'name')
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\TextInput::make('insurance_coverage_amount')
                                    ->numeric()
                                    ->prefix('LYD')
                                    ->default(0),
                            ]),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Due Date'),

                        Forms\Components\Textarea::make('notes')
                            ->rows(3),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bill_number')
                    ->label('Bill #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->searchable(['patient.first_name', 'patient.last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('patient.patient_id')
                    ->label('Patient ID')
                    ->searchable(),

                Tables\Columns\TextColumn::make('bill_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'consultation' => 'primary',
                        'procedure' => 'info',
                        'surgery' => 'warning',
                        'lab_test' => 'success',
                        'medicine' => 'gray',
                        'room_charge' => 'secondary',
                        'emergency' => 'danger',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->money('LYD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->money('LYD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance_due')
                    ->label('Balance Due')
                    ->money('LYD')
                    ->getStateUsing(fn ($record) => $record->total_amount - $record->paid_amount)
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'partially_paid' => 'info',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('bill_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('insuranceProvider.name')
                    ->label('Insurance')
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
                        'paid' => 'Paid',
                        'partially_paid' => 'Partially Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('bill_type')
                    ->options([
                        'consultation' => 'Consultation',
                        'procedure' => 'Procedure',
                        'surgery' => 'Surgery',
                        'lab_test' => 'Laboratory Test',
                        'medicine' => 'Medicine',
                        'room_charge' => 'Room Charge',
                        'emergency' => 'Emergency',
                        'other' => 'Other',
                    ]),

                Tables\Filters\Filter::make('bill_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('bill_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('bill_date', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('overdue_bills')
                    ->label('Overdue Bills')
                    ->query(fn (Builder $query): Builder => $query->where('due_date', '<', now())->where('status', '!=', 'paid')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('record_payment')
                    ->label('Record Payment')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('payment_amount')
                            ->label('Payment Amount')
                            ->numeric()
                            ->prefix('LYD')
                            ->required(),
                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'cash' => 'Cash',
                                'card' => 'Credit/Debit Card',
                                'bank_transfer' => 'Bank Transfer',
                                'insurance' => 'Insurance',
                                'check' => 'Check',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('payment_notes')
                            ->label('Payment Notes')
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        $newPaidAmount = $record->paid_amount + $data['payment_amount'];
                        $newStatus = $newPaidAmount >= $record->total_amount ? 'paid' : 'partially_paid';

                        $record->update([
                            'paid_amount' => $newPaidAmount,
                            'status' => $newStatus,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Payment Recorded')
                            ->body('Payment of LYD ' . number_format($data['payment_amount'], 2) . ' has been recorded.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'partially_paid', 'overdue'])),

                Tables\Actions\Action::make('print_bill')
                    ->label('Print Bill')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->action(function ($record) {
                        \Filament\Notifications\Notification::make()
                            ->title('Print Bill')
                            ->body('Bill printing feature will be implemented.')
                            ->info()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('mark_overdue')
                        ->label('Mark as Overdue')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['status' => 'overdue'])),
                ]),
            ])
            ->defaultSort('bill_date', 'desc');
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
            'index' => Pages\ListBills::route('/'),
            'create' => Pages\CreateBill::route('/create'),
            'edit' => Pages\EditBill::route('/{record}/edit'),
        ];
    }
}
