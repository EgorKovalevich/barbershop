@extends('layouts.main')

@section('content')
    <div class="profile-dashboard">
        <div class="profile-hero">
            <div class="wrapper">
                <div class="profile-hero-inner">
                    <div class="profile-hero-avatar">
                        <div class="profile-avatar">
                            @php
                                $initials = collect([$user?->name , $user?->surname])
                                    ->filter()
                                    ->map(fn ($part) => mb_substr($part, 0, 1))
                                    ->join('') ?: ($user?->email ? mb_substr($user->email, 0, 1) : '—');
                            @endphp

                            <span class="profile-avatar-initials">{{ $initials }}</span>
                        </div>
                        <span class="profile-avatar-caption">Мои записи</span>
                    </div>
                    @php
                        $upcomingAppointment = $appointments
                            ->filter(fn ($event) => optional($event->start)->isFuture())
                            ->sortBy('start')
                            ->first();
                    @endphp

                    <div class="profile-hero-text">
                        <span class="profile-hero-label">{{ __('История визитов') }}</span>
                        <h1 class="profile-hero-title">{{ trim(($user?->surname ?? '') . ' ' . ($user?->name ?? '')) ?: $user?->email }}</h1>
                        <p class="profile-hero-description">Управляйте записями, изменяйте время визита и оставайтесь в курсе подробностей приёма.</p>
                        <div class="profile-hero-meta">
                            <div class="profile-meta-card">
                                <span class="profile-meta-label">Всего записей</span>
                                <span class="profile-meta-value">{{ $appointments->count() }}</span>
                            </div>
                            <div class="profile-meta-card">
                                <span class="profile-meta-label">Ближайшая запись</span>
                                <span class="profile-meta-value">
                                    {{ optional($upcomingAppointment?->start)->format('d.m.Y H:i') ?? '—' }}
                                </span>
                            </div>
                            <div class="profile-meta-card">
                                <span class="profile-meta-label">Последнее обновление</span>
                                <span class="profile-meta-value">{{ optional($user?->updated_at)->diffForHumans() ?? '—' }}</span>
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

            <div class="profile-content">
                <div class="profile-column profile-column--details">
                    <section class="profile-card profile-card--details">
                        <h2 class="profile-card-title">Записи к мастерам</h2>
                        <p class="profile-card-subtitle">Подробности каждого визита: дата, время, мастер и выбранная услуга.</p>

                        <div class="profile-appointments">
                            @forelse ($appointments as $appointment)
                                @php
                                    $barberUser = $appointment->barber;
                                    $barberName = $barberUser
                                        ? trim(collect([$barberUser->surname ?? null, $barberUser->name ?? null])->filter()->join(' '))
                                        : null;

                                    if (blank($barberName) && $barberUser?->name) {
                                        $barberName = $barberUser->name;
                                    }
                                @endphp
                                <article class="profile-appointment">
                                    <div class="profile-appointment-header">
                                        <div>
                                            <span class="profile-appointment-date">{{ optional($appointment->start)->translatedFormat('d F Y') }}</span>
                                            <span class="profile-appointment-time">{{ optional($appointment->start)->format('H:i') }} – {{ optional($appointment->end)->format('H:i') }}</span>
                                        </div>
                                        <span class="profile-appointment-status">{{ App\Models\Event::statusOptions()[$appointment->status] ?? 'Без статуса' }}</span>
                                    </div>

                                    <dl class="profile-appointment-details">
                                        <div class="profile-appointment-detail">
                                            <dt>Барбер</dt>
                                            <dd>{{ $barberName ?? '—' }}</dd>
                                        </div>
                                        <div class="profile-appointment-detail">
                                            <dt>Услуга</dt>
                                            <dd>{{ optional($categories->get($appointment->category))->name ?? '—' }}</dd>
                                        </div>
                                        <div class="profile-appointment-detail">
                                            <dt>Контактный телефон</dt>
                                            <dd>{{ $appointment->number ?? '—' }}</dd>
                                        </div>
                                        <div class="profile-appointment-detail">
                                            <dt>Комментарий</dt>
                                            <dd>{{ $appointment->body ?? '—' }}</dd>
                                        </div>
                                    </dl>

                                    <div class="profile-appointment-actions">
                                        <a href="{{ route('appointments.edit', $appointment) }}" class="profile-appointment-edit">Редактировать</a>
                                    </div>
                                </article>
                            @empty
                                <p class="profile-empty">У вас пока нет активных записей. Оформите первую запись на главной странице.</p>
                            @endforelse
                        </div>
                    </section>
                </div>

                <div class="profile-column profile-column--forms">
                    <section class="profile-card profile-card--activity">
                        <h2 class="profile-card-title">Как изменить запись</h2>
                        <p class="profile-card-subtitle">Чтобы перенести или обновить визит, используйте кнопку «Редактировать» возле нужной записи.</p>

                        <ul class="profile-tips-list">
                            <li>Выберите новую дату и время в доступных интервалах.</li>
                            <li>Укажите комментарий для мастера, если есть особые пожелания.</li>
                            <li>Мы сообщим мастеру о внесённых изменениях автоматически.</li>
                        </ul>
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
