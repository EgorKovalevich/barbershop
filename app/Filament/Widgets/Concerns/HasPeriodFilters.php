<?php

namespace App\Filament\Widgets\Concerns;

use Carbon\Carbon;

trait HasPeriodFilters
{
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

    protected function resolvePeriods(?string $filter): array
    {
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
