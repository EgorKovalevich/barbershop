@pushOnce('styles')
    <link rel="stylesheet" href="{{ asset('css/filament-dashboard.css') }}">
@endPushOnce

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
                                'transition focus-visible:ring-2 focus-visible:ring-offset-2',
                                'ring-2 ring-primary-500 ring-offset-1 dark:ring-offset-gray-900' => $currentFilter === $key,
                                'ring-0' => $currentFilter !== $key,
                            ]);
                        @endphp
                        <x-filament::button
                            type="button"
                            size="sm"
                            :color="$currentFilter === $key ? 'primary' : 'gray'"
                            class="{{ $buttonClasses }} dashboard-period-button"
                            wire:click="setFilter({{ \Illuminate\Support\Js::from($key) }})"
                            wire:loading.attr="disabled"
                            wire:target="setFilter"
                            wire:key="dashboard-filter-{{ $key }}"
                        >
                            <span class="dashboard-period-button__label" wire:loading.remove wire:target="setFilter">{{ $label }}</span>
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

                    @if (! empty($group['sections']))
                        <div class="mt-6 space-y-6">
                            @foreach ($group['sections'] as $section)
                                <div class="flex flex-col gap-3">
                                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                        <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                                            {{ $section['title'] }}
                                        </h4>
                                        @if (! empty($section['description']))
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $section['description'] }}
                                            </p>
                                        @endif
                                    </div>
                                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                        @foreach ($section['metrics'] as $metric)
                                            @include('filament.widgets.partials.metric-card', [
                                                'metric' => $metric,
                                                'styles' => $group['styles'],
                                                'defaultIcon' => $group['icon'],
                                            ])
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($group['metrics'] as $metric)
                                @include('filament.widgets.partials.metric-card', [
                                    'metric' => $metric,
                                    'styles' => $group['styles'],
                                    'defaultIcon' => $group['icon'],
                                ])
                            @endforeach
                        </div>
                    @endif
                </x-filament::card>
            @endforeach
        </div>
    </x-filament::card>
</x-filament::widget>
