<?php

namespace App\Http\Controllers;

use App\Models\Barber;
use App\Models\Category;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if (! $user || $user->role !== 'client') {
            abort(403);
        }

        $appointments = $user->appointments()->with(['barber'])->get();
        $categories = Category::query()->get()->keyBy('id');

        return view('appointments.index', [
            'user' => $user,
            'appointments' => $appointments,
            'categories' => $categories,
        ]);
    }

    public function edit(Request $request, Event $appointment): View
    {
        $user = $request->user();

        if (! $user || $user->role !== 'client' || $appointment->organizer_id !== $user->id) {
            abort(403);
        }

        $categories = Category::query()->orderBy('name')->get();
        $barbers = Barber::query()->with('user')->get();

        return view('appointments.edit', [
            'user' => $user,
            'appointment' => $appointment->load('barber'),
            'categories' => $categories,
            'barbers' => $barbers,
        ]);
    }

    public function update(Request $request, Event $appointment): RedirectResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'client' || $appointment->organizer_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string'],
            'barber' => ['required', 'integer', 'exists:users,id'],
            'start' => ['required', 'date'],
            'startTime' => ['required', 'date_format:H:i'],
            'body' => ['nullable', 'string', 'max:500'],
        ]);

        $barber = Barber::query()->where('user_id', $validated['barber'])->first();

        if (! $barber) {
            return back()->withErrors([
                'barber' => 'Выбранный барбер недоступен для записи.',
            ])->withInput();
        }

        $startDate = Carbon::parse($validated['start'])->toDateString();
        $start = Carbon::parse($startDate . ' ' . $validated['startTime'])->seconds(0);
        $end = $start->copy()->addHour();

        $dayKey = strtolower($start->englishDayOfWeek);
        $workingDays = collect($barber->working_days ?? []);

        if (! $workingDays->contains($dayKey)) {
            return back()->withErrors([
                'start' => 'Выбранный барбер не работает в этот день.',
            ])->withInput();
        }

        $shiftStart = Carbon::parse($startDate . ' ' . $barber->start_working_time);
        $shiftEnd = Carbon::parse($startDate . ' ' . $barber->end_working_time);

        if ($start->lt($shiftStart) || $end->gt($shiftEnd)) {
            return back()->withErrors([
                'startTime' => 'Выбранное время выходит за пределы рабочего дня барбера.',
            ])->withInput();
        }

        if ($start->lt(Carbon::now())) {
            return back()->withErrors([
                'start' => 'Нельзя перенести запись на прошедшее время.',
            ])->withInput();
        }

        $hasOverlap = Event::query()
            ->where('barber_id', $validated['barber'])
            ->whereDate('start', $startDate)
            ->whereIn('status', Event::blockingStatuses())
            ->where('id', '!=', $appointment->id)
            ->where(function ($query) use ($start, $end) {
                $query->where('start', '<', $end)
                    ->where('end', '>', $start);
            })
            ->exists();

        if ($hasOverlap) {
            return back()->withErrors([
                'startTime' => 'Выбранное время уже занято.',
            ])->withInput();
        }

        $appointment->fill([
            'subject' => $validated['subject'],
            'body' => $validated['body'] ?? null,
            'number' => $validated['number'],
            'category' => $validated['category'],
            'barber_id' => $validated['barber'],
            'start' => $start,
            'end' => $end,
        ]);

        $appointment->save();

        return redirect()
            ->route('appointments.index')
            ->with('appointmentUpdated', 'Запись успешно обновлена.');
    }

    public function destroy(Request $request, Event $appointment): RedirectResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'client' || $appointment->organizer_id !== $user->id) {
            abort(403);
        }

        $appointment->delete();

        return redirect()
            ->route('appointments.index')
            ->with('appointmentDeleted', 'Запись успешно удалена.');
    }
}
