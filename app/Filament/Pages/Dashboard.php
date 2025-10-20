<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BookingStats;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets;

class Dashboard extends BaseDashboard
{
    protected function getWidgets(): array
    {
        return [
            BookingStats::class,
            Widgets\AccountWidget::class,
            Widgets\FilamentInfoWidget::class,
        ];
    }

    protected function getColumns(): int|array
    {
        return [
            'default' => 2,
            'lg' => 3,
        ];
    }
}
