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
                            class="group flex items-center gap-3 rounded-full bg-white/90 px-3 py-2 text-left text-sm font-medium text-slate-700 shadow-lg ring-1 ring-white/70 transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-300 focus:ring-offset-2">
                        <span class="sr-only">Открыть меню пользователя</span>

                        <div class="relative flex h-12 w-12 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-yellow-400 via-amber-500 to-orange-600 text-white shadow-inner">
                            <div class="absolute inset-0 rounded-full border border-white/30"></div>
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

                        <div class="hidden text-left sm:block">
                            <span class="block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Аккаунт') }}</span>
                            <span class="block text-sm font-semibold text-slate-900">{{ $displayName !== '' ? $displayName : __('Пользователь') }}</span>
                        </div>

                        <svg class="h-4 w-4 text-slate-400 transition group-hover:text-slate-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div id="client-user-menu"
                         class="absolute right-0 z-50 mt-3 hidden w-72 origin-top-right overflow-hidden rounded-3xl border border-slate-200/80 bg-white/95 text-sm text-slate-600 shadow-2xl backdrop-blur">
                        <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 px-6 py-5 text-white">
                            <p class="text-base font-semibold">{{ $displayName !== '' ? $displayName : __('Пользователь') }}</p>
                            @if($user?->email)
                                <p class="mt-1 text-sm text-white/70">{{ $user->email }}</p>
                            @endif
                            @if($user?->role)
                                <span class="mt-3 inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium uppercase tracking-wide text-white/80">
                                    {{ $user->role === 'admin' ? 'Администратор' : 'Клиент' }}
                                </span>
                            @endif
                        </div>

                        <div class="space-y-1 bg-white/90 px-4 py-4">
                            <a href="{{ route('profile.show') }}"
                               class="group flex items-center gap-3 rounded-2xl px-4 py-3 font-semibold text-slate-700 transition hover:bg-slate-100">
                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900/90 text-white shadow">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5l-3-3-3 3" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 21h6a2.25 2.25 0 002.25-2.25V7.5L12.75 4.5a2.25 2.25 0 00-1.5 0L6.75 7.5v11.25A2.25 2.25 0 009 21z" />
                                    </svg>
                                </span>
                                <div class="flex flex-col">
                                    <span class="text-sm">Профиль</span>
                                    <span class="text-xs font-normal text-slate-400">Просмотреть информацию аккаунта</span>
                                </div>
                            </a>
                        </div>

                        <div class="bg-slate-50/90 px-4 py-4">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="group flex w-full items-center justify-between rounded-2xl bg-red-50 px-4 py-3 text-left text-sm font-semibold text-red-600 transition hover:bg-red-100">
                                    <span class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-500/10 text-red-600">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H9m9 0l-3 3m3-3l-3-3" />
                                            </svg>
                                        </span>
                                        Выйти
                                    </span>
                                    <svg class="h-4 w-4 text-red-500 transition group-hover:translate-x-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h8.69l-2.22-2.22a.75.75 0 011.06-1.06l3.5 3.5a.75.75 0 010 1.06l-3.5 3.5a.75.75 0 01-1.06-1.06l2.22-2.22H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                                    </svg>
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

