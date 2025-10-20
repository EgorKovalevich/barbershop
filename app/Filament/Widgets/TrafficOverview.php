<?php

namespace App\Filament\Widgets;

use App\Support\DashboardMetrics;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class TrafficOverview extends StatsOverviewWidget
{
    protected function getCards(): array
    {
        $end = Carbon::now();
        $start = $end->copy()->subDays(29);

        $metricsService = app(DashboardMetrics::class);
        $siteActivity = $metricsService->siteActivity($start, $end);
        $technical = $metricsService->technical($start, $end, $siteActivity);

        $averageSessionDuration = $this->formatDuration($technical['average_session_duration'] ?? 0);
        $conversionRate = $siteActivity['conversion_rate'] ?? 0.0;
        $bookingsTotal = $siteActivity['bookings_total'] ?? 0;

        return [
            Card::make('Сессии (30 дней)', number_format($technical['sessions'] ?? 0, 0, ',', ' '))
                ->description('Последние 30 дней')
                ->descriptionIcon('heroicon-o-chart-bar'),
            Card::make('Уникальные пользователи', number_format($technical['unique_users'] ?? 0, 0, ',', ' '))
                ->description(sprintf('Конверсия %.1f%%', $conversionRate))
                ->descriptionIcon('heroicon-o-user-group'),
            Card::make('Среднее время на сайте', $averageSessionDuration)
                ->description(sprintf('Записей: %s', number_format($bookingsTotal, 0, ',', ' ')))
                ->descriptionIcon('heroicon-o-clock'),
        ];
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '—';
        }

        $minutes = intdiv($seconds, 60);
        $remaining = $seconds % 60;

        return sprintf('%d:%02d', $minutes, $remaining);
    }
}
