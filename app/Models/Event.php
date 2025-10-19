<?php

namespace App\Models;

use Buildix\Timex\Models\Event as BaseEvent;
use Buildix\Timex\Traits\TimexTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Event extends BaseEvent
{
    use HasUuids, TimexTrait, HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_NO_SHOW = 'no_show';
    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = [];

    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
        'isAllDay' => 'boolean',
        'participants' => 'array',
        'attachments' => 'array',
    ];

    protected $appends = [
        'startTime',
        'endTime',
        'organizer',
    ];

    public static function statusOptions(): array
    {
        return [
            self::STATUS_SCHEDULED => 'Запланирован',
            self::STATUS_COMPLETED => 'Приём состоялся',
            self::STATUS_NO_SHOW => 'Клиент не пришёл',
            self::STATUS_CANCELLED => 'Приём отменён',
        ];
    }

    public static function blockingStatuses(): array
    {
        return [
            self::STATUS_SCHEDULED,
            self::STATUS_COMPLETED,
            self::STATUS_NO_SHOW,
        ];
    }

    public function getTable()
    {
        return config('timex.tables.event.name', 'timex_events');
    }

    protected static function booted(): void
    {
        static::saving(function (self $event): void {
            if ($event->barber_id) {
                $event->participants = [$event->barber_id];
            }

            if ($event->start && ! $event->end) {
                $event->end = Carbon::parse($event->start)->addHour();
            }

            if (! $event->organizer_id && Auth::check()) {
                $event->organizer_id = Auth::id();
            }
        });
    }

    public function getStartTimeAttribute(): ?string
    {
        return $this->start?->format('H:i');
    }

    public function getEndTimeAttribute(): ?string
    {
        return $this->end?->format('H:i');
    }

    public function getOrganizerAttribute(): ?int
    {
        return $this->organizer_id;
    }

    public function setOrganizerAttribute(?int $value): void
    {
        $this->organizer_id = $value;
    }

    public function category()
    {
        return $this->hasOne(self::getCategoryModel());
    }

    public function barber(): BelongsTo
    {
        return $this->belongsTo(User::class, 'barber_id');
    }

    public function organizerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }
}
