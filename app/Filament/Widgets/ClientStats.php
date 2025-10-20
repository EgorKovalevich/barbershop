<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithPeriodFilters;
use App\Models\Event;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Collection;

class ClientStats extends StatsOverviewWidget
{
    use InteractsWithPeriodFilters;

    protected function getHeading(): string
    {
        return 'Клиенты';
    }

    protected function getCards(): array
    {
        $filter = $this->filter ?? $this->getDefaultFilter();

        [$start, $end] = $this->resolveCurrentPeriod($filter);

        $metrics = $this->collectMetrics($start, $end);

        return [
            Card::make('Новые клиенты', number_format($metrics['new_clients']))
                ->description('Первое посещение в выбранный период')
                ->descriptionIcon('heroicon-o-user-plus')
                ->descriptionColor('success')
                ->extraAttributes(['class' => 'min-h-[164px]']),
            Card::make('Постоянные клиенты', number_format($metrics['loyal_clients']))
                ->description('С 2+ визитами за всё время')
                ->descriptionIcon('heroicon-o-users')
                ->descriptionColor('primary')
                ->extraAttributes(['class' => 'min-h-[164px]']),
            Card::make('Среднее число визитов', sprintf('%.2f', $metrics['avg_visits_per_client']))
                ->description('На клиента за период (по завершённым визитам)')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->descriptionColor('primary')
                ->extraAttributes(['class' => 'min-h-[164px]']),
            Card::make('Средний интервал', $this->formatInterval($metrics['avg_interval_days']))
                ->description('Между визитами (по завершённым визитам)')
                ->descriptionIcon('heroicon-o-calendar')
                ->descriptionColor('secondary')
                ->extraAttributes(['class' => 'min-h-[164px]']),
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveCurrentPeriod(?string $filter): array
    {
        [$start, $end] = array_slice($this->resolvePeriods($filter), 0, 2);

        return [$start, $end];
    }

    private function collectMetrics(Carbon $start, Carbon $end): array
    {
        $clientIds = Event::query()
            ->whereNotNull('organizer_id')
            ->whereBetween('start', [$start, $end])
            ->distinct()
            ->pluck('organizer_id');

        if ($clientIds->isEmpty()) {
            return [
                'new_clients' => 0,
                'loyal_clients' => 0,
                'avg_visits_per_client' => 0.0,
                'avg_interval_days' => null,
            ];
        }

        $firstVisits = Event::query()
            ->selectRaw('organizer_id, MIN(start) as first_visit_at')
            ->whereNotNull('organizer_id')
            ->whereIn('organizer_id', $clientIds)
            ->groupBy('organizer_id')
            ->pluck('first_visit_at', 'organizer_id')
            ->map(fn (string $date): Carbon => Carbon::parse($date));

        $completedVisits = Event::query()
            ->selectRaw('organizer_id, COUNT(*) as completed_visits')
            ->whereNotNull('organizer_id')
            ->whereIn('organizer_id', $clientIds)
            ->where('status', Event::STATUS_COMPLETED)
            ->groupBy('organizer_id')
            ->pluck('completed_visits', 'organizer_id');

        $periodCompletedVisits = Event::query()
            ->whereNotNull('organizer_id')
            ->whereBetween('start', [$start, $end])
            ->where('status', Event::STATUS_COMPLETED)
            ->get(['organizer_id', 'start'])
            ->groupBy('organizer_id');

        $newClients = $clientIds
            ->filter(fn ($id): bool => $firstVisits[$id]?->between($start, $end, true) ?? false)
            ->count();

        $loyalClients = $clientIds
            ->filter(fn ($id): bool => ($completedVisits[$id] ?? 0) >= 2)
            ->count();

        $totalCompletedVisits = $periodCompletedVisits->sum(fn (Collection $visits) => $visits->count());
        $uniqueCompletedClients = $periodCompletedVisits->count();
        $avgVisitsPerClient = $uniqueCompletedClients > 0
            ? round($totalCompletedVisits / $uniqueCompletedClients, 2)
            : 0.0;

        $averageInterval = $this->calculateAverageInterval($start, $end, $clientIds);

        return [
            'new_clients' => $newClients,
            'loyal_clients' => $loyalClients,
            'avg_visits_per_client' => $avgVisitsPerClient,
            'avg_interval_days' => $averageInterval,
        ];
    }

    private function calculateAverageInterval(Carbon $start, Carbon $end, Collection $clientIds): ?float
    {
        $completedEvents = Event::query()
            ->whereNotNull('organizer_id')
            ->whereIn('organizer_id', $clientIds)
            ->where('status', Event::STATUS_COMPLETED)
            ->where('start', '<=', $end)
            ->orderBy('organizer_id')
            ->orderBy('start')
            ->get(['organizer_id', 'start']);

        if ($completedEvents->isEmpty()) {
            return null;
        }

        $intervals = [];

        $completedEvents
            ->groupBy('organizer_id')
            ->each(function (Collection $visits) use (&$intervals, $start, $end): void {
                $visits = $visits->values();

                for ($i = 1; $i < $visits->count(); $i++) {
                    /** @var Carbon $current */
                    $current = $visits[$i]->start;

                    if (! $current->between($start, $end, true)) {
                        continue;
                    }

                    /** @var Carbon $previous */
                    $previous = $visits[$i - 1]->start;
                    $intervals[] = $previous->diffInDays($current);
                }
            });

        if (empty($intervals)) {
            return null;
        }

        return round(array_sum($intervals) / count($intervals), 1);
    }

    private function formatInterval(?float $days): string
    {
        if ($days === null) {
            return '—';
        }

        if ($days <= 0) {
            return '0 дней';
        }

        if ($days < 1) {
            return sprintf('%.1f дня', $days);
        }

        if (abs($days - round($days)) < 0.1) {
            return sprintf('%d дней', (int) round($days));
        }

        return sprintf('~%.1f дня', $days);
    }
}
