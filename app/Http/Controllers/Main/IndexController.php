<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Category;
use App\Models\Barber;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class IndexController extends Controller
{
    public function show()
    {
        $category = Category::all();

        $barber = User::query()
            ->where('role', 'barber')
            ->with('barber')
            ->get();

        $period = CarbonPeriod::between(now(), now()->addMonths(1))->filter(function ($date) {
            return $date->isFuture() || $date->isToday();
        });

        if (session('success_message')) {
            Alert::success('Время запланированно!', session('success_message'));
        }

        if (session('error_message')) {
            Alert::error('Время недоступно!', session('error_message'));
        }

        return view('main.index', [
            'categories' => $category,
            'barbers' => $barber,
            'days' => $period,
        ]);
    }

    public function store(Request $request)
    {
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
            return back()->with('error_message', 'Выбранный барбер не найден.')->withInput();
        }

        $startDate = Carbon::parse($validated['start'])->toDateString();
        $start = Carbon::parse($startDate.' '.$validated['startTime'])->seconds(0);
        $end = $start->copy()->addHour();

        $dayKey = strtolower($start->englishDayOfWeek);
        $workingDays = collect($barber->working_days ?? []);

        if (! $workingDays->contains($dayKey)) {
            return back()->with('error_message', 'В этот день барбер не работает.')->withInput();
        }

        $shiftStart = Carbon::parse($startDate.' '.$barber->start_working_time);
        $shiftEnd = Carbon::parse($startDate.' '.$barber->end_working_time);

        if ($start->lt($shiftStart) || $end->gt($shiftEnd)) {
            return back()->with('error_message', 'Выбранное время выходит за пределы рабочего дня.')->withInput();
        }

        if ($start->lt(Carbon::now())) {
            return back()->with('error_message', 'Нельзя записаться на прошедшее время.')->withInput();
        }

        $hasOverlap = Event::query()
            ->where('barber_id', $validated['barber'])
            ->whereDate('start', $startDate)
            ->whereIn('status', Event::blockingStatuses())
            ->where(function ($query) use ($start, $end) {
                $query->where('start', '<', $end)
                    ->where('end', '>', $start);
            })
            ->exists();

        if ($hasOverlap) {
            return back()->with('error_message', 'Это время уже занято.')->withInput();
        }

        $event = Event::create([
            'subject' => $validated['subject'],
            'body' => $validated['body'] ?? null,
            'number' => $validated['number'],
            'category' => $validated['category'],
            'barber_id' => $validated['barber'],
            'start' => $start,
            'end' => $end,
            'status' => Event::STATUS_SCHEDULED,
            'organizer_id' => auth()->id(),
            'participants' => [$validated['barber']],
        ]);

        Payment::create([
            'fullname' => $validated['subject'],
            'category_id' => $validated['category'],
            'paid' => 0,
            'payment_date' => $startDate,
            'payment_time' => $start->format('H:i:s'),
            'who_created' => auth()->user()?->role ?? 'client',
        ]);

        return back()->with('success_message', sprintf(
            'Ваш приём запланирован на %s в %s.',
            $start->translatedFormat('d.m'),
            $start->format('H:i')
        ));
    }

    public function availableTimes(Request $request)
    {
        $validated = $request->validate([
            'barber' => ['required', 'integer', 'exists:users,id'],
            'date' => ['required', 'date'],
        ]);

        $barber = Barber::query()->where('user_id', $validated['barber'])->first();

        if (! $barber) {
            return response()->json([]);
        }

        $date = Carbon::parse($validated['date'])->toDateString();
        $dayKey = strtolower(Carbon::parse($date)->englishDayOfWeek);
        $workingDays = collect($barber->working_days ?? []);

        if (! $workingDays->contains($dayKey)) {
            return response()->json([]);
        }

        $startOfDay = Carbon::parse($date.' '.$barber->start_working_time);
        $endOfDay = Carbon::parse($date.' '.$barber->end_working_time);
        $slots = [];
        $cursor = $startOfDay->copy();

        while ($cursor->copy()->addHour()->lte($endOfDay)) {
            if ($cursor->gt(Carbon::now())) {
                $slots[] = $cursor->format('H:i');
            }
            $cursor->addHour();
        }

        if (empty($slots)) {
            return response()->json([]);
        }

        $busySlots = Event::query()
            ->where('barber_id', $validated['barber'])
            ->whereDate('start', $date)
            ->whereIn('status', Event::blockingStatuses())
            ->pluck('start')
            ->map(fn($start) => Carbon::parse($start)->format('H:i'))
            ->toArray();

        $available = array_values(array_diff($slots, $busySlots));

        return response()->json($available);
    }
}

