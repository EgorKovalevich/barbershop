<div class="container_header" id="header">
    <div class="flex flex-wrap items-center justify-end gap-4">
        @auth
            @php
                $user = auth()->user();
                $avatarUrl = null;
                $initials = '';

                if ($user) {
                    $providerClass = config('filament.default_avatar_provider');

                    if (is_string($providerClass) && class_exists($providerClass)) {
                        $provider = app($providerClass);

                        if (method_exists($provider, 'get')) {
                            $avatarUrl = $provider->get($user);
                        }
                    }

                    $displayName = trim($user->name ?? $user->email ?? '');

                    if ($displayName === '' && filled($user->surname ?? null)) {
                        $displayName = trim(($user->surname ?? '') . ' ' . ($user->name ?? ''));
                    }

                    if ($displayName !== '') {
                        $initials = collect(preg_split('/\s+/u', $displayName, -1, PREG_SPLIT_NO_EMPTY))
                            ->map(fn ($segment) => mb_substr($segment, 0, 1))
                            ->join('');
                    }

                    if ($initials === '' && isset($user->email)) {
                        $initials = mb_substr($user->email, 0, 1);
                    }
                }
            @endphp

            <div class="relative flex items-center gap-4">
                @if($user?->role === 'admin' && Route::has('filament.pages.dashboard'))
                    <a href="{{ route('filament.pages.dashboard') }}"
                       class="rounded-lg bg-yellow-500 px-6 py-3 text-sm font-semibold uppercase text-white transition hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-300 focus:ring-offset-2">
                        Панель админа
                    </a>
                @endif

                <button type="button" id="user-menu-button" data-dropdown-toggle="user-menu-dropdown" data-dropdown-placement="bottom-end" aria-expanded="false"
                        class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-full bg-gray-900 text-white focus:outline-none focus:ring-2 focus:ring-yellow-300 focus:ring-offset-2">
                    <span class="sr-only">Открыть меню пользователя</span>
                    @if ($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="{{ $user?->name ?? $user?->email }}" class="h-full w-full object-cover">
                    @elseif ($initials !== '')
                        <span class="text-lg font-semibold uppercase">{{ $initials }}</span>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M4.5 20.25a7.5 7.5 0 0115 0" />
                        </svg>
                    @endif
                </button>

                <div id="user-menu-dropdown" class="absolute right-0 top-full z-50 mt-3 hidden w-56 origin-top-right rounded-xl border border-gray-100 bg-white p-2 text-sm shadow-lg focus:outline-none">
                    @if($user?->role !== 'admin')
                        <a href="{{ route('profile.show') }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 text-gray-700 transition hover:bg-gray-100">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M4.5 20.25a7.5 7.5 0 0115 0" />
                            </svg>
                            Профиль
                        </a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-gray-700 transition hover:bg-gray-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H9m0 0l3-3m-3 3l3 3" />
                            </svg>
                            Выйти
                        </button>
                    </form>
                </div>
            </div>
        @else
            @if (Route::has('filament.auth.login'))
                <a href="{{ route('filament.auth.login') }}"
                   class="rounded-lg bg-yellow-500 px-6 py-3 text-sm font-semibold uppercase text-white transition hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-300 focus:ring-offset-2">
                    Войти
                </a>
            @endif

            @if (Route::has('filament.auth.register'))
                <a href="{{ route('filament.auth.register') }}"
                   class="rounded-lg border border-yellow-500 px-6 py-3 text-sm font-semibold uppercase text-yellow-500 transition hover:bg-yellow-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-yellow-300 focus:ring-offset-2">
                    Регистрация
                </a>
            @endif
        @endauth
    </div>
</div>

