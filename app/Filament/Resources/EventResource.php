<?php

namespace App\Filament\Resources;

use App\Models\Barber;
use App\Models\Category;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Event;

use Filament\Resources\Form;
use Filament\Resources\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Buildix\Timex\Traits\TimexTrait;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\EventResource\Pages;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EventResource extends Resource
{
    use TimexTrait;

    protected static ?string $recordTitleAttribute = 'Объекты';

    protected $chosenStartTime;

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->subject;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Число' => $record->number,
            'Дата' => optional($record->start)->format('d-m-Y'),
            'Время' => $record->startTime,
        ];
    }

    public static function getCategoryModel(): string
    {
        return Category::class;
    }

    public static function getModel(): string
    {
        return config('timex.models.event');
    }

    public static function getModelLabel(): string
    {
        return trans('timex::timex.model.label');
    }

    public static function getPluralModelLabel(): string
    {
        return trans('timex::timex.model.pluralLabel');
    }

    public static function getSlug(): string
    {
        return config('timex.resources.slug');
    }

    protected static function getNavigationGroup(): ?string
    {
        return config('timex.pages.group');
    }

    protected static function getNavigationSort(): ?int
    {
        return config('timex.resources.sort',1);
    }

    protected static function getNavigationIcon(): string
    {
        return config('timex.resources.icon');
    }

    protected static function shouldRegisterNavigation(): bool
    {
        if (!config('timex.resources.shouldRegisterNavigation')){
            return false;
        }
        if (!static::canViewAny()){
            return false;
        }

        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)->schema([
                    Card::make([
                        TextInput::make('subject')
                            ->label(trans('ФИО'))
                            ->placeholder('Иван Иванов')
                            ->required(),
                        TextInput::make('number')
                            ->label('Номер')
                            ->placeholder('+375(33)3333333')
                            ->required(),
                        Textarea::make('body')
                            ->label(trans('timex::timex.event.body'))
                            ->placeholder('Я опоздаю'),
                    ])->columnSpan(2),
                    Card::make([
                        Select::make('barber_id')
                            ->label('Барбер')
                            ->required()
                            ->searchable()
                            ->options(fn() => User::query()
                                ->where('role', 'barber')
                                ->orderBy('surname')
                                ->get()
                                ->mapWithKeys(fn(User $user) => [$user->id => trim($user->surname . ' ' . $user->name)])
                                ->toArray()
                            )
                            ->reactive()
                            ->afterStateUpdated(fn($state, callable $set, callable $get) => self::recalculateAvailableTimes($get, $set)),
                        Select::make('category')
                            ->label(trans('timex::timex.event.category'))
                            ->required()
                            ->columnSpanFull()
                            ->options(function () {
                                return self::isCategoryModelEnabled() ? self::getCategoryModel()::all()
                                    ->pluck(self::getCategoryModelColumn('value'), self::getCategoryModelColumn('key'))
                                    : config('timex.categories.labels');
                            }),
                        Grid::make(3)->schema([
                            DatePicker::make('start_date')
                                ->label('Дата')
                                ->required()
                                ->columnSpan(config('timex.resources.isStartEndHidden', false) ? 'full' : 2)
                                ->default(today())
                                ->minDate(today())
                                ->reactive()
                                ->afterStateUpdated(fn($state, callable $set, callable $get) => self::recalculateAvailableTimes($get, $set)),

                            Select::make('start_time')
                                ->label('Время')
                                ->required()
                                ->options(fn($get) => $get('available_times') ?? [])
                                ->afterStateUpdated(fn($state, callable $set, callable $get) => self::recalculateAvailableTimes($get, $set)),
                        ]),

                        Select::make('status')
                            ->label('Статус')
                            ->required()
                            ->options(Event::statusOptions())
                            ->default(Event::STATUS_SCHEDULED),
                        Hidden::make('available_times')
                            ->default([])
                            ->dehydrated(false),
                        Hidden::make('record_id')
                            ->dehydrated(false)
                            ->default(fn(?Event $record) => $record?->id),
                    ])->columnSpan(1),
                ]),
            ]);
    }

    public static function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['start'])) {
            $start = Carbon::parse($data['start']);
            $data['start_date'] = $start->toDateString();
            $data['start_time'] = $start->format('H:i');
            $data['record_id'] = $data['id'] ?? null;

            if (! empty($data['barber_id'])) {
                $data['available_times'] = self::buildAvailableTimes(
                    (int) $data['barber_id'],
                    $data['start_date'],
                    $data['start_time'],
                    $data['record_id'] ?? null
                );
            }
        }

        return $data;
    }

    public static function prepareEventPayload(array $data): array
    {
        if (! empty($data['start_date']) && ! empty($data['start_time'])) {
            $start = Carbon::parse($data['start_date'].' '.$data['start_time']);
            $data['start'] = $start;
            $data['end'] = $start->copy()->addHour();
        }

        unset($data['start_date'], $data['start_time'], $data['available_times'], $data['record_id']);

        if (isset($data['status']) && ! array_key_exists($data['status'], Event::statusOptions())) {
            $data['status'] = Event::STATUS_SCHEDULED;
        }

        if (! empty($data['barber_id'])) {
            $data['barber_id'] = (int) $data['barber_id'];
            $data['participants'] = [$data['barber_id']];
        }

        if (empty($data['status'])) {
            $data['status'] = Event::STATUS_SCHEDULED;
        }

        if (array_key_exists('organizer', $data)) {
            if (! isset($data['organizer_id']) && $data['organizer']) {
                $data['organizer_id'] = $data['organizer'];
            }

            unset($data['organizer']);
        }

        return $data;
    }

    protected static function recalculateAvailableTimes(callable $get, callable $set): void
    {
        $barberId = $get('barber_id');
        $date = $get('start_date');
        $currentTime = $get('start_time');

        if (! $barberId || ! $date) {
            $set('available_times', []);
            return;
        }

        $recordId = $get('record_id');
        $slots = self::buildAvailableTimes((int) $barberId, $date, $currentTime, $recordId);

        $set('available_times', $slots);
    }

    public static function buildAvailableTimes(int $barberId, string $date, ?string $currentTime = null, ?string $ignoreEventId = null): array
    {
        $barber = Barber::query()->where('user_id', $barberId)->first();

        if (! $barber) {
            return [];
        }

        $dayKey = Str::lower(Carbon::parse($date)->englishDayOfWeek);
        $workingDays = collect($barber->working_days ?? []);

        if (! $workingDays->contains($dayKey)) {
            return [];
        }

        $startOfDay = Carbon::parse($date.' '.$barber->start_working_time);
        $endOfDay = Carbon::parse($date.' '.$barber->end_working_time);
        $now = Carbon::now();

        $slots = [];
        $cursor = $startOfDay->copy();

        while ($cursor->copy()->addHour()->lte($endOfDay)) {
            if ($cursor->greaterThan($now)) {
                $slots[$cursor->format('H:i')] = $cursor->format('H:i');
            }
            $cursor->addHour();
        }

        $busyStarts = Event::query()
            ->when($ignoreEventId, fn($query) => $query->where('id', '!=', $ignoreEventId))
            ->where('barber_id', $barberId)
            ->whereDate('start', $date)
            ->whereIn('status', Event::blockingStatuses())
            ->get(['start', 'end']);

        foreach ($busyStarts as $event) {
            $eventStart = Carbon::parse($event->start);
            $eventEnd = $event->end
                ? Carbon::parse($event->end)
                : $eventStart->copy()->addHour();

            foreach ($slots as $time => $label) {
                $slotStart = Carbon::parse($date.' '.$time);
                $slotEnd = $slotStart->copy()->addHour();

                if ($slotStart->lt($eventEnd) && $slotEnd->gt($eventStart)) {
                    unset($slots[$time]);
                }
            }
        }

        if ($currentTime && ! array_key_exists($currentTime, $slots)) {
            $slots[$currentTime] = $currentTime;
            ksort($slots);
        }

        return $slots;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject')
                    ->searchable()
                    ->sortable()
                    ->label('Имя'),
                BadgeColumn::make('number')
                    ->label('Номер')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('body')
                    ->label(trans('timex::timex.event.body'))
                    ->sortable()
                    ->wrap()
                    ->limit(100),
                BadgeColumn::make('category')
                    ->label(trans('Вид стрижки'))
                    ->enum(config('timex.categories.labels'))
                    ->sortable()
                    ->formatStateUsing(function ($record){
                        if (\Str::isUuid($record->category)){
                            return self::getCategoryModel() == null ? "" : self::getCategoryModel()::findOrFail($record->category)->getAttributes()[self::getCategoryModelColumn('value')];
                        }else{
                            return config('timex.categories.labels')[$record->category] ?? "";
                        }
                    })
                    ->color(function ($record){
                        if (\Str::isUuid($record->category)){
                            return self::getCategoryModel() == null ? "primary" :self::getCategoryModel()::findOrFail($record->category)->getAttributes()[self::getCategoryModelColumn('color')];
                        }else{
                            return config('timex.categories.colors')[$record->category] ?? "primary";
                        }
                    }),
                TextColumn::make('barber.surname')
                    ->label('Барбер')
                    ->sortable()
                    ->formatStateUsing(fn($state, Event $record) => $record->barber
                        ? trim($record->barber->surname.' '.$record->barber->name)
                        : '—'),
                TextColumn::make('start')
                    ->label('Начало')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('end')
                    ->label('Окончание')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Статус')
                    ->enum(Event::statusOptions())
                    ->colors([
                        'primary' => Event::STATUS_SCHEDULED,
                        'success' => Event::STATUS_COMPLETED,
                        'danger' => Event::STATUS_NO_SHOW,
                        'secondary' => Event::STATUS_CANCELLED,
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }

}
