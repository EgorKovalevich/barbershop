<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Widgets\PaymentStatsOverview;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Payment;
use Filament\Resources\Form;
use Filament\Resources\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\PaymentResource\Pages;
use HusamTariq\FilamentTimePicker\Forms\Components\TimePickerField;


class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?int $navigationSort = 2;

    protected static ?string $breadcrumb = 'Заработок';

    protected static ?string $pluralModelLabel = 'Заработок';

    protected static ?string $slug = 'Заработок';


    protected static ?string $recordTitleAttribute = 'Заработок';

    protected static ?string $navigationIcon = 'heroicon-o-cash';

    protected static ?string $navigationLabel = 'Заработок';

    protected static ?string $navigationGroup = 'Ресурсы';


    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->fullname;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Значение' => $record->category->amount,
            'Дата оплаты' => Carbon::parse($record->payment_date)->format('d-m-Y'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['fullname'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('fullname')
                            ->label('Полное Имя')
                            ->placeholder('Иванов Иван Иванович')
                            ->required(),
                        Forms\Components\Select::make('category')
                            ->label('Категория')
                            ->relationship('category', 'name')
                            ->required(),
                        TimePickerField::make('payment_time')
                            ->label('Время оплаты')
                            ->okLabel('Confirm')
                            ->cancelLabel('Cancel')
                            ->required(),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Дата оплаты')
                            ->placeholder('нояб. 24, 2024')
                            ->maxDate(now())
                            ->required(),
                        Forms\Components\Toggle::make('paid')
                            ->label('Оплачено'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fullname')
                    ->label('Полное Имя')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Категория')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('category.amount')
                    ->label('Цена')
                    ->prefix('Br ')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('paid')
                    ->label('Оплачено')
                    ->sortable()
                    ->searchable()
                    ->boolean(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Дата оплаты')
                    ->description(fn($record)=> $record->payment_time)
                    ->searchable()
                    ->sortable()
                    ->date(),
            ])->defaultSort('payment_date')
            ->filters([
                Tables\Filters\Filter::make('payment_date')
                    ->form([
                        Forms\Components\DatePicker::make('payment_date_from')
                            ->label('Дата оплаты с момента')
                            ->placeholder('нояб. 20, 2024')
                            ->maxDate(now()),
                        Forms\Components\DatePicker::make('payment_date_until')
                            ->label('Дата оплаты до')
                            ->placeholder('нояб. 24, 2024')
                            ->maxDate(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['payment_date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '>=', $date),
                            )
                            ->when(
                                $data['payment_date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['payment_date_from'] ?? null) {
                            $indicators['from'] = 'Дата оплаты с момента' . Carbon::parse($data['payment_date_from'])->toFormattedDateString();
                        }

                        if ($data['payment_date_until'] ?? null) {
                            $indicators['until'] = 'Дата оплаты до ' . Carbon::parse($data['payment_date_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
    public static function getWidgets(): array
    {
        return [
            PaymentResource\Widgets\PaymentStatsOverview::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
