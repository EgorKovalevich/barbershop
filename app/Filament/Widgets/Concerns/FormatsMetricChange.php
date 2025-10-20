<?php

namespace App\Filament\Widgets\Concerns;

trait FormatsMetricChange
{
    protected function formatChange(int|float $current, int|float $previous, bool $invert = false): array
    {
        if ($previous === 0.0 || $previous === 0) {
            if ($current === 0.0 || $current === 0) {
                return [
                    'description' => 'Без изменений',
                    'icon' => 'heroicon-o-minus',
                    'color' => 'secondary',
                ];
            }

            return [
                'description' => 'Рост на 100%',
                'icon' => 'heroicon-o-trending-up',
                'color' => $invert ? 'danger' : 'success',
            ];
        }

        $change = (($current - $previous) / $previous) * 100;
        $rounded = round(abs($change), 1);

        if (abs($change) < 0.05) {
            return [
                'description' => 'Без изменений',
                'icon' => 'heroicon-o-minus',
                'color' => 'secondary',
            ];
        }

        if ($change > 0) {
            return [
                'description' => 'Рост на ' . $rounded . '%',
                'icon' => 'heroicon-o-trending-up',
                'color' => $invert ? 'danger' : 'success',
            ];
        }

        return [
            'description' => 'Спад на ' . $rounded . '%',
            'icon' => 'heroicon-o-trending-down',
            'color' => $invert ? 'success' : 'danger',
        ];
    }
}
