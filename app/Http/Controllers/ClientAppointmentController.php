<?php

namespace App\Http\Controllers;

use App\Models\Barber;
use App\Models\Category;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class ClientAppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless($user?->isClient(), 403);

        $appointments = Event::query()
            ->with(['barber'])
            ->where('organizer_id', $user->id)
            ->orderByDesc('start')
            ->get();

        return view('profile.appointments.index', [
            'user' => $user,
            'appointments' => $appointments,
            'statusOptions' => Event::statusOptions(),
            'categoryNames' => Category::query()->pluck('name', 'id'),
        ]);
    }

    public function edit(Request $request, Event $appointment): View
    {
        $user = $request->user();

        abort_unless($user?->isClient(), 403);
        abort_unless($appointment->organizer_id === $user->id, 403);

        return view('profile.appointments.edit', [
            'user' => $user,
            'appointment' => $appointment,
            'barbers' => Barber::query()->with('user')->get(),
            'categories' => Category::all(),
            'statusOptions' => Event::statusOptions(),
        ]);
    }

    public function update(Request $request, Event $appointment): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user?->isClient(), 403);
        abort_unless($appointment->organizer_id === $user->id, 403);

        $validated = $request->validate(
            [
                'subject' => ['required', 'string', 'max:255'],
                'number' => ['required', 'string', 'max:255'],
                'category' => ['required', 'string'],
                'barber' => ['required', 'integer', 'exists:users,id'],
                'start' => ['required', 'date'],
                'startTime' => ['required', 'date_format:H:i'],
                'status' => ['required', 'string', 'in:' . implode(',', array_keys(Event::statusOptions()))],
                'body' => ['nullable', 'string', 'max:500'],
            ],
            [
                'startTime.date_format' => __('Укажите время в формате ЧЧ:ММ.'),
            ]
        );

        $barber = Barber::query()->where('user_id', $validated['barber'])->first();

        if (! $barber) {
            throw ValidationException::withMessages([
                'barber' => __('Выбранный барбер не найден.'),
            ]);
        }

        $startDate = Carbon::parse($validated['start'])->toDateString();
        $start = Carbon::parse($startDate . ' ' . $validated['startTime'])->seconds(0);
        $end = $start->copy()->addHour();

        $this->ensureBarberIsAvailable($barber, $start, $end, $appointment);

        $appointment->fill([
            'subject' => $validated['subject'],
            'body' => $validated['body'] ?? null,
            'number' => $validated['number'],
            'category' => $validated['category'],
            'barber_id' => $validated['barber'],
            'start' => $start,
            'end' => $end,
            'status' => $validated['status'],
            'participants' => [$validated['barber']],
        ])->save();

        return redirect()
            ->route('appointments.index')
            ->with('appointmentUpdated', __('Запись успешно обновлена.'));
    }

    private function ensureBarberIsAvailable(Barber $barber, Carbon $start, Carbon $end, Event $current): void
    {
        $workingDays = collect($barber->working_days ?? []);
        $dayKey = strtolower($start->englishDayOfWeek);

        if (! $workingDays->contains($dayKey)) {
            throw ValidationException::withMessages([
                'start' => __('В этот день барбер не работает.'),
            ]);
        }

        if (! $barber->start_working_time || ! $barber->end_working_time) {
            throw ValidationException::withMessages([
                'start' => __('Расписание барбера не настроено.'),
            ]);
        }

        $shiftStart = Carbon::parse($start->toDateString() . ' ' . $barber->start_working_time);
        $shiftEnd = Carbon::parse($start->toDateString() . ' ' . $barber->end_working_time);

        if ($start->lt($shiftStart) || $end->gt($shiftEnd)) {
            throw ValidationException::withMessages([
                'startTime' => __('Выбранное время выходит за пределы рабочего дня.'),
            ]);
        }

        $now = Carbon::now()->seconds(0);
        if (! $start->equalTo($current->start) && $start->lt($now)) {
            throw ValidationException::withMessages([
                'startTime' => __('Нельзя перенести запись на прошедшее время.'),
            ]);
        }

        $hasOverlap = Event::query()
            ->where('barber_id', $barber->user_id)
            ->whereDate('start', $start->toDateString())
            ->where('id', '!=', $current->id)
            ->whereIn('status', Event::blockingStatuses())
            ->where(function ($query) use ($start, $end) {
                $query->where('start', '<', $end)
                    ->where('end', '>', $start);
            })
            ->exists();

        if ($hasOverlap) {
            throw ValidationException::withMessages([
                'startTime' => __('Выбранное время недоступно.'),
            ]);
        }
    }
}
