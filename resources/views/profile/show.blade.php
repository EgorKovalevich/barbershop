@extends('layouts.main')

@section('content')
    <div class="profile-dashboard">
        <div class="profile-hero">
            <div class="wrapper">
                <div class="profile-hero-inner">
                    <div class="profile-hero-text">
                        <span class="profile-hero-label">{{ __('Панель профиля') }}</span>
                        <h1 class="profile-hero-title">{{ trim(($user?->surname ?? '') . ' ' . ($user?->name ?? '')) ?: $user?->email }}</h1>
                        <p class="profile-hero-description">Управляйте личными данными, контактами и безопасностью аккаунта в едином окне, оформленном в стиле Filament.</p>
                        <div class="profile-hero-meta">
                            <div class="profile-meta-card">
                                <span class="profile-meta-label">Роль</span>
                                <span class="profile-meta-value">{{ $user?->role === 'admin' ? 'Администратор' : 'Клиент' }}</span>
                            </div>
                            <div class="profile-meta-card">
                                <span class="profile-meta-label">На платформе с</span>
                                <span class="profile-meta-value">{{ optional($user?->created_at)->format('d.m.Y') ?? '—' }}</span>
                            </div>
                            <div class="profile-meta-card">
                                <span class="profile-meta-label">Последнее обновление</span>
                                <span class="profile-meta-value">{{ optional($user?->updated_at)->diffForHumans() ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="profile-hero-avatar">
                        <div class="profile-avatar">
                            @php
                                $initials = collect([$user?->surname, $user?->name])
                                    ->filter()
                                    ->map(fn ($part) => mb_substr($part, 0, 1))
                                    ->join('') ?: ($user?->email ? mb_substr($user->email, 0, 1) : '—');
                            @endphp

                            <span class="profile-avatar-initials">{{ $initials }}</span>
                        </div>
                        <span class="profile-avatar-caption">Персональный аккаунт</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="wrapper">
            @if (session('profileUpdated'))
                <div class="profile-alert profile-alert--success">{{ session('profileUpdated') }}</div>
            @endif

            @if (session('passwordUpdated'))
                <div class="profile-alert profile-alert--success">{{ session('passwordUpdated') }}</div>
            @endif

            @if ($errors->any())
                <div class="profile-alert profile-alert--error">
                    <p class="profile-alert-title">Проверьте введённые данные:</p>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($errors->passwordUpdate->any())
                <div class="profile-alert profile-alert--error">
                    <p class="profile-alert-title">Не удалось сменить пароль:</p>
                    <ul>
                        @foreach ($errors->passwordUpdate->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="profile-content">
                <div class="profile-column profile-column--details">
                    <section class="profile-card profile-card--details">
                        <h2 class="profile-card-title">Основная информация</h2>
                        <p class="profile-card-subtitle">Все ключевые данные собраны в карточках для быстрого просмотра.</p>

                        <div class="profile-details-grid">
                            <div class="profile-detail">
                                <span class="profile-detail-label">Фамилия</span>
                                <span class="profile-detail-value">{{ $user?->surname ?? '—' }}</span>
                            </div>
                            <div class="profile-detail">
                                <span class="profile-detail-label">Имя</span>
                                <span class="profile-detail-value">{{ $user?->name ?? '—' }}</span>
                            </div>
                            <div class="profile-detail">
                                <span class="profile-detail-label">Отчество</span>
                                <span class="profile-detail-value">{{ $user?->patronymic ?? '—' }}</span>
                            </div>
                            <div class="profile-detail">
                                <span class="profile-detail-label">Email</span>
                                <span class="profile-detail-value">{{ $user?->email ?? '—' }}</span>
                            </div>
                            <div class="profile-detail">
                                <span class="profile-detail-label">Телефон</span>
                                <span class="profile-detail-value">{{ $user?->number ?? '—' }}</span>
                            </div>
                            <div class="profile-detail">
                                <span class="profile-detail-label">Последний вход</span>
                                <span class="profile-detail-value">{{ optional($user?->last_login_at)->format('d.m.Y H:i') ?? '—' }}</span>
                            </div>
                        </div>
                    </section>

                    <section class="profile-card profile-card--activity">
                        <h2 class="profile-card-title">Активность аккаунта</h2>
                        <p class="profile-card-subtitle">Статистика помогает следить за актуальностью контактной информации и безопасности.</p>

                        <div class="profile-stats">
                            <div class="profile-stat">
                                <span class="profile-stat-value">{{ $user?->email_verified_at ? 'Подтверждён' : 'Не подтверждён' }}</span>
                                <span class="profile-stat-label">Статус email</span>
                            </div>
                            <div class="profile-stat">
                                <span class="profile-stat-value">{{ optional($user?->updated_at)->format('d.m.Y H:i') ?? '—' }}</span>
                                <span class="profile-stat-label">Обновление профиля</span>
                            </div>
                            <div class="profile-stat">
                                <span class="profile-stat-value">{{ ($user && method_exists($user, 'appointments')) ? $user->appointments()->count() : '—' }}</span>
                                <span class="profile-stat-label">Записей к мастерам</span>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="profile-column profile-column--forms">
                    <section class="profile-card profile-card--form">
                        <h2 class="profile-card-title">Редактирование профиля</h2>
                        <p class="profile-card-subtitle">Измените персональные и контактные данные. Все поля сохраняются мгновенно и безопасно.</p>

                        <form method="POST" action="{{ route('profile.update') }}" class="profile-form">
                            @csrf
                            @method('PUT')

                            <div class="profile-form-group">
                                <label for="surname" class="profile-form-label">Фамилия</label>
                                <input id="surname" type="text" name="surname" value="{{ old('surname', $user?->surname) }}" class="profile-input" required>
                            </div>

                            <div class="profile-form-group">
                                <label for="name" class="profile-form-label">Имя</label>
                                <input id="name" type="text" name="name" value="{{ old('name', $user?->name) }}" class="profile-input" required>
                            </div>

                            <div class="profile-form-group">
                                <label for="patronymic" class="profile-form-label">Отчество</label>
                                <input id="patronymic" type="text" name="patronymic" value="{{ old('patronymic', $user?->patronymic) }}" class="profile-input">
                            </div>

                            <div class="profile-form-group">
                                <label for="email" class="profile-form-label">Email</label>
                                <input id="email" type="email" name="email" value="{{ old('email', $user?->email) }}" class="profile-input" required>
                            </div>

                            <div class="profile-form-group">
                                <label for="number" class="profile-form-label">Телефон</label>
                                <input id="number" type="text" name="number" value="{{ old('number', $user?->number) }}" class="profile-input" placeholder="+7 (___) ___-__-__">
                            </div>

                            <button type="submit" class="profile-submit">Сохранить изменения</button>
                        </form>
                    </section>

                    <section class="profile-card profile-card--form">
                        <h2 class="profile-card-title">Смена пароля</h2>
                        <p class="profile-card-subtitle">Используйте сложный пароль для защиты аккаунта. Новые данные вступают в силу сразу.</p>

                        <form method="POST" action="{{ route('profile.password.update') }}" class="profile-form">
                            @csrf
                            @method('PUT')

                            <div class="profile-form-group">
                                <label for="current_password" class="profile-form-label">Текущий пароль</label>
                                <input id="current_password" type="password" name="current_password" class="profile-input" required autocomplete="current-password">
                            </div>

                            <div class="profile-form-group">
                                <label for="password" class="profile-form-label">Новый пароль</label>
                                <input id="password" type="password" name="password" class="profile-input" required autocomplete="new-password">
                            </div>

                            <div class="profile-form-group">
                                <label for="password_confirmation" class="profile-form-label">Подтверждение пароля</label>
                                <input id="password_confirmation" type="password" name="password_confirmation" class="profile-input" required autocomplete="new-password">
                            </div>

                            <button type="submit" class="profile-submit profile-submit--secondary">Обновить пароль</button>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
