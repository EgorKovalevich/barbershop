<x-filament::section>
    @php
        $periodLabels = [
            'day' => 'За день',
            'week' => 'За неделю',
            'month' => 'За месяц',
        ];

        $formatNumber = static fn ($value) => $value === null ? '—' : number_format($value, 0, ',', ' ');
        $formatFloat = static fn ($value, $decimals = 1) => $value === null ? '—' : number_format($value, $decimals, ',', ' ');
        $formatCurrency = static fn ($value) => $value === null ? '—' : ('Br ' . number_format($value, 2, ',', ' '));
    @endphp

    <div class="space-y-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Записи и посещаемость</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                @foreach ($periodLabels as $key => $label)
                    @php
                        $period = $stats['bookings']['periods'][$key] ?? null;
                    @endphp
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Количество записей {{ strtolower($label) }}</div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $period ? $formatNumber($period['current']) : '—' }}</span>
                            @if ($period && $period['trend'])
                                @php
                                    $trend = $period['trend'];
                                    $trendIcon = [
                                        'up' => 'heroicon-o-arrow-trending-up',
                                        'down' => 'heroicon-o-arrow-trending-down',
                                        'flat' => 'heroicon-o-arrows-right-left',
                                    ][$trend['direction']] ?? 'heroicon-o-arrows-right-left';
                                @endphp
                                <span @class([
                                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-success-100 text-success-800 dark:bg-success-500/10 dark:text-success-400' => $trend['direction'] === 'up',
                                    'bg-danger-100 text-danger-800 dark:bg-danger-500/10 dark:text-danger-400' => $trend['direction'] === 'down',
                                    'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' => $trend['direction'] === 'flat',
                                ])>
                                    <x-filament::icon :icon="$trendIcon" class="mr-1 h-3 w-3" />
                                    {{ $trend['percentage'] === null ? 'новый рост' : (($trend['percentage'] > 0 ? '+' : '') . $trend['percentage'] . '%') }}
                                </span>
                            @endif
                        </div>
                        @if ($period && $period['previous'] !== null)
                            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Предыдущий период: {{ $formatNumber($period['previous']) }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Завершённые визиты (месяц)</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $formatNumber($stats['bookings']['completed']['visits'] ?? null) }}</div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Клиентов пришло: {{ $formatNumber($stats['bookings']['completed']['clients'] ?? null) }}</div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Отмены и неявки (месяц)</div>
                    <div class="mt-2 flex items-baseline gap-3 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        <span>{{ $formatNumber($stats['bookings']['cancellations']['cancelled'] ?? null) }}</span>
                        <span class="text-base font-medium text-gray-500 dark:text-gray-400">отмен</span>
                        <span>{{ $formatNumber($stats['bookings']['cancellations']['no_show'] ?? null) }}</span>
                        <span class="text-base font-medium text-gray-500 dark:text-gray-400">неявок</span>
                    </div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Доля отмен: {{ $formatFloat($stats['bookings']['cancellations']['rate'] ?? null) }}%
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Повторные визиты (месяц)</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $formatNumber($stats['bookings']['retention']['clients'] ?? null) }}</div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Retention Rate: {{ $formatFloat($stats['bookings']['retention']['rate'] ?? null) }}%</div>
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Клиенты</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-4">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Новые клиенты (месяц)</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $formatNumber($stats['clients']['new_clients'] ?? null) }}</div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Постоянные клиенты</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $formatNumber($stats['clients']['loyal_clients'] ?? null) }}</div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Среднее число визитов (90 дней)</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $formatFloat($stats['clients']['average_visits'] ?? null, 2) }}</div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Средний интервал между визитами</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $stats['clients']['average_interval_days'] ? $formatFloat($stats['clients']['average_interval_days']) . ' дн.' : '—' }}
                    </div>
                </div>
            </div>

            <div class="mt-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Источники клиентов</div>
                <div class="mt-3 grid gap-4 md:grid-cols-4">
                    @forelse ($stats['clients']['sources'] as $source)
                        <div>
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $source['label'] }}</div>
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $formatNumber($source['count'] ?? null) }} записей</div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Данные появятся после указания источника в платежах или CRM.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Барберы и услуги</h3>
            <div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-300">Барбер</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-300">Клиенты/день</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-300">Клиенты/неделя</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-300">Клиенты/месяц</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-300">Средний чек</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-300">Загрузка (д/н/м)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($stats['barbers']['barbers'] as $barber)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $barber['name'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $formatNumber($barber['counts']['day'] ?? null) }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $formatNumber($barber['counts']['week'] ?? null) }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $formatNumber($barber['counts']['month'] ?? null) }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $formatCurrency($barber['average_check'] ?? null) }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    <div class="flex flex-col text-xs">
                                        <span>День: {{ $formatFloat($barber['utilization']['day'] ?? null) }}%</span>
                                        <span>Неделя: {{ $formatFloat($barber['utilization']['week'] ?? null) }}%</span>
                                        <span>Месяц: {{ $formatFloat($barber['utilization']['month'] ?? null) }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-3 text-center text-sm text-gray-500 dark:text-gray-400">Нет данных о барберах.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">ТОП-3 услуг (месяц)</div>
                    <ul class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-300">
                        @forelse ($stats['barbers']['top_services'] as $service)
                            <li class="flex items-baseline justify-between">
                                <span>{{ $service['name'] ?? 'Услуга' }} ({{ $service['count'] }})</span>
                                @if (isset($service['price']))
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $formatCurrency($service['price']) }}</span>
                                @endif
                            </li>
                        @empty
                            <li class="text-sm text-gray-500 dark:text-gray-400">Нет данных о популярности услуг.</li>
                        @endforelse
                    </ul>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Средняя длительность визита</div>
                    <div class="mt-3 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $stats['barbers']['average_duration_minutes'] ? $formatNumber($stats['barbers']['average_duration_minutes']) . ' мин.' : '—' }}
                    </div>
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Финансовая статистика</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-4">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Выручка за день</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $formatCurrency($stats['finance']['revenue']['day'] ?? null) }}</div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Выручка за неделю</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $formatCurrency($stats['finance']['revenue']['week'] ?? null) }}</div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Выручка за месяц</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $formatCurrency($stats['finance']['revenue']['month'] ?? null) }}</div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Средний чек</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $formatCurrency($stats['finance']['average_check'] ?? null) }}</div>
                </div>
            </div>

            <div class="mt-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Выручка по мастерам (месяц)</div>
                <ul class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    @forelse ($stats['finance']['revenue_by_barber'] as $row)
                        <li class="flex items-center justify-between">
                            <span>{{ $row['barber'] }}</span>
                            <span class="font-medium">{{ $formatCurrency($row['revenue'] ?? null) }}</span>
                        </li>
                    @empty
                        <li class="text-sm text-gray-500 dark:text-gray-400">Нет данных о выручке по барберам.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Сайт и онлайн-активность</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Онлайн-записи (месяц)</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $formatNumber($stats['marketing']['online_bookings'] ?? null) }}</div>
                </div>
                <div class="rounded-xl border border-dashed border-gray-200 bg-white p-4 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <p class="font-medium text-gray-700 dark:text-gray-200">Посетители сайта и конверсия</p>
                    <p class="mt-2 text-gray-500 dark:text-gray-400">Подключите Google Analytics, Яндекс.Метрику или CRM, чтобы получать данные о трафике, источниках и конверсии.</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Популярные слоты для записи</div>
                    <ul class="mt-2 space-y-1 text-sm text-gray-700 dark:text-gray-300">
                        @forelse ($stats['marketing']['popular_slots'] as $slot)
                            <li>{{ $slot['slot'] }} — {{ $formatNumber($slot['count'] ?? null) }} записей</li>
                        @empty
                            <li class="text-sm text-gray-500 dark:text-gray-400">Пока нет данных по слотам.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Технические метрики</h3>
            <div class="mt-4 rounded-xl border border-dashed border-gray-200 bg-white p-4 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <p class="font-medium text-gray-700 dark:text-gray-200">Интеграция с аналитикой</p>
                <p class="mt-2 text-gray-500 dark:text-gray-400">Подключите внешний источник (Google Analytics, Яндекс.Метрика, YClients и т.п.), чтобы отображать сессии, устройства и географию посетителей.</p>
            </div>
        </div>
    </div>
</x-filament::section>
