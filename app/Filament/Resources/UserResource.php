<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Resources\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use HusamTariq\FilamentTimePicker\Forms\Components\TimePickerField;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Hash;


class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 2;

    protected static ?string $breadcrumb = 'Барберы';

    protected static ?string $pluralModelLabel = 'Барберы';

    protected static ?string $modelLabel = 'Барбера';

    protected static ?string $slug = 'Барберы';

    protected static ?string $navigationLabel = 'Барберы';

    protected static ?string $navigationGroup = 'Ресурсы';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(3)->schema([
                Card::make([
                    TextInput::make('surname')
                        ->required()
                        ->label('Фамилия'),

                    TextInput::make('name')
                        ->required()
                        ->label('Имя'),

                    TextInput::make('patronymic')
                        ->required()
                        ->label('Отчество'),

                    Select::make('role')
                        ->options([
                            'client' => 'Клиент',
                            'barber' => 'Барбер',
                            'admin' => 'Администратор',
                        ])
                        ->default('barber')
                        ->required()
                        ->label('Роль'),


                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique(User::class, 'email', ignoreRecord: true)
                        ->label('Email'),

                    TextInput::make('password')
                        ->password()
                        ->required()
                        ->label('Пароль')
                        ->dehydrateStateUsing(fn($state) => Hash::make($state))
                        ->hiddenOn('edit'),

                    TextInput::make('number')
                        ->label('Номер телефона')
                        ->required()
                        ->nullable(),
                ])->columnSpan(2),

                Section::make('Рабочее время')
                    ->relationship('barber')
                    ->schema([
                        CheckboxList::make('working_days')
                            ->label('Рабочие дни')
                            ->options([
                                'monday' => 'Понедельник',
                                'tuesday' => 'Вторник',
                                'wednesday' => 'Среда',
                                'thursday' => 'Четверг',
                                'friday' => 'Пятница',
                                'saturday' => 'Суббота',
                                'sunday' => 'Воскресенье',
                            ])
                            ->columns(2)
                            ->required(),
                        TimePickerField::make('start_working_time')
                            ->label('Время начала')
                            ->required(),
                        TimePickerField::make('end_working_time')
                            ->label('Время окончания')
                            ->required(),
                    ])->columnSpan(1),
            ])

        ]);
    }


    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('role', 'barber');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('surname')
                    ->label('Фамилия')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Имя')
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->sortable(),
                TextColumn::make('barber.working_days')->label('Рабочие дни')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        if (! is_array($state) || empty($state)) {
                            return '-';
                        }

                        $labels = [
                            'monday' => 'Пн',
                            'tuesday' => 'Вт',
                            'wednesday' => 'Ср',
                            'thursday' => 'Чт',
                            'friday' => 'Пт',
                            'saturday' => 'Сб',
                            'sunday' => 'Вс',
                        ];

                        return collect($state)->map(fn($day) => $labels[$day] ?? $day)->join(', ');
                    }),
                TextColumn::make('barber.start_working_time')->label('Время начала')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        return $state ? Carbon::parse($state)->format('H:i') : '-';
                    }),
                TextColumn::make('barber.end_working_time')->label('Время окончания')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        return $state ? Carbon::parse($state)->format('H:i') : '-';
                    }),
                TextColumn::make('created_at')->dateTime()->label('Дата создания'),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($user) {
            $user->role = 'barber';
        });

        static::updating(function ($user) {
            if (!$user->isDirty('role')) {
                $user->role = 'barber';
            }
        });
    }
}

