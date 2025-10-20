<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FormatsMetricChange;
use App\Filament\Widgets\Concerns\HasPeriodFilters;
use App\Support\DashboardMetrics;
use Filament\Widgets\Widget;

class DashboardStats extends Widget
{
    use HasPeriodFilters;
    use FormatsMetricChange;

    protected static ?string $pollingInterval = '60s';
    protected static string $view = 'filament.widgets.dashboard-stats';

    public ?string $filter = null;

    public function mount(): void
    {
        $this->filter ??= $this->getDefaultFilter();
    }

    public function setFilter(string $filter): void
    {
        if (! array_key_exists($filter, $this->getFilters() ?? [])) {
            return;
        }

        if ($this->filter === $filter) {
            return;
        }

        $this->filter = $filter;
    }

    protected function getViewData(): array
    {
        $filter = $this->filter ?? $this->getDefaultFilter();
        [$start, $end, $previousStart, $previousEnd] = $this->resolvePeriods($filter);

        $metricsService = app(DashboardMetrics::class);

        $bookingCurrent = $metricsService->bookings($start, $end);
        $bookingPrevious = $metricsService->bookings($previousStart, $previousEnd);

        $clientCurrent = $metricsService->clients($start, $end);
        $clientPrevious = $metricsService->clients($previousStart, $previousEnd);

        $barberMetrics = $metricsService->barberServices($start, $end);
        $financeCurrent = $metricsService->financials($start, $end);
        $financePrevious = $metricsService->financials($previousStart, $previousEnd);

        $groups = collect([
            $this->buildBookingGroup($bookingCurrent, $bookingPrevious),
            $this->buildClientGroup($clientCurrent, $clientPrevious),
            $this->buildBarberGroup($barberMetrics),
            $this->buildFinanceGroup($financeCurrent, $financePrevious),
        ])->map(function (array $group) {
            $group['styles'] = $this->accentStyles($group['accent']);

            return $group;
        })->all();

        return [
            'groups' => $groups,
            'filters' => $this->getFilters(),
            'currentFilter' => $filter,
        ];
    }

    private function buildBookingGroup(array $current, array $previous): array
    {
        $bookingsChange = $this->formatChangeData($current['total_bookings'], $previous['total_bookings']);
        $completedChange = $this->formatChangeData($current['completed'], $previous['completed']);
        $attendanceChange = $this->formatChangeData($current['attended_clients'], $previous['attended_clients']);
        $cancellationChange = $this->formatChangeData(
            $current['cancellations_total'],
            $previous['cancellations_total'],
            invert: true,
        );

        return [
            'title' => '1. Записи',
            'description' => 'Основные показатели загрузки и посещаемости за выбранный период.',
            'icon' => 'heroicon-o-calendar',
            'accent' => 'primary',
            'metrics' => [
                [
                    'label' => 'Количество записей',
                    'value' => number_format($current['total_bookings']),
                    'icon' => 'heroicon-o-clipboard-check',
                    'change' => $bookingsChange,
                ],
                [
                    'label' => 'Завершённые визиты',
                    'value' => number_format($current['completed']),
                    'icon' => 'heroicon-o-badge-check',
                    'change' => $completedChange,
                ],
                [
                    'label' => 'Фактическая посещаемость',
                    'value' => number_format($current['attended_clients']),
                    'icon' => 'heroicon-o-users',
                    'change' => $attendanceChange,
                    'helper' => sprintf('%.1f%% от всех записей', $current['attendance_rate']),
                ],
                [
                    'label' => 'Отменённые / неявки',
                    'value' => sprintf('%s / %s', number_format($current['cancelled']), number_format($current['no_show'])),
                    'icon' => 'heroicon-o-x-circle',
                    'change' => $cancellationChange,
                    'helper' => 'Всего отмен: ' . number_format($current['cancellations_total']),
                ],
                [
                    'label' => 'Процент отмен',
                    'value' => sprintf('%.1f%%', $current['cancellation_rate']),
                    'icon' => 'heroicon-o-chart-pie',
                    'helper' => $current['total_bookings'] > 0
                        ? sprintf('%d из %d записей', $current['cancellations_total'], $current['total_bookings'])
                        : 'Нет записей за период',
                ],
            ],
        ];
    }

    private function buildClientGroup(array $current, array $previous): array
    {
        $newClientsChange = $this->formatChangeData($current['new_clients'], $previous['new_clients']);
        $returningClientsChange = $this->formatChangeData($current['returning_clients'], $previous['returning_clients']);

        $returningHelper = $current['unique_clients'] > 0
            ? sprintf('%.1f%% от базы за период', $current['returning_rate'])
            : 'Нет данных за период';

        return [
            'title' => '2. Клиенты',
            'description' => 'Аналитика по новым и постоянным клиентам.',
            'icon' => 'heroicon-o-users',
            'accent' => 'success',
            'metrics' => [
                [
                    'label' => 'Новые клиенты',
                    'value' => number_format($current['new_clients']),
                    'icon' => 'heroicon-o-user-add',
                    'change' => $newClientsChange,
                ],
                [
                    'label' => 'Постоянные клиенты',
                    'value' => number_format($current['returning_clients']),
                    'icon' => 'heroicon-o-refresh',
                    'change' => $returningClientsChange,
                    'helper' => $returningHelper,
                ],
                [
                    'label' => 'Среднее число визитов',
                    'value' => number_format($current['average_visits_per_client'], 1),
                    'icon' => 'heroicon-o-finger-print',
                    'helper' => $current['unique_clients'] > 0
                        ? sprintf('%d визитов у %d клиентов', $current['total_visits'], $current['unique_clients'])
                        : 'Нет визитов за период',
                ],
                [
                    'label' => 'Интервал между визитами',
                    'value' => $current['average_visit_interval'] !== null
                        ? sprintf('%.1f дн.', $current['average_visit_interval'])
                        : 'Недостаточно данных',
                    'icon' => 'heroicon-o-clock',
                    'helper' => $current['average_visit_interval'] !== null
                        ? sprintf('На основании %d повторных визитов', $current['interval_count'])
                        : 'Мало данных для расчёта',
                ],
            ],
        ];
    }

    private function buildBarberGroup(array $metrics): array
    {
        $clientsPerBarber = $metrics['clients_per_barber'];
        $averageCheck = $metrics['average_check'];
        $averageOccupancy = $metrics['average_occupancy'];
        $workload = $metrics['workload'];
        $topServices = $metrics['top_services'];

        $topServicesText = empty($topServices['items'])
            ? 'Нет данных'
            : collect($topServices['items'])
                ->values()
                ->map(fn (array $service, int $index) => ($index + 1) . '. ' . $service['name'] . ' — ' . $service['count'])
                ->implode(' · ');

        return [
            'title' => '3. Барберы и услуги',
            'description' => 'Эффективность мастеров и популярность услуг.',
            'icon' => 'heroicon-o-briefcase',
            'accent' => 'warning',
            'metrics' => [
                [
                    'label' => 'Клиентов на мастера',
                    'value' => number_format($clientsPerBarber['average'], 1, ',', ' '),
                    'icon' => 'heroicon-o-user-group',
                    'helper' => $clientsPerBarber['active_barbers'] > 0
                        ? sprintf('Всего %d клиентов · %d мастера(-ов)', $clientsPerBarber['total_clients'], $clientsPerBarber['active_barbers'])
                        : 'Нет данных за период',
                ],
                [
                    'label' => 'Средний чек по мастеру',
                    'value' => 'Br ' . number_format($averageCheck['amount'], 2, ',', ' '),
                    'icon' => 'heroicon-o-cash',
                    'helper' => $averageCheck['total_revenue'] > 0
                        ? sprintf('Выручка Br %s · визитов %d', number_format($averageCheck['total_revenue'], 0, ',', ' '), $averageCheck['completed_visits'])
                        : 'Нет завершённых визитов',
                ],
                [
                    'label' => 'Загрузка барбера',
                    'value' => sprintf('%.1f%%', $averageOccupancy['percentage']),
                    'icon' => 'heroicon-o-chart-square-bar',
                    'helper' => $averageOccupancy['leader'] !== null
                        ? sprintf('Лидер: %s — %.1f%% · записей %d',
                            $averageOccupancy['leader']['name'],
                            $averageOccupancy['leader']['occupancy'],
                            $averageOccupancy['leader']['bookings'])
                        : 'Нет активных записей',
                ],
                [
                    'label' => 'Рабочее время занято',
                    'value' => sprintf('%.1f%%', $workload['percentage']),
                    'icon' => 'heroicon-o-chart-bar',
                    'status_color' => $workload['percentage'] > 85 ? 'danger' : 'success',
                    'helper' => $workload['available_minutes'] > 0
                        ? sprintf('Занято %s из %s ч.',
                            number_format($workload['occupied_minutes'] / 60, 1, ',', ' '),
                            number_format($workload['available_minutes'] / 60, 1, ',', ' '))
                        : 'Нет графиков для расчёта',
                ],
                [
                    'label' => 'ТОП-3 популярных услуг',
                    'value' => $topServicesText,
                    'icon' => 'heroicon-o-star',
                    'helper' => $topServices['items'] === []
                        ? 'Нет данных за период'
                        : sprintf('На основе %d визитов', $topServices['total']),
                ],
            ],
        ];
    }

    private function buildFinanceGroup(array $current, array $previous): array
    {
        $revenueChange = $this->formatChangeData($current['total_revenue'], $previous['total_revenue']);
        $averageCheckChange = $this->formatChangeData($current['average_check'], $previous['average_check']);

        $daily = $current['breakdowns']['daily'];
        $weekly = $current['breakdowns']['weekly'];
        $monthly = $current['breakdowns']['monthly'];
        $barbers = $current['barbers'];

        return [
            'title' => '4. Финансовая статистика',
            'description' => 'Для контроля доходности и выявления точек роста.',
            'icon' => 'heroicon-o-cash',
            'accent' => 'danger',
            'metrics' => [
                [
                    'label' => 'Выручка за период',
                    'value' => $this->formatCurrency($current['total_revenue']),
                    'icon' => 'heroicon-o-chart-bar',
                    'change' => $revenueChange,
                    'helper' => $current['completed_visits'] > 0
                        ? sprintf('Завершено визитов: %d', $current['completed_visits'])
                        : 'Нет завершённых визитов',
                ],
                [
                    'label' => 'Выручка по дням',
                    'value' => $this->formatCurrency($daily['total']),
                    'icon' => 'heroicon-o-sun',
                    'helper' => $this->formatRevenueHelper($daily),
                ],
                [
                    'label' => 'Выручка по неделям',
                    'value' => $this->formatCurrency($weekly['total']),
                    'icon' => 'heroicon-o-calendar-days',
                    'helper' => $this->formatRevenueHelper($weekly),
                ],
                [
                    'label' => 'Выручка по месяцам',
                    'value' => $this->formatCurrency($monthly['total']),
                    'icon' => 'heroicon-o-chart-pie',
                    'helper' => $this->formatRevenueHelper($monthly),
                ],
                [
                    'label' => 'Средний чек',
                    'value' => $this->formatCurrency($current['average_check'], 2),
                    'icon' => 'heroicon-o-receipt-percent',
                    'change' => $averageCheckChange,
                    'helper' => $current['completed_visits'] > 0
                        ? sprintf('На основе %d визитов', $current['completed_visits'])
                        : 'Нет данных для расчёта',
                ],
                [
                    'label' => 'Сравнение выручки по барберам',
                    'value' => $barbers['leader']
                        ? sprintf('%s — %s', $barbers['leader']['name'], $this->formatCurrency($barbers['leader']['amount']))
                        : 'Нет данных',
                    'icon' => 'heroicon-o-adjustments-horizontal',
                    'helper' => $this->formatBarberComparisonHelper($barbers),
                ],
            ],
        ];
    }

    private function formatChangeData(int|float $current, int|float $previous, bool $invert = false): array
    {
        $change = $this->formatChange($current, $previous, $invert);
        $change['class'] = $this->colorClass($change['color']);

        return $change;
    }

    private function formatCurrency(float $amount, int $precision = 0): string
    {
        $decimals = $precision > 0 ? $precision : 0;

        return 'Br ' . number_format($amount, $decimals, ',', ' ');
    }

    private function formatRevenueHelper(array $bucket): string
    {
        if (($bucket['count'] ?? 0) === 0) {
            return 'Нет данных за период';
        }

        if (($bucket['total'] ?? 0) <= 0) {
            return 'Нет данных за период';
        }

        $average = $this->formatCurrency($bucket['average'], 2);
        $top = $bucket['top'] ?? null;

        if (! $top) {
            return sprintf('Среднее значение: %s', $average);
        }

        $topAmount = $this->formatCurrency($top['amount']);

        return sprintf('Среднее: %s · Пик: %s — %s', $average, $top['label'], $topAmount);
    }

    private function formatBarberComparisonHelper(array $barbers): string
    {
        if (($barbers['count'] ?? 0) === 0 || empty($barbers['leader'])) {
            return 'Нет данных по мастерам';
        }

        $parts = [];

        $parts[] = sprintf('Средняя выручка: %s', $this->formatCurrency($barbers['average']));

        if (! empty($barbers['laggard']) && ($barbers['count'] ?? 0) > 1) {
            $parts[] = sprintf('Минимум: %s — %s',
                $barbers['laggard']['name'],
                $this->formatCurrency($barbers['laggard']['amount'])
            );

            if (($barbers['gap'] ?? 0) > 0) {
                $parts[] = sprintf('Разница: %s', $this->formatCurrency($barbers['gap']));
            }
        }

        $parts[] = sprintf('Мастеров в срезе: %d', $barbers['count']);

        return implode(' · ', $parts);
    }

    private function colorClass(string $color): string
    {
        return match ($color) {
            'success' => 'text-success-600 dark:text-success-400',
            'danger' => 'text-danger-600 dark:text-danger-400',
            'warning' => 'text-warning-600 dark:text-warning-400',
            'primary' => 'text-primary-600 dark:text-primary-400',
            default => 'text-gray-500 dark:text-gray-400',
        };
    }

    private function accentStyles(string $accent): array
    {
        return match ($accent) {
            'success' => [
                'badge' => 'bg-success-100 text-success-600 dark:bg-success-500/10 dark:text-success-400',
                'chip' => 'bg-success-50 text-success-500 dark:bg-success-500/10 dark:text-success-300',
                'hover' => 'hover:border-success-200',
            ],
            'warning' => [
                'badge' => 'bg-warning-100 text-warning-600 dark:bg-warning-500/10 dark:text-warning-400',
                'chip' => 'bg-warning-50 text-warning-500 dark:bg-warning-500/10 dark:text-warning-300',
                'hover' => 'hover:border-warning-200',
            ],
            'danger' => [
                'badge' => 'bg-danger-100 text-danger-600 dark:bg-danger-500/10 dark:text-danger-400',
                'chip' => 'bg-danger-50 text-danger-500 dark:bg-danger-500/10 dark:text-danger-300',
                'hover' => 'hover:border-danger-200',
            ],
            default => [
                'badge' => 'bg-primary-100 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400',
                'chip' => 'bg-primary-50 text-primary-500 dark:bg-primary-500/10 dark:text-primary-300',
                'hover' => 'hover:border-primary-200',
            ],
        };
    }
}
