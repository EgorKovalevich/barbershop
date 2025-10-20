@php
    $hoverClass = $styles['hover'] ?? '';
    $chipClass = $styles['chip'] ?? '';
    $iconName = $metric['icon'] ?? $defaultIcon ?? null;
@endphp

<div class="relative overflow-hidden rounded-xl border border-gray-200/70 bg-white/90 p-4 shadow-sm transition duration-150 {{ $hoverClass }} hover:shadow-md dark:border-gray-700/70 dark:bg-gray-900/70">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ $metric['label'] ?? '' }}
            </p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                {{ $metric['value'] ?? '—' }}
            </p>
        </div>
        @if ($iconName)
            <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $chipClass }}">
                <x-dynamic-component :component="$iconName" class="h-5 w-5" />
            </div>
        @endif
    </div>

    @if (! empty($metric['change']))
        <div class="mt-4 flex items-center gap-2 text-sm {{ $metric['change']['class'] ?? '' }}">
            @if (! empty($metric['change']['icon']))
                <x-dynamic-component :component="$metric['change']['icon']" class="h-4 w-4" />
            @endif
            <span>{{ $metric['change']['description'] ?? '' }}</span>
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
