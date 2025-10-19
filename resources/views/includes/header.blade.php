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

            <div class="flex items-center gap-4">
                @if($user?->role === 'admin' && Route::has('filament.pages.dashboard'))
                    <a href="{{ route('filament.pages.dashboard') }}"
                       class="rounded-lg bg-yellow-500 px-6 py-3 text-sm font-semibold uppercase text-white transition hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-300 focus:ring-offset-2">
                        Панель админа
                    </a>
                @endif

                <div class="relative">
                    <button type="button"
                            id="client-user-menu-button"
                            data-dropdown-toggle="client-user-menu"
                            class="flex items-center gap-3 rounded-full border border-gray-200 bg-white p-2 text-sm font-medium text-gray-700 shadow-sm transition hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-300 focus:ring-offset-2">
                        <span class="sr-only">Открыть меню пользователя</span>

                        <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-full bg-gray-900 text-white">
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
                        </div>

                        <svg class="h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div id="client-user-menu"
                         class="z-50 hidden w-56 divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-100 bg-white text-gray-700 shadow-xl">
                        <div class="px-4 py-3">
                            <p class="text-sm font-semibold text-gray-900">{{ $displayName !== '' ? $displayName : __('Пользователь') }}</p>
                            @if($user?->email)
                                <p class="truncate text-sm text-gray-500">{{ $user->email }}</p>
                            @endif
                        </div>

                        <ul class="py-2 text-sm" aria-labelledby="client-user-menu-button">
                            <li>
                                <a href="{{ route('profile.show') }}" class="block px-4 py-2 transition hover:bg-gray-50">Профиль</a>
                            </li>
                        </ul>

                        <div class="py-2">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm font-medium text-red-600 transition hover:bg-red-50">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H9m9 0l-3 3m3-3l-3-3" />
                                    </svg>
                                    Выйти
                                </button>
                            </form>
                        </div>
                    </div>
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

