<?php

namespace App\Filament\Widgets;

use App\Services\AdminDashboard\AdminDashboardStatsService;
use Filament\Widgets\Widget;

class AdminDashboardStatsWidget extends Widget
{
    protected static ?int $sort = -10;

    protected static string $view = 'filament.widgets.admin-dashboard-stats';

    protected function getViewData(): array
    {
        $stats = app(AdminDashboardStatsService::class)->getStats();

        return [
            'stats' => $stats,
        ];
    }
}
