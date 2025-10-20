<?php

namespace App\Services\AdminDashboard;

use App\Models\Barber;
use App\Models\Category;
use App\Models\Event;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminDashboardStatsService
{
    private CarbonImmutable $now;

    public function __construct(?CarbonImmutable $now = null)
    {
        $this->now = $now ?? CarbonImmutable::now();
    }

    public function getStats(): array
    {
        $categories = $this->loadCategories();

        return [
            'bookings' => $this->buildBookingStats($categories),
            'clients' => $this->buildClientStats($categories),
            'barbers' => $this->buildBarberStats($categories),
            'finance' => $this->buildFinanceStats($categories),
            'marketing' => $this->buildMarketingStats(),
            'technical' => $this->buildTechnicalStats(),
        ];
    }

    private function buildBookingStats(Collection $categories): array
    {
        $periods = [
            'day' => [$this->now->startOfDay(), $this->now->endOfDay(), $this->now->subDay()->startOfDay(), $this->now->subDay()->endOfDay()],
            'week' => [$this->now->startOfWeek(), $this->now->endOfWeek(), $this->now->subWeek()->startOfWeek(), $this->now->subWeek()->endOfWeek()],
            'month' => [$this->now->startOfMonth(), $this->now->endOfMonth(), $this->now->subMonth()->startOfMonth(), $this->now->subMonth()->endOfMonth()],
        ];

        $periodStats = [];

        foreach ($periods as $key => [$currentStart, $currentEnd, $previousStart, $previousEnd]) {
            $currentCount = $this->countEventsBetween($currentStart, $currentEnd);
            $previousCount = $this->countEventsBetween($previousStart, $previousEnd);

            $periodStats[$key] = [
                'current' => $currentCount,
                'previous' => $previousCount,
                'trend' => $this->calculateTrend($currentCount, $previousCount),
                'start' => $currentStart,
                'end' => $currentEnd,
            ];
        }

        $monthEventsQuery = $this->baseEventQuery()
            ->whereBetween('start', [$periods['month'][0], $periods['month'][1]]);

        $totalMonthEvents = (clone $monthEventsQuery)->count();
        $completedVisits = (clone $monthEventsQuery)->where('status', Event::STATUS_COMPLETED)->count();
        $attendedClients = (clone $monthEventsQuery)->where('status', Event::STATUS_COMPLETED)
            ->whereNotNull('organizer_id')
            ->distinct('organizer_id')
            ->count('organizer_id');
        $cancelled = (clone $monthEventsQuery)->where('status', Event::STATUS_CANCELLED)->count();
        $noShows = (clone $monthEventsQuery)->where('status', Event::STATUS_NO_SHOW)->count();

        $retentionData = $this->calculateRetention($periods['month'][0], $periods['month'][1]);

        return [
            'periods' => $periodStats,
            'completed' => [
                'visits' => $completedVisits,
                'clients' => $attendedClients,
            ],
            'cancellations' => [
                'cancelled' => $cancelled,
                'no_show' => $noShows,
                'rate' => $totalMonthEvents > 0
                    ? round((($cancelled + $noShows) / $totalMonthEvents) * 100, 1)
                    : null,
            ],
            'retention' => $retentionData,
        ];
    }

    private function buildClientStats(Collection $categories): array
    {
        $monthStart = $this->now->startOfMonth();
        $monthEnd = $this->now->endOfMonth();

        $newClients = User::query()
            ->where('role', 'client')
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->count();

        $loyalClients = $this->countLoyalClients();

        $recentEvents = $this->baseEventQuery()
            ->where('status', Event::STATUS_COMPLETED)
            ->where('start', '>=', $this->now->subMonths(3))
            ->whereNotNull('organizer_id')
            ->get(['organizer_id']);

        $clientsWithVisits = $recentEvents->groupBy('organizer_id');
        $totalRecentEvents = $recentEvents->count();
        $recentClientCount = $clientsWithVisits->count();

        $averageVisits = $recentClientCount > 0
            ? round($totalRecentEvents / $recentClientCount, 2)
            : null;

        $averageInterval = $this->calculateAverageVisitInterval();

        return [
            'new_clients' => $newClients,
            'loyal_clients' => $loyalClients,
            'average_visits' => $averageVisits,
            'average_interval_days' => $averageInterval,
            'sources' => $this->buildClientSourceBreakdown(),
        ];
    }

    private function buildBarberStats(Collection $categories): array
    {
        $barbers = User::query()
            ->where('role', 'barber')
            ->with('barber')
            ->get();

        $dayRange = [$this->now->startOfDay(), $this->now->endOfDay()];
        $weekRange = [$this->now->startOfWeek(), $this->now->endOfWeek()];
        $monthRange = [$this->now->startOfMonth(), $this->now->endOfMonth()];

        $eventsByPeriod = [
            'day' => $this->eventsGroupedByBarber($dayRange[0], $dayRange[1]),
            'week' => $this->eventsGroupedByBarber($weekRange[0], $weekRange[1]),
            'month' => $this->eventsGroupedByBarber($monthRange[0], $monthRange[1]),
        ];

        $barberStats = [];

        foreach ($barbers as $barber) {
            $barberId = $barber->id;
            $profile = $barber->barber;

            $monthlyEvents = $eventsByPeriod['month']->get($barberId, collect());
            $averageCheck = $this->calculateAverageCheckForEvents($monthlyEvents, $categories);

            $utilization = [
                'day' => $this->calculateUtilization($profile, $eventsByPeriod['day']->get($barberId, collect()), $dayRange[0], $dayRange[1]),
                'week' => $this->calculateUtilization($profile, $eventsByPeriod['week']->get($barberId, collect()), $weekRange[0], $weekRange[1]),
                'month' => $this->calculateUtilization($profile, $monthlyEvents, $monthRange[0], $monthRange[1]),
            ];

            $barberStats[] = [
                'id' => $barberId,
                'name' => trim($barber->surname.' '.$barber->name),
                'counts' => [
                    'day' => $eventsByPeriod['day']->get($barberId, collect())->count(),
                    'week' => $eventsByPeriod['week']->get($barberId, collect())->count(),
                    'month' => $monthlyEvents->count(),
                ],
                'average_check' => $averageCheck,
                'utilization' => $utilization,
            ];
        }

        $topServices = $this->buildTopServices($categories);
        $averageDuration = $this->calculateAverageVisitDuration();

        return [
            'barbers' => $barberStats,
            'top_services' => $topServices,
            'average_duration_minutes' => $averageDuration,
        ];
    }

    private function buildFinanceStats(Collection $categories): array
    {
        $dayStart = $this->now->startOfDay();
        $dayEnd = $this->now->endOfDay();
        $weekStart = $this->now->startOfWeek();
        $weekEnd = $this->now->endOfWeek();
        $monthStart = $this->now->startOfMonth();
        $monthEnd = $this->now->endOfMonth();

        $dayRevenue = $this->sumPaymentsBetween($dayStart, $dayEnd);
        $weekRevenue = $this->sumPaymentsBetween($weekStart, $weekEnd);
        $monthRevenue = $this->sumPaymentsBetween($monthStart, $monthEnd);

        $paidPayments = $this->basePaymentQuery()->count();
        $totalRevenue = $this->sumPaymentsBetween(null, null);

        $averageCheck = $paidPayments > 0
            ? round($totalRevenue / $paidPayments, 2)
            : null;

        $revenueByBarber = $this->calculateRevenueByBarber($categories, $monthStart, $monthEnd);

        return [
            'revenue' => [
                'day' => $dayRevenue,
                'week' => $weekRevenue,
                'month' => $monthRevenue,
            ],
            'average_check' => $averageCheck,
            'revenue_by_barber' => $revenueByBarber,
        ];
    }

    private function buildMarketingStats(): array
    {
        $monthStart = $this->now->startOfMonth();
        $monthEnd = $this->now->endOfMonth();

        $onlineBookings = $this->baseEventQuery()
            ->whereBetween('start', [$monthStart, $monthEnd])
            ->whereHas('organizerUser', fn ($query) => $query->where('role', 'client'))
            ->count();

        $popularSlots = $this->baseEventQuery()
            ->whereBetween('start', [$monthStart, $monthEnd])
            ->get(['start'])
            ->groupBy(function (Event $event) {
                $start = Carbon::parse($event->start);

                return $start->translatedFormat('l, H:00');
            })
            ->map(fn (Collection $events) => $events->count())
            ->sortDesc()
            ->take(5)
            ->map(function ($count, $slot) {
                return [
                    'slot' => Str::ucfirst($slot),
                    'count' => $count,
                ];
            })
            ->values()
            ->all();

        return [
            'visitors' => null,
            'unique_visitors' => null,
            'traffic_sources' => [],
            'conversion_rate' => null,
            'online_bookings' => $onlineBookings,
            'popular_slots' => $popularSlots,
            'requires_integration' => true,
        ];
    }

    private function buildTechnicalStats(): array
    {
        return [
            'sessions' => null,
            'unique_users' => null,
            'avg_session_duration' => null,
            'top_pages' => [],
            'devices' => [],
            'locations' => [],
        ];
    }

    private function loadCategories(): Collection
    {
        return Category::query()->get(['id', 'name', 'amount', 'color'])->keyBy('id');
    }

    private function baseEventQuery(): EloquentBuilder
    {
        return Event::query();
    }

    private function basePaymentQuery(): EloquentBuilder
    {
        return Payment::query()->where('paid', true);
    }

    private function countEventsBetween(CarbonInterface $start, CarbonInterface $end): int
    {
        return $this->baseEventQuery()
            ->whereBetween('start', [$start, $end])
            ->count();
    }

    private function calculateTrend(int $current, int $previous): ?array
    {
        if ($previous === 0) {
            if ($current === 0) {
                return null;
            }

            return [
                'direction' => 'up',
                'percentage' => null,
            ];
        }

        $change = $current - $previous;
        $percent = round(($change / $previous) * 100, 1);

        return [
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat'),
            'percentage' => $percent,
        ];
    }

    private function calculateRetention(CarbonInterface $start, CarbonInterface $end): array
    {
        $events = $this->baseEventQuery()
            ->whereBetween('start', [$start, $end])
            ->whereNotNull('organizer_id')
            ->get(['organizer_id']);

        $clients = $events->groupBy('organizer_id');
        $returning = $clients->filter(fn (Collection $collection) => $collection->count() > 1);

        $totalClients = $clients->count();
        $returningCount = $returning->count();
        $retentionRate = $totalClients > 0
            ? round(($returningCount / $totalClients) * 100, 1)
            : null;

        return [
            'clients' => $returningCount,
            'rate' => $retentionRate,
        ];
    }

    private function countLoyalClients(): int
    {
        return $this->baseEventQuery()
            ->select('organizer_id')
            ->whereNotNull('organizer_id')
            ->where('status', Event::STATUS_COMPLETED)
            ->groupBy('organizer_id')
            ->havingRaw('COUNT(*) >= 3')
            ->count();
    }

    private function calculateAverageVisitInterval(): ?float
    {
        $events = $this->baseEventQuery()
            ->whereNotNull('organizer_id')
            ->where('status', Event::STATUS_COMPLETED)
            ->where('start', '>=', $this->now->subYear())
            ->orderBy('organizer_id')
            ->orderBy('start')
            ->get(['organizer_id', 'start']);

        if ($events->isEmpty()) {
            return null;
        }

        $grouped = $events->groupBy('organizer_id');

        $intervals = [];

        foreach ($grouped as $clientEvents) {
            if ($clientEvents->count() < 2) {
                continue;
            }

            $clientEvents = $clientEvents->values();

            for ($i = 1; $i < $clientEvents->count(); $i++) {
                $previous = Carbon::parse($clientEvents[$i - 1]->start);
                $current = Carbon::parse($clientEvents[$i]->start);
                $intervals[] = $previous->diffInDays($current);
            }
        }

        if ($intervals === []) {
            return null;
        }

        return round(array_sum($intervals) / count($intervals), 1);
    }

    private function buildClientSourceBreakdown(): array
    {
        $payments = Payment::query()
            ->select('who_created', DB::raw('COUNT(*) as total'))
            ->groupBy('who_created')
            ->get();

        if ($payments->isEmpty()) {
            return [];
        }

        return $payments
            ->map(function ($payment) {
                $label = match ($payment->who_created) {
                    'client' => 'Сайт',
                    'instagram' => 'Instagram',
                    'referral' => 'Рекомендации',
                    'walk_in' => 'Проходящие',
                    default => Str::title(str_replace('_', ' ', (string) $payment->who_created)),
                };

                return [
                    'label' => $label,
                    'count' => (int) $payment->total,
                ];
            })
            ->values()
            ->all();
    }

    private function eventsGroupedByBarber(CarbonInterface $start, CarbonInterface $end): Collection
    {
        return $this->baseEventQuery()
            ->whereBetween('start', [$start, $end])
            ->whereNotNull('barber_id')
            ->get(['barber_id', 'start', 'end', 'status', 'category'])
            ->groupBy('barber_id')
            ->map(fn (Collection $events) => $events->values());
    }

    private function calculateAverageCheckForEvents(Collection $events, Collection $categories): ?float
    {
        if ($events->isEmpty()) {
            return null;
        }

        $amounts = $events->map(function (Event $event) use ($categories) {
            return $this->resolveEventAmount($event, $categories);
        })->filter(fn (?float $value) => $value !== null);

        if ($amounts->isEmpty()) {
            return null;
        }

        return round($amounts->average(), 2);
    }

    private function resolveEventAmount(Event $event, Collection $categories): ?float
    {
        $categoryKey = $event->category;

        if (empty($categoryKey)) {
            return null;
        }

        if (Str::isUuid($categoryKey)) {
            return optional($categories->get($categoryKey))->amount;
        }

        $labels = collect(config('timex.categories.labels', []));
        $amounts = collect(config('timex.categories.amounts', []));

        $index = $labels->search($categoryKey);

        if ($index === false) {
            return null;
        }

        return $amounts->get($index);
    }

    private function calculateUtilization(?Barber $barberProfile, Collection $events, CarbonInterface $periodStart, CarbonInterface $periodEnd): ?float
    {
        if (! $barberProfile) {
            return null;
        }

        $workingDays = collect($barberProfile->working_days ?? []);

        if ($workingDays->isEmpty()) {
            return null;
        }

        $shiftStart = $barberProfile->start_working_time;
        $shiftEnd = $barberProfile->end_working_time;

        if (! $shiftStart || ! $shiftEnd) {
            return null;
        }

        $shiftStartCarbon = Carbon::parse($shiftStart);
        $shiftEndCarbon = Carbon::parse($shiftEnd);

        if ($shiftEndCarbon->lessThanOrEqualTo($shiftStartCarbon)) {
            return null;
        }

        $dailyMinutes = $shiftStartCarbon->diffInMinutes($shiftEndCarbon);

        $period = CarbonPeriod::create($periodStart, '1 day', $periodEnd);

        $availableMinutes = 0;

        foreach ($period as $date) {
            if ($workingDays->contains(strtolower($date->englishDayOfWeek))) {
                $availableMinutes += $dailyMinutes;
            }
        }

        if ($availableMinutes === 0) {
            return null;
        }

        $bookedMinutes = $events
            ->filter(fn (Event $event) => in_array($event->status, Event::blockingStatuses(), true))
            ->sum(function (Event $event) {
                $start = Carbon::parse($event->start);
                $end = $event->end ? Carbon::parse($event->end) : $start->copy()->addHour();

                return $start->diffInMinutes($end);
            });

        return $bookedMinutes > 0
            ? round(min($bookedMinutes / $availableMinutes, 1) * 100, 1)
            : 0.0;
    }

    private function buildTopServices(Collection $categories): array
    {
        $monthStart = $this->now->startOfMonth();
        $monthEnd = $this->now->endOfMonth();

        $events = $this->baseEventQuery()
            ->whereBetween('start', [$monthStart, $monthEnd])
            ->whereNotNull('category')
            ->get(['category']);

        if ($events->isEmpty()) {
            return [];
        }

        $labels = collect(config('timex.categories.labels', []));

        return $events
            ->groupBy('category')
            ->map(fn (Collection $group) => $group->count())
            ->sortDesc()
            ->take(3)
            ->map(function ($count, $categoryId) use ($categories, $labels) {
                if (Str::isUuid($categoryId)) {
                    $category = $categories->get($categoryId);
                    $name = $category?->name ?? 'Услуга';
                    $price = $category?->amount;
                } else {
                    $name = $labels->get($categoryId, 'Услуга');
                    $price = null;
                }

                return array_filter([
                    'name' => $name,
                    'count' => $count,
                    'price' => $price,
                ], fn ($value) => $value !== null);
            })
            ->values()
            ->all();
    }

    private function calculateAverageVisitDuration(): ?int
    {
        $events = $this->baseEventQuery()
            ->where('status', Event::STATUS_COMPLETED)
            ->whereNotNull('start')
            ->whereBetween('start', [$this->now->subMonths(3), $this->now])
            ->get(['start', 'end']);

        if ($events->isEmpty()) {
            return null;
        }

        $minutes = $events->map(function (Event $event) {
            $start = Carbon::parse($event->start);
            $end = $event->end ? Carbon::parse($event->end) : $start->copy()->addHour();

            return $start->diffInMinutes($end);
        });

        if ($minutes->isEmpty()) {
            return null;
        }

        return (int) round($minutes->average());
    }

    private function sumPaymentsBetween(?CarbonInterface $start, ?CarbonInterface $end): float
    {
        $query = $this->basePaymentQuery();

        if ($start && $end) {
            $query->whereBetween(DB::raw('COALESCE(payment_date, DATE(created_at))'), [$start->toDateString(), $end->toDateString()]);
        }

        $categoryTable = config('timex.tables.category.name', 'timex_categories');

        $total = $query
            ->join($categoryTable.' as categories', 'categories.id', '=', 'payments.category_id')
            ->sum('categories.amount');

        return (float) $total;
    }

    private function calculateRevenueByBarber(Collection $categories, CarbonInterface $start, CarbonInterface $end): array
    {
        $events = $this->baseEventQuery()
            ->whereBetween('start', [$start, $end])
            ->where('status', Event::STATUS_COMPLETED)
            ->whereNotNull('barber_id')
            ->get(['barber_id', 'category']);

        if ($events->isEmpty()) {
            return [];
        }

        $eventsByBarber = $events->groupBy('barber_id');

        $barbers = User::query()
            ->whereIn('id', $eventsByBarber->keys()->all())
            ->get(['id', 'surname', 'name'])
            ->keyBy('id');

        return $eventsByBarber
            ->map(function (Collection $barberEvents, $barberId) use ($barbers, $categories) {
                $revenue = $barberEvents->sum(fn (Event $event) => $this->resolveEventAmount($event, $categories) ?? 0.0);
                $barber = $barbers->get($barberId);

                return [
                    'barber' => $barber ? trim($barber->surname.' '.$barber->name) : 'Барбер',
                    'revenue' => round($revenue, 2),
                ];
            })
            ->sortByDesc('revenue')
            ->values()
            ->all();
    }
}
