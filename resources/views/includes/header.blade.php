<div class="container_header" id="header">
    <div class="header-auth-wrapper">
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

            <div class="header-actions">
                <a href="{{ url('/') }}" class="header-home-button" title="{{ __('На главную') }}">
                    <span class="sr-only">{{ __('На главную') }}</span>
                    <img src="{{ asset('favicon.png') }}" alt="" class="header-home-icon" aria-hidden="true">
                </a>

                <div class="header-actions-controls">
                    @if($user?->role === 'admin' && Route::has('filament.pages.dashboard'))
                        <a href="{{ route('filament.pages.dashboard') }}"
                           class="btn-filament-primary">
                            Панель админа
                        </a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}" class="header-logout-form">
                        @csrf
                        <button type="submit" class="header-logout-button" title="{{ __('Выйти') }}">
                            <span class="sr-only">{{ __('Выйти') }}</span>
                            <svg class="header-logout-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"
 xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H9m9 0l-3 3m3-3l-3-3" />
                            </svg>
                        </button>
                    </form>

                    <div class="client-account-menu">
                        <button type="button"
                                id="client-user-menu-button"
                                data-dropdown-toggle="client-user-menu"
                                class="client-account-toggle">
                            <span class="sr-only">Открыть меню пользователя</span>

                            <div class="client-avatar">
                                @if ($avatarUrl)
                                    <img src="{{ $avatarUrl }}" alt="{{ $user?->name ?? $user?->email }}" class="client-avatar-image">
                                @elseif ($initials !== '')
                                    <span class="client-avatar-initials">{{ $initials }}</span>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="client-avatar-placeholder">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M4.5 20.25a7.5 7.5 0 0115 0" />
                                    </svg>
                                @endif
                            </div>

                            <div class="client-account-labels">
                                <span class="client-account-label">{{ __('Аккаунт') }}</span>
                                <span class="client-account-name">{{ $displayName !== '' ? $displayName : __('Пользователь') }}</span>
                            </div>

                            <span class="client-account-chevron" aria-hidden="true"></span>
                        </button>

                        <div id="client-user-menu" class="client-account-dropdown hidden">
                            <div class="client-account-dropdown-header">
                                <p class="client-account-dropdown-name">{{ $displayName !== '' ? $displayName : __('Пользователь') }}</p>
                                @if($user?->email)
                                    <p class="client-account-dropdown-email">{{ $user->email }}</p>
                                @endif
                                @if($user?->role)
                                    <span class="client-account-role">{{ $user->role === 'admin' ? 'Администратор' : 'Клиент' }}</span>
                                @endif
                            </div>

                            <div class="client-account-links">
                                <a href="{{ route('profile.show') }}" class="client-account-link">
                                    <span class="client-account-link-icon">
                                        <svg class="client-account-link-icon-svg" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5l-3-3-3 3" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 21h6a2.25 2.25 0 002.25-2.25V7.5L12.75 4.5a2.25 2.25 0 00-1.5 0L6.75 7.5v11.25A2.25 2.25 0 009 21z" />
                                        </svg>
                                    </span>
                                    <span class="client-account-link-text">
                                        <span class="client-account-link-title">Профиль</span>
                                        <span class="client-account-link-description">Просмотреть информацию аккаунта</span>
                                    </span>
                                </a>
                            </div>

                            <div class="client-account-logout">
                                <form method="POST" action="{{ route('logout') }}" class="client-account-logout-form">
                                    @csrf
                                    <button type="submit" class="client-account-logout-button">
                                        <span class="client-account-logout-text">
                                            <span class="client-account-logout-icon">
                                                <svg class="client-account-link-icon-svg" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H9m9 0l-3 3m3-3l-3-3" />
                                                </svg>
                                            </span>
                                            Выйти
                                        </span>
                                        <span class="client-account-logout-arrow" aria-hidden="true"></span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="header-guest-actions">
                @if (Route::has('filament.auth.login'))
                    <a href="{{ route('filament.auth.login') }}"
                       class="btn-filament-primary">
                        Войти
                    </a>
                @endif

                @if (Route::has('filament.auth.register'))
                    <a href="{{ route('filament.auth.register') }}"
                       class="btn-filament-outline">
                        Регистрация
                    </a>
                @endif
            </div>
        @endauth
    </div>
</div>
