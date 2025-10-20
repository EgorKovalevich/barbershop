<x-filament::widget class="filament-dashboard-stats">
    <x-filament::card>
        <div class="flex flex-col gap-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white">
                        Общая статистика салона
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Данные обновляются автоматически каждые 60 секунд. Выберите период, чтобы увидеть динамику.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($filters as $key => $label)
                        @php
                            $buttonClasses = \Illuminate\Support\Arr::toCssClasses([
                                'relative min-w-[96px] overflow-hidden rounded-full px-5 py-2 text-sm font-semibold transition-all duration-300 ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-gray-900',
                                'bg-gradient-to-r from-primary-500 to-primary-600 text-white shadow-lg shadow-primary-500/30 ring-0 scale-[1.02]' => $currentFilter === $key,
                                'bg-white/80 text-gray-600 ring-1 ring-gray-200/70 hover:ring-primary-200 hover:text-primary-600 hover:shadow-md dark:bg-gray-800/70 dark:text-gray-300 dark:ring-gray-700/70 dark:hover:ring-primary-400/40 dark:hover:text-primary-300' => $currentFilter !== $key,
                            ]);
                        @endphp
                        <x-filament::button
                            type="button"
                            class="{{ $buttonClasses }}"
                            wire:click="setFilter({{ \Illuminate\Support\Js::from($key) }})"
                            wire:loading.attr="disabled"
                            wire:loading.class="scale-95 opacity-80"
                            wire:target="setFilter"
                            wire:key="dashboard-filter-{{ $key }}"
                        >
                            <span wire:loading.remove wire:target="setFilter">{{ $label }}</span>
                        </x-filament::button>
                    @endforeach
                </div>
            </div>

            @foreach ($groups as $group)
                <x-filament::card class="border border-gray-200/60 bg-white/80 shadow-sm dark:border-gray-700/60 dark:bg-gray-900/60">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full {{ $group['styles']['badge'] }}">
                                    @if ($group['icon'])
                                        <x-dynamic-component :component="$group['icon']" class="h-5 w-5" />
                                    @endif
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $group['title'] }}
                                </h3>
                            </div>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ $group['description'] }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($group['metrics'] as $metric)
                            <div class="relative overflow-hidden rounded-xl border border-gray-200/70 bg-white/90 p-4 shadow-sm transition duration-150 {{ $group['styles']['hover'] }} hover:shadow-md dark:border-gray-700/70 dark:bg-gray-900/70">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            {{ $metric['label'] }}
                                        </p>
                                        <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                                            {{ $metric['value'] }}
                                        </p>
                                    </div>
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $group['styles']['chip'] }}">
                                        @php($icon = $metric['icon'] ?? $group['icon'])
                                        @if ($icon)
                                            <x-dynamic-component :component="$icon" class="h-5 w-5" />
                                        @endif
                                    </div>
                                </div>

                                @if (! empty($metric['change']))
                                    <div class="mt-4 flex items-center gap-2 text-sm {{ $metric['change']['class'] }}">
                                        @if (! empty($metric['change']['icon']))
                                            <x-dynamic-component :component="$metric['change']['icon']" class="h-4 w-4" />
                                        @endif
                                        <span>{{ $metric['change']['description'] }}</span>
                                    </div>
                                @endif

                                @if (! empty($metric['status_color']))
                                    <div class="mt-4 text-sm font-medium {{ $metric['status_color'] === 'danger' ? 'text-danger-600 dark:text-danger-400' : 'text-success-600 dark:text-success-400' }}">
                                        {{ $metric['status_color'] === 'danger' ? 'Перегрузка' : 'Рабочая нагрузка в норме' }}
                                    </div>
                                @endif

                                @if (! empty($metric['helper']))
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $metric['helper'] }}
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-filament::card>
            @endforeach
        </div>
    </x-filament::card>
</x-filament::widget>
