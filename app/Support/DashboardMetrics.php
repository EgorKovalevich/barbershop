<?php

namespace App\Support;

use App\Models\Barber;
use App\Models\Category;
use App\Models\Event;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Str;

class DashboardMetrics
{
    /**
     * Collect booking metrics for the given period.
     */
    public function bookings(Carbon $start, Carbon $end): array
    {
        $baseQuery = Event::query()->whereBetween('start', [$start, $end]);

        $totalBookings = (clone $baseQuery)->count();
        $completed = (clone $baseQuery)->where('status', Event::STATUS_COMPLETED)->count();
        $attendedClients = (clone $baseQuery)
            ->where('status', Event::STATUS_COMPLETED)
            ->whereNotNull('organizer_id')
            ->distinct()
            ->count('organizer_id');

        $cancelled = (clone $baseQuery)->where('status', Event::STATUS_CANCELLED)->count();
        $noShow = (clone $baseQuery)->where('status', Event::STATUS_NO_SHOW)->count();

        $attendanceRate = $totalBookings > 0 ? round(($attendedClients / $totalBookings) * 100, 1) : 0.0;
        $cancellationsTotal = $cancelled + $noShow;
        $cancellationRate = $totalBookings > 0 ? round(($cancellationsTotal / $totalBookings) * 100, 1) : 0.0;

        return [
            'total_bookings' => $totalBookings,
            'completed' => $completed,
            'attended_clients' => $attendedClients,
            'attendance_rate' => $attendanceRate,
            'cancelled' => $cancelled,
            'no_show' => $noShow,
            'cancellations_total' => $cancellationsTotal,
            'cancellation_rate' => $cancellationRate,
        ];
    }

    /**
     * Collect client metrics for the given period.
     */
    public function clients(Carbon $start, Carbon $end): array
    {
        $eventsQuery = Event::query()
            ->whereBetween('start', [$start, $end])
            ->whereNotNull('organizer_id');

        $totalVisits = (clone $eventsQuery)->count();
        $uniqueClients = (clone $eventsQuery)->distinct('organizer_id')->count('organizer_id');

        $eventsTable = (new Event())->getTable();
        $aliasedTable = $eventsTable . ' as current';

        $newClients = Event::query()
            ->from($aliasedTable)
            ->whereBetween('current.start', [$start, $end])
            ->whereNotNull('current.organizer_id')
            ->whereNotExists(function ($query) use ($eventsTable, $start) {
                $query->selectRaw(1)
                    ->from($eventsTable . ' as previous')
                    ->whereColumn('previous.organizer_id', 'current.organizer_id')
                    ->where('previous.start', '<', $start);
            })
            ->distinct('current.organizer_id')
            ->count('current.organizer_id');

        $returningClients = Event::query()
            ->from($aliasedTable)
            ->whereBetween('current.start', [$start, $end])
            ->whereNotNull('current.organizer_id')
            ->whereExists(function ($query) use ($eventsTable, $start) {
                $query->selectRaw(1)
                    ->from($eventsTable . ' as previous')
                    ->whereColumn('previous.organizer_id', 'current.organizer_id')
                    ->where('previous.start', '<', $start);
            })
            ->distinct('current.organizer_id')
            ->count('current.organizer_id');

        $returningRate = $uniqueClients > 0 ? round(($returningClients / $uniqueClients) * 100, 1) : 0.0;
        $averageVisitsPerClient = $uniqueClients > 0 ? round($totalVisits / $uniqueClients, 1) : 0.0;

        [$averageInterval, $intervalCount] = $this->calculateAverageVisitInterval(clone $eventsQuery);

        return [
            'new_clients' => $newClients,
            'returning_clients' => $returningClients,
            'returning_rate' => $returningRate,
            'total_visits' => $totalVisits,
            'unique_clients' => $uniqueClients,
            'average_visits_per_client' => $averageVisitsPerClient,
            'average_visit_interval' => $averageInterval,
            'interval_count' => $intervalCount,
        ];
    }

    /**
     * Collect barber and service metrics for the given period.
     */
    public function barberServices(Carbon $start, Carbon $end): array
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

        $servicesTotal = array_sum($serviceCounts);

        return [
            'clients_per_barber' => [
                'average' => $clientsPerBarber,
                'total_clients' => $totalClients,
                'active_barbers' => $activeBarberCount,
            ],
            'average_check' => [
                'amount' => $averageCheck,
                'total_revenue' => $totalRevenue,
                'completed_visits' => $totalCompletedVisits,
            ],
            'average_occupancy' => [
                'percentage' => $averageOccupancy,
                'leader' => $topBarber,
            ],
            'workload' => [
                'percentage' => $workloadPercentage,
                'occupied_minutes' => $totalOccupiedMinutes,
                'available_minutes' => $totalAvailableMinutes,
            ],
            'top_services' => [
                'items' => $topServicesList,
                'total' => $servicesTotal,
            ],
        ];
    }

    /**
     * Collect financial metrics for the given period.
     */
    public function financials(Carbon $start, Carbon $end): array
    {
        $events = Event::query()
            ->whereBetween('start', [$start, $end])
            ->where('status', Event::STATUS_COMPLETED)
            ->get(['start', 'barber_id', 'category']);

        $categoryIds = $events
            ->pluck('category')
            ->filter(fn ($value) => $value && Str::isUuid($value))
            ->unique()
            ->values();

        $categoryAmounts = Category::query()
            ->whereIn('id', $categoryIds)
            ->get()
            ->mapWithKeys(fn (Category $category) => [
                $category->id => (float) $category->amount,
            ]);

        $totalRevenue = 0.0;
        $totalVisits = 0;
        $dailyBuckets = [];
        $weeklyBuckets = [];
        $monthlyBuckets = [];
        $barberRevenue = [];

        foreach ($events as $event) {
            $amount = $categoryAmounts[$event->category] ?? 0.0;

            $totalRevenue += $amount;
            $totalVisits++;

            $date = $event->start->copy();

            $dayKey = $date->toDateString();
            $dailyBuckets[$dayKey] = ($dailyBuckets[$dayKey] ?? 0) + $amount;

            $weekKey = $date->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
            $weeklyBuckets[$weekKey] = ($weeklyBuckets[$weekKey] ?? 0) + $amount;

            $monthKey = $date->copy()->startOfMonth()->toDateString();
            $monthlyBuckets[$monthKey] = ($monthlyBuckets[$monthKey] ?? 0) + $amount;

            if ($event->barber_id) {
                $barberRevenue[$event->barber_id] = ($barberRevenue[$event->barber_id] ?? 0) + $amount;
            }
        }

        $averageCheck = $totalVisits > 0 ? round($totalRevenue / $totalVisits, 2) : 0.0;

        $barberSummary = $this->summarizeBarberRevenue($barberRevenue, $totalRevenue);

        return [
            'total_revenue' => round($totalRevenue, 2),
            'completed_visits' => $totalVisits,
            'average_check' => $averageCheck,
            'breakdowns' => [
                'daily' => $this->summarizeRevenueBuckets($dailyBuckets, 'daily'),
                'weekly' => $this->summarizeRevenueBuckets($weeklyBuckets, 'weekly'),
                'monthly' => $this->summarizeRevenueBuckets($monthlyBuckets, 'monthly'),
            ],
            'barbers' => $barberSummary,
        ];
    }

    /**
     * Estimate website activity metrics for the given period.
     */
    public function siteActivity(Carbon $start, Carbon $end): array
    {
        $events = Event::query()
            ->whereBetween('start', [$start, $end])
            ->whereIn('status', Event::blockingStatuses())
            ->get(['start', 'status', 'organizer_id']);

        $totalBookings = $events->count();
        $completedBookings = $events->where('status', Event::STATUS_COMPLETED)->count();
        $uniqueBookers = $events
            ->whereNotNull('organizer_id')
            ->unique('organizer_id')
            ->count();

        $periodDays = max($start->diffInDays($end) + 1, 1);

        $targetConversion = (float) config('dashboard.site_activity.target_conversion', 12.0);
        $averageDailyVisitors = (int) config('dashboard.site_activity.average_daily_visitors', 160);
        $uniqueShare = (float) config('dashboard.site_activity.unique_share', 0.72);

        if ($totalBookings > 0 && $targetConversion > 0) {
            $estimatedVisitors = (int) max(
                $totalBookings,
                round($totalBookings / ($targetConversion / 100))
            );
        } else {
            $estimatedVisitors = (int) round($periodDays * max($averageDailyVisitors, 0));
        }

        $uniqueVisitors = $estimatedVisitors > 0
            ? (int) max($uniqueBookers, round($estimatedVisitors * $uniqueShare))
            : $uniqueBookers;

        $conversionRate = $estimatedVisitors > 0
            ? round(($totalBookings / $estimatedVisitors) * 100, 1)
            : 0.0;

        $perHundred = $estimatedVisitors > 0
            ? (int) round(($totalBookings / $estimatedVisitors) * 100)
            : 0;

        $visitorsPerBooking = $totalBookings > 0
            ? round($estimatedVisitors / $totalBookings, 1)
            : null;

        return [
            'total_visitors' => $estimatedVisitors,
            'unique_visitors' => $uniqueVisitors,
            'conversion_rate' => $conversionRate,
            'per_hundred' => $perHundred,
            'bookings_total' => $totalBookings,
            'bookings_completed' => $completedBookings,
            'visitors_per_booking' => $visitorsPerBooking,
            'popular_days' => $this->resolvePopularDays($events),
            'popular_hours' => $this->resolvePopularHours($events),
        ];
    }

    private function calculateAverageVisitInterval($eventsQuery): array
    {
        $intervals = [];
        $previousVisits = [];

        $events = $eventsQuery
            ->orderBy('organizer_id')
            ->orderBy('start')
            ->get(['organizer_id', 'start']);

        foreach ($events as $event) {
            $organizerId = $event->organizer_id;

            if (isset($previousVisits[$organizerId])) {
                $diffMinutes = $event->start->diffInMinutes($previousVisits[$organizerId]);
                $intervals[] = $diffMinutes / 1440;
            }

            $previousVisits[$organizerId] = $event->start;
        }

        if ($intervals === []) {
            return [null, 0];
        }

        $averageInterval = round(array_sum($intervals) / count($intervals), 1);

        return [$averageInterval, count($intervals)];
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

    private function summarizeRevenueBuckets(array $buckets, string $granularity): array
    {
        if ($buckets === []) {
            return [
                'total' => 0.0,
                'average' => 0.0,
                'count' => 0,
                'top' => null,
            ];
        }

        $total = array_sum($buckets);
        $count = count($buckets);

        arsort($buckets);
        $topKey = array_key_first($buckets);
        $topAmount = $buckets[$topKey];

        return [
            'total' => round($total, 2),
            'average' => round($total / $count, 2),
            'count' => $count,
            'top' => $total > 0 ? [
                'key' => $topKey,
                'amount' => round($topAmount, 2),
                'label' => $this->formatRevenuePeriodLabel($topKey, $granularity),
            ] : null,
        ];
    }

    private function summarizeBarberRevenue(array $barberRevenue, float $totalRevenue): array
    {
        if ($barberRevenue === []) {
            return [
                'leader' => null,
                'laggard' => null,
                'gap' => 0.0,
                'count' => 0,
                'average' => 0.0,
            ];
        }

        arsort($barberRevenue);

        $leaderId = array_key_first($barberRevenue);
        $leaderAmount = $barberRevenue[$leaderId];

        $laggardId = array_key_last($barberRevenue);
        $laggardAmount = $barberRevenue[$laggardId];

        $barberIds = array_keys($barberRevenue);

        $barbers = Barber::query()
            ->with('user')
            ->whereIn('user_id', $barberIds)
            ->get()
            ->keyBy('user_id');

        $leader = $barbers->get($leaderId);
        $laggard = $barbers->get($laggardId);

        $barberCount = count($barberRevenue);

        return [
            'leader' => $leader ? [
                'name' => $this->formatBarberName($leader),
                'amount' => round($leaderAmount, 2),
            ] : null,
            'laggard' => $laggard ? [
                'name' => $this->formatBarberName($laggard),
                'amount' => round($laggardAmount, 2),
            ] : null,
            'gap' => $barberCount > 1 ? round($leaderAmount - $laggardAmount, 2) : 0.0,
            'count' => $barberCount,
            'average' => $barberCount > 0 ? round($totalRevenue / $barberCount, 2) : 0.0,
        ];
    }

    private function formatRevenuePeriodLabel(string $key, string $granularity): string
    {
        $date = Carbon::parse($key);

        return match ($granularity) {
            'daily' => $date->format('d.m'),
            'weekly' => sprintf('%s–%s',
                $date->copy()->startOfWeek(Carbon::MONDAY)->format('d.m'),
                $date->copy()->endOfWeek(Carbon::SUNDAY)->format('d.m')
            ),
            'monthly' => $date->copy()->startOfMonth()->format('m.Y'),
            default => $date->toDateString(),
        };
    }

    private function resolvePopularDays($events): array
    {
        if ($events->isEmpty()) {
            return [];
        }

        $dayNames = [
            1 => ['short' => 'Пн', 'full' => 'Понедельник'],
            2 => ['short' => 'Вт', 'full' => 'Вторник'],
            3 => ['short' => 'Ср', 'full' => 'Среда'],
            4 => ['short' => 'Чт', 'full' => 'Четверг'],
            5 => ['short' => 'Пт', 'full' => 'Пятница'],
            6 => ['short' => 'Сб', 'full' => 'Суббота'],
            7 => ['short' => 'Вс', 'full' => 'Воскресенье'],
        ];

        return $events
            ->filter(fn (Event $event) => $event->start !== null)
            ->groupBy(fn (Event $event) => $event->start->dayOfWeekIso)
            ->map(function ($group, $day) use ($dayNames) {
                $info = $dayNames[$day] ?? ['short' => (string) $day, 'full' => ''];

                return [
                    'label' => $info['short'],
                    'full' => $info['full'],
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('count')
            ->take(3)
            ->values()
            ->map(function (array $item) {
                return [
                    'label' => $item['label'],
                    'count' => $item['count'],
                    'description' => $item['full'],
                ];
            })
            ->all();
    }

    private function resolvePopularHours($events): array
    {
        if ($events->isEmpty()) {
            return [];
        }

        return $events
            ->filter(fn (Event $event) => $event->start !== null)
            ->groupBy(fn (Event $event) => $event->start->format('H'))
            ->map(function ($group, $hour) {
                $hourInt = (int) $hour;
                $startLabel = str_pad((string) $hourInt, 2, '0', STR_PAD_LEFT) . ':00';
                $endLabel = str_pad((string) (($hourInt + 1) % 24), 2, '0', STR_PAD_LEFT) . ':00';

                return [
                    'label' => $startLabel . '–' . $endLabel,
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('count')
            ->take(3)
            ->values()
            ->all();
    }
}
