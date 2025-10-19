<div class="container_header" id="header">
    <div class="flex flex-wrap justify-end gap-4">
        @if (Route::has('filament.auth.login'))
            <a href="{{ route('filament.auth.login') }}"
               class="rounded-lg bg-yellow-500 px-6 py-3 text-sm font-semibold uppercase text-white transition hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-300 focus:ring-offset-2">
                Войти в панель
            </a>
        @endif

        @if (Route::has('register'))
            <a href="{{ route('register') }}"
               class="rounded-lg border border-yellow-500 px-6 py-3 text-sm font-semibold uppercase text-yellow-500 transition hover:bg-yellow-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-yellow-300 focus:ring-offset-2">
                Регистрация
            </a>
        @endif
    </div>
</div>

