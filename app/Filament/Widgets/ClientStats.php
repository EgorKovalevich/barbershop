<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FormatsMetricChange;
use App\Filament\Widgets\Concerns\HasPeriodFilters;
use App\Models\Event;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class ClientStats extends StatsOverviewWidget
{
    use HasPeriodFilters;
    use FormatsMetricChange;

    protected static ?string $pollingInterval = '60s';
    protected static ?string $heading = '2. Клиенты';

    protected function getCards(): array
    {
        $filter = $this->filter ?? $this->getDefaultFilter();

        [$start, $end, $previousStart, $previousEnd] = $this->resolvePeriods($filter);

        $metrics = $this->collectMetrics($start, $end);
        $previousMetrics = $this->collectMetrics($previousStart, $previousEnd);

        $newClientsChange = $this->formatChange($metrics['new_clients'], $previousMetrics['new_clients']);
        $returningClientsChange = $this->formatChange($metrics['returning_clients'], $previousMetrics['returning_clients']);

        $returningDescription = $metrics['unique_clients'] > 0
            ? $returningClientsChange['description'] . ' · ' . sprintf('%.1f%% от базы за период', $metrics['returning_rate'])
            : 'Нет данных за период';

        return [
            Card::make('Новые клиенты', number_format($metrics['new_clients']))
                ->description($newClientsChange['description'])
                ->descriptionIcon($newClientsChange['icon'])
                ->descriptionColor($newClientsChange['color'])
                ->extraAttributes(['class' => 'min-h-[164px]']),
            Card::make('Постоянные клиенты', number_format($metrics['returning_clients']))
                ->description($returningDescription)
                ->descriptionIcon($returningClientsChange['icon'])
                ->descriptionColor($returningClientsChange['color'])
                ->extraAttributes(['class' => 'min-h-[164px]']),
            Card::make('Среднее количество визитов', sprintf('%.1f', $metrics['average_visits_per_client']))
                ->description(
                    $metrics['unique_clients'] > 0
                        ? sprintf('%d визитов у %d клиентов', $metrics['total_visits'], $metrics['unique_clients'])
                        : 'Нет визитов за период'
                )
                ->descriptionColor('primary')
                ->extraAttributes(['class' => 'min-h-[164px]']),
            Card::make('Средний интервал между визитами', $metrics['average_visit_interval'] !== null
                ? sprintf('%.1f дн.', $metrics['average_visit_interval'])
                : 'Недостаточно данных')
                ->description('Время между визитами одного клиента')
                ->descriptionColor($metrics['average_visit_interval'] !== null ? 'secondary' : 'warning')
                ->extraAttributes(['class' => 'min-h-[164px]']),
        ];
    }

    private function collectMetrics(Carbon $start, Carbon $end): array
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

        $intervals = [];
        $previousVisits = [];

        $events = (clone $eventsQuery)
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

        $averageInterval = count($intervals) > 0
            ? round(array_sum($intervals) / count($intervals), 1)
            : null;

        return [
            'new_clients' => $newClients,
            'returning_clients' => $returningClients,
            'returning_rate' => $returningRate,
            'total_visits' => $totalVisits,
            'unique_clients' => $uniqueClients,
            'average_visits_per_client' => $averageVisitsPerClient,
            'average_visit_interval' => $averageInterval,
        ];
    }
}
