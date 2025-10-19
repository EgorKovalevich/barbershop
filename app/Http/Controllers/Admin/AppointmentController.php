<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the appointments.
     */
    public function index(): View
    {
        $events = $this->paginatedEvents();
        $statuses = Event::statusOptions();
        $categoryNames = Category::query()->pluck('name', 'id')->all();

        return view('admin.appointments.index', [
            'events' => $events,
            'statuses' => $statuses,
            'categoryNames' => $categoryNames,
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

        return view('admin.appointments.edit', [
            'event' => $event,
            'categories' => $categories,
            'barbers' => $barbers,
            'statuses' => Event::statusOptions(),
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
            'status' => ['required', Rule::in(array_keys(Event::statusOptions()))],
        ]);

        $start = Carbon::parse($validated['start_date'].' '.$validated['start_time'])->seconds(0);
        $end = $start->copy()->addHour();

        $event->update([
            'subject' => $validated['subject'],
            'number' => $validated['number'],
            'category' => (string) $validated['category'],
            'barber_id' => $validated['barber_id'],
            'start' => $start,
            'end' => $end,
            'body' => $validated['body'] ?? null,
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('admin.appointments.index')
            ->with('appointmentUpdated', 'Запись успешно обновлена.');
    }

    private function paginatedEvents(): LengthAwarePaginator
    {
        return Event::query()
            ->with(['barber', 'organizerUser'])
            ->orderByDesc('start')
            ->paginate(12);
    }

    private function authorizeEvent(Event $event): void
    {
        if (! auth()->check() || auth()->user()->role !== 'admin') {
            abort(403);
        }
    }
}
