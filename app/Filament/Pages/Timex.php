<?php

namespace App\Filament\Pages;

use App\Filament\Resources\EventResource as EventFilamentResource;
use App\Models\Event;
use Buildix\Timex\Pages\Timex as BaseTimex;
use Buildix\Timex\Events\EventItem;
use Buildix\Timex\Events\InteractWithEvents;
use Buildix\Timex\Traits\TimexTrait;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Pages\Actions\Action;
use Filament\Resources\Pages\Concerns\UsesResourceForm;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Timex extends BaseTimex
{
    use TimexTrait;
    use InteractWithEvents;
    use UsesResourceForm;

    protected static function shouldRegisterNavigation(): bool
    {
        if (!config('timex.pages.shouldRegisterNavigation')){
            return false;
        }
        if (config('timex.pages.enablePolicy',false) && \Gate::getPolicyFor(self::getModel()) && !\Gate::allows('viewAny',self::getModel())){
            return false;
        }

        return true;
    }

    protected function getHeading(): string|Htmlable
    {
        return " ";
    }

    public function monthNameChanged($data,$year)
    {
            $this->monthName = Carbon::create($data)->monthName.' '.$this->getYearFormat($data);
            $this->year = Carbon::create($data);
            $this->period = CarbonPeriod::create(Carbon::create($data)->firstOfYear(),'1 month',Carbon::create($data)->lastOfYear());
    }

    public function __construct()
    {
        $this->monthName = today()->monthName." ".today()->year;
        $this->year = today();
        $this->period = CarbonPeriod::create(Carbon::create($this->year->firstOfYear()),'1 month',$this->year->lastOfYear());

    }

    protected function getActions(): array
    {
        return [
                Action::make('openCreateModal')
                    ->label(trans('filament::resources/pages/create-record.title',
                            ['label' => Str::lower(__('timex::timex.model.label'))]))
                    ->icon(config('timex.pages.buttons.icons.createEvent'))
                    ->size('sm')
                    ->outlined(config('timex.pages.buttons.outlined'))
                    ->slideOver()
                    ->extraAttributes(['class' => '-mr-2'])
                    ->form($this->getResourceForm(2)->getSchema())
                    ->modalHeading(trans('timex::timex.model.label'))
                    ->modalWidth(config('timex.pages.modalWidth'))
                    ->action(fn(array $data) => $this->updateOrCreate($data))
                    ->modalActions([
                        Action::makeModalAction('submit')
                            ->label(trans('timex::timex.modal.submit'))
                            ->color(config('timex.pages.buttons.modal.submit.color','primary'))
                            ->outlined(config('timex.pages.buttons.modal.submit.outlined',false))
                            ->icon(config('timex.pages.buttons.modal.submit.icon.name',''))
                            ->submit(),
                        Action::makeModalAction('cancel')
                            ->label(trans('timex::timex.modal.cancel'))
                            ->color(config('timex.pages.buttons.modal.cancel.color','secondary'))
                            ->outlined(config('timex.pages.buttons.modal.cancel.outlined',false))
                            ->icon(config('timex.pages.buttons.modal.cancel.icon.name',''))
                            ->cancel(),
                    ]),
        ];
    }

    public static function getEvents(): array
    {
        $user = \Auth::user();

        $events = self::getModel()::orderBy('start')->get()
            ->filter(function ($event) use ($user) {
                if (! $user) {
                    return false;
                }

                return match ($user->role) {
                    'admin' => true,
                    'barber' => (int) $event->barber_id === $user->id,
                    default => (int) $event->organizer_id === $user->id,
                };
            })
            ->map(function ($event){
                return EventItem::make($event->id)
                    ->body($event->body)
                    ->category($event->category)
                    ->color($event->category)
                    ->end($event->end ? Carbon::parse($event->end) : null)
                    ->isAllDay($event->isAllDay)
                    ->subject($event->subject)
                    ->organizer($event->organizer_id)
                    ->participants($event?->participants ?? [])
                    ->start(Carbon::parse($event->start))
                    ->startTime($event?->startTime);
            })->toArray();

        return $events;
    }

    public function updateOrCreate($data)
    {
        $payload = $this->normalizePayload($data);

        if (! $this->record){
            $this->getModel()::query()->create($payload);
        }else{
            $this->getFormModel()::query()->find($this->getFormModel()->id)?->update($payload);
        }
        $this->dispatEventUpdates();
    }

    public function deleteEvent()
    {
        $this->getFormModel()->delete();
        $this->dispatEventUpdates();
    }

    public function dispatEventUpdates(): void
    {
        $this->emit('modelUpdated',['id' => $this->id]);
        $this->emit('updateWidget',['id' => $this->id]);
    }

    public function onEventClick($eventID)
    {
        $this->record = $eventID;
        $event = $this->getFormModel()->getAttributes();
        $this->mountAction('openCreateModal');

        if ($this->getFormModel()->getAttribute('organizer_id') !== \Auth::id()){
            $this->getMountedAction()
                ->modalContent(\view('timex::event.view',['data' => $event]))
                ->modalHeading($event['subject'])
                ->form([])
                ->modalActions([

                ]);
        }else{
            $start = isset($event['start']) ? Carbon::parse($event['start']) : null;
            $startDate = $start?->toDateString();
            $startTime = $start?->format('H:i');
            $barberId = $event['barber_id'] ?? null;
            $availableTimes = [];

            if ($startDate && $startTime && $barberId) {
                $availableTimes = EventFilamentResource::buildAvailableTimes((int) $barberId, $startDate, $startTime, $event['id'] ?? null);
            }

            $this->getMountedActionForm()
                ->fill([
                    ...$event,
                    'participants' => $this->getFormModel()?->participants,
                    'attachments' => $this->getFormModel()?->attachments,
                    'start_date' => $startDate,
                    'start_time' => $startTime,
                    'available_times' => $availableTimes,
                    'record_id' => $event['id'] ?? null,
                ]);
        }
    }

    public function onDayClick($timestamp)
    {
        if (config('timex.isDayClickEnabled',true)){
            if (config('timex.isPastCreationEnabled',false)){
                $this->onCreateClick($timestamp);
            }else{
                Carbon::createFromTimestamp($timestamp)->isBefore(Carbon::today()) ? '' : $this->onCreateClick($timestamp);
            }
        }
    }

    public function onCreateClick(int | string | null $timestamp = null)
    {
        $this->mountAction('openCreateModal');
        $start = isset($timestamp)
            ? Carbon::createFromTimestamp($timestamp)
            : today();

        $this->getMountedActionForm()
            ->fill([
                'start_date' => $start->toDateString(),
                'start_time' => null,
                'available_times' => [],
                'record_id' => null,
            ]);
    }

    protected function normalizePayload(array $data): array
    {
        if (isset($data['organizer']) && ! isset($data['organizer_id'])) {
            $data['organizer_id'] = $data['organizer'];
        }

        $payload = EventFilamentResource::prepareEventPayload($data);

        if (empty($payload['organizer_id']) && \Auth::check()) {
            $payload['organizer_id'] = \Auth::id();
        }

        return $payload;
    }

    protected function getHeader(): ?View
    {
        return \view('timex::header.header');
    }

    public function onNextDropDownYearClick()
    {
        $this->year = $this->year->addYear();
        $this->period = CarbonPeriod::create(Carbon::create($this->year->firstOfYear()),'1 month',$this->year->lastOfYear());
    }

    public function onPrevDropDownYearClick()
    {
        $this->year = $this->year->subYear();
        $this->period = CarbonPeriod::create(Carbon::create($this->year->firstOfYear()),'1 month',$this->year->lastOfYear());
    }


    public function getYearFormat($data)
    {
        return Carbon::create($data)->year;
    }

    public function loadAttachment($file): void
    {
        $this->redirect(Storage::url($file));
    }

}
