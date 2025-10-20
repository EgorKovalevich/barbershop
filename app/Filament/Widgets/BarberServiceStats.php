<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasPeriodFilters;
use App\Models\Barber;
use App\Models\Category;
use App\Models\Event;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Str;

class BarberServiceStats extends StatsOverviewWidget
{
    use HasPeriodFilters;

    protected static ?string $pollingInterval = '60s';
    protected static ?string $heading = '3. Барберы и услуги';

    protected function getCards(): array
    {
        $filter = $this->filter ?? $this->getDefaultFilter();

        $period = $this->resolvePeriods($filter);
        $start = $period[0];
        $end = $period[1];

        $metrics = $this->calculateMetrics($start, $end);

        return [
            Card::make('Клиентов на мастера', $metrics['clients_per_barber'])
                ->description($metrics['clients_per_barber_description'])
                ->extraAttributes(['class' => 'min-h-[164px]']),
            Card::make('Средний чек по мастеру', $metrics['average_check'])
                ->description($metrics['average_check_description'])
                ->extraAttributes(['class' => 'min-h-[164px]']),
            Card::make('Загрузка барбера', $metrics['average_occupancy'])
                ->description($metrics['average_occupancy_description'])
                ->extraAttributes(['class' => 'min-h-[164px]']),
            Card::make('Процент рабочего времени, занятого записями', $metrics['workload_percentage'])
                ->description($metrics['workload_description'])
                ->descriptionColor($metrics['workload_color'])
                ->extraAttributes(['class' => 'min-h-[164px]']),
            Card::make('ТОП-3 популярных услуг', $metrics['top_services_text'])
                ->description($metrics['top_services_description'])
                ->extraAttributes(['class' => 'min-h-[164px]']),
        ];
    }

    private function calculateMetrics(Carbon $start, Carbon $end): array
    {
        $barbers = Barber::query()->with('user')->get();

        $events = Event::query()
            ->whereBetween('start', [$start, $end])
            ->whereNotNull('barber_id')
            ->get(['barber_id', 'organizer_id', 'status', 'start', 'end', 'category']);

        $blockingStatuses = [
            Event::STATUS_SCHEDULED,
            Event::STATUS_COMPLETED,
            Event::STATUS_NO_SHOW,
        ];

        $activeEvents = $events->filter(fn (Event $event) => in_array($event->status, $blockingStatuses, true));
        $completedEvents = $events->filter(fn (Event $event) => $event->status === Event::STATUS_COMPLETED);

        $clientsPerBarberValues = [];
        $totalClients = 0;
        $activeBarberCount = 0;
        $occupancyValues = [];
        $totalAvailableMinutes = 0;
        $totalOccupiedMinutes = 0;
        $topBarber = null;

        $categoryIds = collect();
        $serviceCounts = [];

        foreach ($activeEvents as $event) {
            if ($event->category) {
                $serviceCounts[$event->category] = ($serviceCounts[$event->category] ?? 0) + 1;

                if (Str::isUuid($event->category)) {
                    $categoryIds->push($event->category);
                }
            }
        }

        foreach ($completedEvents as $event) {
            if ($event->category && Str::isUuid($event->category)) {
                $categoryIds->push($event->category);
            }
        }

        $categoryIds = $categoryIds->unique()->values();

        $categoryAmounts = Category::query()
            ->whereIn('id', $categoryIds)
            ->get()
            ->mapWithKeys(fn (Category $category) => [
                $category->id => [
                    'amount' => $category->amount,
                    'name' => $category->name,
                ],
            ]);

        $averageCheckValues = [];
        $totalRevenue = 0;
        $totalCompletedVisits = 0;

        foreach ($barbers as $barber) {
            $barberUserId = $barber->user_id;

            $barberActiveEvents = $activeEvents->where('barber_id', $barberUserId);
            $barberCompletedEvents = $completedEvents->where('barber_id', $barberUserId);

            if ($barberActiveEvents->isNotEmpty()) {
                $activeBarberCount++;

                $uniqueClients = $barberActiveEvents
                    ->whereNotNull('organizer_id')
                    ->unique('organizer_id')
                    ->count();

                $totalClients += $uniqueClients;

                if ($uniqueClients > 0) {
                    $clientsPerBarberValues[] = $uniqueClients;
                }

                $occupiedMinutes = $barberActiveEvents->sum(function (Event $event) use ($start, $end) {
                    $eventStart = $event->start->copy();
                    if ($eventStart->lt($start)) {
                        $eventStart = $start->copy();
                    }

                    $eventEnd = $event->end?->copy() ?? $event->start->copy()->addHour();
                    if ($eventEnd->gt($end)) {
                        $eventEnd = $end->copy();
                    }

                    if ($eventEnd->lte($eventStart)) {
                        return 0;
                    }

                    return $eventStart->diffInMinutes($eventEnd);
                });

                $availableMinutes = $this->calculateAvailableMinutes($barber, $start, $end);

                $totalAvailableMinutes += $availableMinutes;
                $totalOccupiedMinutes += $occupiedMinutes;

                if ($availableMinutes > 0) {
                    $occupancy = round(($occupiedMinutes / $availableMinutes) * 100, 1);
                    $occupancyValues[] = $occupancy;

                    if ($topBarber === null || $occupancy > $topBarber['occupancy']) {
                        $topBarber = [
                            'name' => $this->formatBarberName($barber),
                            'occupancy' => $occupancy,
                            'bookings' => $barberActiveEvents->count(),
                        ];
                    }
                }
            }

            if ($barberCompletedEvents->isNotEmpty()) {
                $barberRevenue = 0;
                $barberVisits = 0;

                foreach ($barberCompletedEvents as $event) {
                    $categoryKey = $event->category;

                    if (! $categoryKey) {
                        continue;
                    }

                    $categoryData = $categoryAmounts[$categoryKey] ?? null;

                    if ($categoryData === null) {
                        continue;
                    }

                    $barberRevenue += $categoryData['amount'];
                    $barberVisits++;
                }

                if ($barberVisits > 0 && $barberRevenue > 0) {
                    $averageCheckValues[] = $barberRevenue / $barberVisits;
                    $totalRevenue += $barberRevenue;
                    $totalCompletedVisits += $barberVisits;
                }
            }
        }

        $clientsPerBarber = ! empty($clientsPerBarberValues)
            ? round(array_sum($clientsPerBarberValues) / count($clientsPerBarberValues), 1)
            : 0.0;

        $averageOccupancy = ! empty($occupancyValues)
            ? round(array_sum($occupancyValues) / count($occupancyValues), 1)
            : 0.0;

        $workloadPercentage = $totalAvailableMinutes > 0
            ? round(($totalOccupiedMinutes / $totalAvailableMinutes) * 100, 1)
            : 0.0;

        $averageCheck = ! empty($averageCheckValues)
            ? round(array_sum($averageCheckValues) / count($averageCheckValues), 2)
            : 0.0;

        $topServicesList = [];

        if ($serviceCounts !== []) {
            arsort($serviceCounts);
            $topServiceSlice = array_slice($serviceCounts, 0, 3, true);

            foreach ($topServiceSlice as $categoryKey => $count) {
                $name = $categoryAmounts[$categoryKey]['name'] ?? $categoryKey;

                $topServicesList[] = [
                    'name' => $name,
                    'count' => $count,
                ];
            }
        }

        $topServicesText = $topServicesList === []
            ? 'Нет данных'
            : collect($topServicesList)
                ->values()
                ->map(fn (array $service, int $index) => ($index + 1) . '. ' . $service['name'] . ' — ' . $service['count'])
                ->implode(' · ');

        $servicesTotal = array_sum($serviceCounts);

        $leaderDescription = $topBarber !== null
            ? sprintf('Лидер: %s — %.1f%% · записей %d', $topBarber['name'], $topBarber['occupancy'], $topBarber['bookings'])
            : 'Нет активных записей';

        return [
            'clients_per_barber' => number_format($clientsPerBarber, 1, ',', ' '),
            'clients_per_barber_description' => $activeBarberCount > 0
                ? sprintf('Всего %d клиентов · %d барбера(-ов)', $totalClients, $activeBarberCount)
                : 'Нет данных за период',
            'average_check' => 'Br ' . number_format($averageCheck, 2, ',', ' '),
            'average_check_description' => $totalRevenue > 0
                ? sprintf('Выручка Br %s · визитов %d', number_format($totalRevenue, 0, ',', ' '), $totalCompletedVisits)
                : 'Нет завершённых визитов',
            'average_occupancy' => sprintf('%.1f%%', $averageOccupancy),
            'average_occupancy_description' => $activeBarberCount > 0
                ? $leaderDescription
                : 'Нет данных за период',
            'workload_percentage' => sprintf('%.1f%%', $workloadPercentage),
            'workload_description' => $totalAvailableMinutes > 0
                ? sprintf('Занято %s из %s ч.',
                    number_format($totalOccupiedMinutes / 60, 1, ',', ' '),
                    number_format($totalAvailableMinutes / 60, 1, ',', ' ')
                )
                : 'Нет графиков для расчёта',
            'workload_color' => $workloadPercentage > 85 ? 'danger' : 'success',
            'top_services_text' => $topServicesText,
            'top_services_description' => $topServicesList === []
                ? 'Нет данных за период'
                : sprintf('На основе %d визитов', $servicesTotal),
        ];
    }

    private function calculateAvailableMinutes(Barber $barber, Carbon $start, Carbon $end): int
    {
        if (! $barber->start_working_time || ! $barber->end_working_time) {
            return 0;
        }

        $workingDays = collect($barber->working_days ?? []);

        if ($workingDays->isEmpty()) {
            return 0;
        }

        $period = CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay());

        $total = 0;

        foreach ($period as $date) {
            $dayKey = strtolower($date->englishDayOfWeek);

            if (! $workingDays->contains($dayKey)) {
                continue;
            }

            $shiftStart = Carbon::parse($date->toDateString() . ' ' . $barber->start_working_time);
            $shiftEnd = Carbon::parse($date->toDateString() . ' ' . $barber->end_working_time);

            if ($shiftEnd->lte($shiftStart)) {
                continue;
            }

            $dayStart = $shiftStart->copy();
            if ($dayStart->lt($start)) {
                $dayStart = $start->copy();
            }

            $dayEnd = $shiftEnd->copy();
            if ($dayEnd->gt($end)) {
                $dayEnd = $end->copy();
            }

            if ($dayEnd->lte($dayStart)) {
                continue;
            }

            $total += $dayStart->diffInMinutes($dayEnd);
        }

        return $total;
    }

    private function formatBarberName(Barber $barber): string
    {
        $user = $barber->user;

        if (! $user) {
            return 'Без имени';
        }

        $fullName = trim(collect([
            $user->surname,
            $user->name,
            $user->patronymic,
        ])->filter()->implode(' '));

        return $fullName !== '' ? $fullName : ($user->email ?? 'ID ' . $user->id);
    }
}
