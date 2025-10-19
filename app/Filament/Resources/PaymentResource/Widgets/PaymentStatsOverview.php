<?php

namespace App\Filament\Resources\PaymentResource\Widgets;

use App\Models\Payment;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class PaymentStatsOverview extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('Количество платежей', Payment::all()->count()),
            Card::make('Сумма платежей', 'Br' . ' ' . Payment::all()->where('paid', 1)->sum('category.amount')),
            Card::make('Последние платежи (7 дней)', 'Br' . ' ' . Payment::all()->where('paid', 1)->where('created_at', '>=', Carbon::now()->subDays(7))->sum('category.amount')),
        ];
    }
}
