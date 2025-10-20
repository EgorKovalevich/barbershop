<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AdminDashboardStatsWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;

class Dashboard extends BaseDashboard
{
    protected function getWidgets(): array
    {
        return [
            AdminDashboardStatsWidget::class,
            AccountWidget::class,
            FilamentInfoWidget::class,
        ];
    }

    protected function getColumns(): int|string|array
    {
        return [
            'default' => 1,
            'xl' => 2,
        ];
    }
}
