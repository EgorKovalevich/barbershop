<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardStats;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected function getWidgets(): array
    {
        return [
            DashboardStats::class,
        ];
    }

    protected function getColumns(): int|array
    {
        return 1;
    }
}
