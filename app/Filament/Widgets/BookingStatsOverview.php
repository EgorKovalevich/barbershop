<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class BookingStatsOverview extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '60s';

    protected function getCards(): array
    {
        $period = $this->filter ?? 'day';

        [$start, $end, $previousStart, $previousEnd] = $this->resolvePeriodBounds($period);

        $periodQuery = Event::query()->whereBetween('start', [$start, $end]);

        $total = (clone $periodQuery)->count();
        $completed = (clone $periodQuery)->where('status', Event::STATUS_COMPLETED)->count();
        $cancelled = (clone $periodQuery)->where('status', Event::STATUS_CANCELLED)->count();
        $noShow = (clone $periodQuery)->where('status', Event::STATUS_NO_SHOW)->count();

        $previousTotal = Event::query()
            ->whereBetween('start', [$previousStart, $previousEnd])
            ->count();

        $trend = $this->makeTrendMeta($total, $previousTotal);

        $completionRate = $total > 0 ? round($completed / $total * 100, 1) : 0.0;
        $cancelTotal = $cancelled + $noShow;
        $cancelRate = $total > 0 ? round($cancelTotal / $total * 100, 1) : 0.0;

        $returningClients = $this->countReturningClients($start, $end);
        $uniqueClients = $this->countUniqueClients($start, $end);
        $retentionRate = $uniqueClients > 0 ? round($returningClients / $uniqueClients * 100, 1) : null;

        return [
            Card::make('Количество записей', $this->formatNumber($total))
                ->description($trend['description'])
                ->descriptionIcon($trend['icon'])
                ->color($trend['color']),

            Card::make('Завершённые визиты', $this->formatNumber($completed))
                ->description($total > 0 ? sprintf('Доля: %s%%', $this->formatNumber($completionRate, 1)) : 'Нет данных')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Card::make('Отмены и неявки', $this->formatNumber($cancelTotal))
                ->description($total > 0
                    ? sprintf('%.1f%% от всех записей', $cancelRate)
                    : 'Нет данных')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color($cancelTotal > 0 ? 'danger' : 'secondary'),

            Card::make('Повторные записи', $this->formatNumber($returningClients))
                ->description($retentionRate !== null
                    ? sprintf('Retention: %s%%', $this->formatNumber($retentionRate, 1))
                    : 'Нет данных')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color($returningClients > 0 ? 'primary' : 'secondary'),
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            'day' => 'День',
            'week' => 'Неделя',
            'month' => 'Месяц',
        ];
    }

    private function resolvePeriodBounds(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'week' => [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
                $now->copy()->subWeek()->startOfWeek(),
                $now->copy()->subWeek()->endOfWeek(),
            ],
            'month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth(),
            ],
            default => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
            ],
        };
    }

    private function makeTrendMeta(int $current, int $previous): array
    {
        if ($previous === 0) {
            if ($current === 0) {
                return [
                    'description' => 'Без изменений',
                    'icon' => 'heroicon-o-minus-small',
                    'color' => 'secondary',
                ];
            }

            return [
                'description' => sprintf('+%s (нет данных для сравнения)', $this->formatNumber($current)),
                'icon' => 'heroicon-o-arrow-trending-up',
                'color' => 'success',
            ];
        }

        $difference = $current - $previous;

        if ($difference === 0) {
            return [
                'description' => 'На уровне прошлого периода',
                'icon' => 'heroicon-o-minus-small',
                'color' => 'secondary',
            ];
        }

        $percent = round($difference / $previous * 100, 1);
        $percentFormatted = $this->formatNumber(abs($percent), 1);
        $percentPrefix = $percent > 0 ? '+' : ($percent < 0 ? '-' : '');
        $differencePrefix = $difference > 0 ? '+' : ($difference < 0 ? '-' : '');

        return [
            'description' => sprintf('%s%d (%s%s%%)', $differencePrefix, abs($difference), $percentPrefix, $percentFormatted),
            'icon' => $difference > 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down',
            'color' => $difference > 0 ? 'success' : 'danger',
        ];
    }

    private function countReturningClients(Carbon $start, Carbon $end): int
    {
        $table = (new Event())->getTable();

        return Event::query()
            ->whereBetween('start', [$start, $end])
            ->whereNotNull('number')
            ->whereIn('number', function ($query) use ($start, $table) {
                $query->select('number')
                    ->from($table)
                    ->whereNotNull('number')
                    ->where('start', '<', $start);
            })
            ->distinct('number')
            ->count('number');
    }

    private function countUniqueClients(Carbon $start, Carbon $end): int
    {
        return Event::query()
            ->whereBetween('start', [$start, $end])
            ->whereNotNull('number')
            ->distinct('number')
            ->count('number');
    }

    private function formatNumber(float|int $value, int $precision = 0): string
    {
        return number_format($value, $precision, ',', ' ');
    }
}
