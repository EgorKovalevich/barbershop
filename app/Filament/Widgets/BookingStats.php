<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FormatsMetricChange;
use App\Filament\Widgets\Concerns\HasPeriodFilters;
use App\Models\Event;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class BookingStats extends StatsOverviewWidget
{
    use HasPeriodFilters;
    use FormatsMetricChange;

    protected static ?string $pollingInterval = '60s';
    protected static ?string $heading = '1. Записи';

    protected function getCards(): array
    {
        $filter = $this->filter ?? $this->getDefaultFilter();

        [$start, $end, $previousStart, $previousEnd] = $this->resolvePeriods($filter);

        $metrics = $this->collectMetrics($start, $end);
        $previousMetrics = $this->collectMetrics($previousStart, $previousEnd);

        $bookingsChange = $this->formatChange($metrics['total_bookings'], $previousMetrics['total_bookings']);
        $completedChange = $this->formatChange($metrics['completed'], $previousMetrics['completed']);
        $attendanceChange = $this->formatChange($metrics['attended_clients'], $previousMetrics['attended_clients']);
        $cancellationChange = $this->formatChange(
            $metrics['cancellations_total'],
            $previousMetrics['cancellations_total'],
            invert: true,
        );

        return [
            Card::make('Количество записей', number_format($metrics['total_bookings']))
                ->description($bookingsChange['description'])
                ->descriptionIcon($bookingsChange['icon'])
                ->descriptionColor($bookingsChange['color'])
                ->chart($this->bookingTrend($filter, $start, $end))
                ->extraAttributes(['class' => 'min-h-[164px]']),
            Card::make('Завершённые визиты', number_format($metrics['completed']))
                ->description($completedChange['description'])
                ->descriptionIcon($completedChange['icon'])
                ->descriptionColor($completedChange['color'])
                ->extraAttributes(['class' => 'min-h-[164px]']),
            Card::make('Фактическая посещаемость', number_format($metrics['attended_clients']))
                ->description($attendanceChange['description'] . ' · ' . sprintf('%.1f%% от записей', $metrics['attendance_rate']))
                ->descriptionIcon($attendanceChange['icon'])
                ->descriptionColor($attendanceChange['color'])
                ->extraAttributes(['class' => 'min-h-[164px]']),
            Card::make('Отменённые / неявки', sprintf('%s / %s', number_format($metrics['cancelled']), number_format($metrics['no_show'])))
                ->description($cancellationChange['description'] . ' · всего ' . number_format($metrics['cancellations_total']))
                ->descriptionIcon($cancellationChange['icon'])
                ->descriptionColor($cancellationChange['color'])
                ->extraAttributes(['class' => 'min-h-[164px]']),
            Card::make('Процент отмен', sprintf('%.1f%%', $metrics['cancellation_rate']))
                ->description($metrics['total_bookings'] > 0
                    ? sprintf('%d из %d записей', $metrics['cancellations_total'], $metrics['total_bookings'])
                    : 'Нет записей за период')
                ->descriptionColor($metrics['cancellation_rate'] > 15 ? 'danger' : 'success')
                ->extraAttributes(['class' => 'min-h-[164px]']),
        ];
    }

    private function collectMetrics(Carbon $start, Carbon $end): array
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

    private function bookingTrend(?string $filter, Carbon $start, Carbon $end): array
    {
        $builder = Trend::model(Event::class)->between($start, $end);

        $trendBuilder = match ($filter) {
            'day' => $builder->perHour(),
            'month' => $builder->perDay(),
            default => $builder->perDay(),
        };

        $trend = $trendBuilder->count();

        return $trend->map(fn (TrendValue $value): int => (int) $value->aggregate)->toArray();
    }
}
