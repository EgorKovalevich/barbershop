<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Barber;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the authenticated client's appointments.
     */
    public function index(): View
    {
        $events = $this->clientEvents();
        $categoryNames = Category::query()->pluck('name', 'id')->all();

        return view('client.appointments.index', [
            'events' => $events,
            'categoryNames' => $categoryNames,
            'statusLabels' => Event::statusOptions(),
        ]);
    }

    /**
     * Show the form for editing the specified appointment.
     */
    public function edit(Event $event): View
    {
        $this->authorizeEvent($event);

        $categories = Category::query()->pluck('name', 'id')->all();
        $barbers = User::query()
            ->where('role', 'barber')
            ->orderBy('surname')
            ->orderBy('name')
            ->orderBy('patronymic')
            ->get();

        return view('client.appointments.edit', [
            'event' => $event,
            'categories' => $categories,
            'barbers' => $barbers,
        ]);
    }

    /**
     * Update the specified appointment in storage.
     */
    public function update(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeEvent($event);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:255'],
            'category' => ['required', 'integer', Rule::exists('categories', 'id')],
            'barber_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'barber')],
            'start_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'body' => ['nullable', 'string', 'max:500'],
        ]);

        $barberProfile = Barber::query()->where('user_id', $validated['barber_id'])->first();

        if (! $barberProfile) {
            return back()->withInput()->withErrors([
                'barber_id' => 'Выбранный барбер недоступен для записи.',
            ]);
        }

        $startDate = Carbon::parse($validated['start_date'])->toDateString();
        $start = Carbon::parse($startDate.' '.$validated['start_time'])->seconds(0);
        $end = $start->copy()->addHour();

        $dayKey = strtolower($start->englishDayOfWeek);
        $workingDays = collect($barberProfile->working_days ?? []);

        if (! $workingDays->contains($dayKey)) {
            return back()->withInput()->withErrors([
                'start_date' => 'Барбер не работает в выбранный день недели.',
            ]);
        }

        $shiftStart = Carbon::parse($startDate.' '.$barberProfile->start_working_time);
        $shiftEnd = Carbon::parse($startDate.' '.$barberProfile->end_working_time);

        if ($start->lt($shiftStart) || $end->gt($shiftEnd)) {
            return back()->withInput()->withErrors([
                'start_time' => 'Выбранное время выходит за рамки рабочего дня барбера.',
            ]);
        }

        if ($start->lt(Carbon::now())) {
            return back()->withInput()->withErrors([
                'start_time' => 'Нельзя перенести запись на прошедшее время.',
            ]);
        }

        $hasOverlap = Event::query()
            ->where('barber_id', $validated['barber_id'])
            ->whereDate('start', $startDate)
            ->where($event->getKeyName(), '!=', $event->getKey())
            ->whereIn('status', Event::blockingStatuses())
            ->where(function ($query) use ($start, $end) {
                $query->where('start', '<', $end)
                    ->where('end', '>', $start);
            })
            ->exists();

        if ($hasOverlap) {
            return back()->withInput()->withErrors([
                'start_time' => 'В это время барбер уже занят.',
            ]);
        }

        $event->update([
            'subject' => $validated['subject'],
            'number' => $validated['number'],
            'category' => (string) $validated['category'],
            'barber_id' => $validated['barber_id'],
            'start' => $start,
            'end' => $end,
            'body' => $validated['body'] ?? null,
        ]);

        return redirect()
            ->route('client.appointments.index')
            ->with('clientAppointmentUpdated', 'Запись успешно обновлена.');
    }

    private function clientEvents(): Collection
    {
        return Event::query()
            ->with('barber')
            ->where('organizer_id', auth()->id())
            ->orderByDesc('start')
            ->get();
    }

    private function authorizeEvent(Event $event): void
    {
        if (! auth()->check() || (int) auth()->id() !== (int) $event->organizer_id) {
            abort(403);
        }
    }
}
