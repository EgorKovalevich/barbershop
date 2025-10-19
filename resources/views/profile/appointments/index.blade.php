@extends('layouts.main')

@section('content')
    <div class="profile-dashboard">
        <div class="profile-hero">
            <div class="wrapper">
                <div class="profile-hero-inner">
                    <div class="profile-hero-avatar">
                        <div class="profile-avatar">
                            @php
                                $initials = collect([$user?->name, $user?->surname])
                                    ->filter()
                                    ->map(fn ($part) => mb_substr($part, 0, 1))
                                    ->join('') ?: ($user?->email ? mb_substr($user->email, 0, 1) : '—');
                            @endphp

                            <span class="profile-avatar-initials">{{ $initials }}</span>
                        </div>
                        <span class="profile-avatar-caption">Ваши записи</span>
                    </div>
                    <div class="profile-hero-text">
                        <span class="profile-hero-label">{{ __('Панель клиента') }}</span>
                        <h1 class="profile-hero-title">{{ __('Мои записи к барберам') }}</h1>
                        <p class="profile-hero-description">Управляйте предстоящими и прошедшими визитами, изменяйте данные записи и сохраняйте историю посещений.</p>
                        <div class="profile-hero-meta">
                            <div class="profile-meta-card">
                                <span class="profile-meta-label">Всего записей</span>
                                <span class="profile-meta-value">{{ $appointments->count() }}</span>
                            </div>
                            <div class="profile-meta-card">
                                <span class="profile-meta-label">Ближайший визит</span>
                                <span class="profile-meta-value">
                                    @php
                                        $upcoming = $appointments->first(fn ($appointment) => optional($appointment->start)->isFuture());
                                    @endphp
                                    {{ $upcoming?->start?->translatedFormat('d.m.Y H:i') ?? '—' }}
                                </span>
                            </div>
                            <div class="profile-meta-card">
                                <span class="profile-meta-label">Статус ближайшей записи</span>
                                <span class="profile-meta-value">
                                    @if ($upcoming)
                                        {{ $statusOptions[$upcoming->status] ?? $upcoming->status }}
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="wrapper">
            @if (session('appointmentUpdated'))
                <div class="profile-alert profile-alert--success">{{ session('appointmentUpdated') }}</div>
            @endif

            <div class="profile-content profile-content--single">
                <div class="profile-column profile-column--details">
                    <section class="profile-card profile-card--details">
                        <h2 class="profile-card-title">История и предстоящие визиты</h2>
                        <p class="profile-card-subtitle">Каждая карточка содержит подробности записи: дату, время, мастера и контактные данные.</p>

                        @if ($appointments->isEmpty())
                            <div class="profile-appointments-empty">
                                <p class="profile-appointments-empty-title">У вас пока нет записей</p>
                                <p class="profile-appointments-empty-description">Запланируйте визит на главной странице, чтобы увидеть его здесь.</p>
                            </div>
                        @else
                            <ul class="profile-appointments-list">
                                @foreach ($appointments as $appointment)
                                    <li class="profile-appointment">
                                        <div class="profile-appointment-header">
                                            <div class="profile-appointment-datetime">
                                                <span class="profile-appointment-date">{{ optional($appointment->start)->translatedFormat('d.m.Y') ?? '—' }}</span>
                                                <span class="profile-appointment-time">{{ optional($appointment->start)->format('H:i') ?? '—' }}</span>
                                            </div>
                                            <span class="profile-appointment-status profile-appointment-status--{{ $appointment->status }}">
                                                {{ $statusOptions[$appointment->status] ?? $appointment->status }}
                                            </span>
                                        </div>

                                        <div class="profile-appointment-meta">
                                            <div class="profile-appointment-meta-item">
                                                <span class="profile-appointment-meta-label">Услуга</span>
                                                <span class="profile-appointment-meta-value">{{ $appointment->subject }}</span>
                                            </div>
                                            <div class="profile-appointment-meta-item">
                                                <span class="profile-appointment-meta-label">Барбер</span>
                                                <span class="profile-appointment-meta-value">
                                                    @if ($appointment->barber)
                                                        {{ trim(($appointment->barber->surname ?? '') . ' ' . ($appointment->barber->name ?? '')) ?: $appointment->barber->email }}
                                                    @else
                                                        —
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="profile-appointment-meta-item">
                                                <span class="profile-appointment-meta-label">Категория</span>
                                                <span class="profile-appointment-meta-value">
                                                    {{ $categoryNames[$appointment->category] ?? $appointment->category ?? '—' }}
                                                </span>
                                            </div>
                                            <div class="profile-appointment-meta-item">
                                                <span class="profile-appointment-meta-label">Телефон</span>
                                                <span class="profile-appointment-meta-value">{{ $appointment->number }}</span>
                                            </div>
                                            @if ($appointment->body)
                                                <div class="profile-appointment-meta-item">
                                                    <span class="profile-appointment-meta-label">Комментарий</span>
                                                    <span class="profile-appointment-meta-value">{{ $appointment->body }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="profile-appointment-actions">
                                            <a href="{{ route('profile.appointments.edit', $appointment) }}" class="profile-submit profile-submit--secondary profile-appointment-edit">
                                                {{ __('Редактировать запись') }}
                                            </a>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
