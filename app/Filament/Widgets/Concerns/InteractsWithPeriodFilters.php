<?php

namespace App\Filament\Widgets\Concerns;

use Carbon\Carbon;

trait InteractsWithPeriodFilters
{
    protected static ?string $pollingInterval = '60s';

    protected function getFilters(): ?array
    {
        return [
            'day' => 'Сегодня',
            'week' => 'Неделя',
            'month' => 'Месяц',
        ];
    }

    protected function getDefaultFilter(): ?string
    {
        return 'week';
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: Carbon, 3: Carbon}
     */
    protected function resolvePeriods(?string $filter): array
    {
        $filter ??= $this->getDefaultFilter();

        $now = Carbon::now();

        return match ($filter) {
            'day' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
            ],
            'month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth(),
            ],
            default => [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
                $now->copy()->subWeek()->startOfWeek(),
                $now->copy()->subWeek()->endOfWeek(),
            ],
        };
    }
}
