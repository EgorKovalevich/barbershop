@extends('layouts.main')

@section('content')
    <div class="profile-dashboard client-appointments-dashboard">
        <div class="profile-hero">
            <div class="wrapper">
                <div class="profile-hero-inner">
                    <div class="profile-hero-text">
                        <span class="profile-hero-label">Ваши визиты</span>
                        <h1 class="profile-hero-title">Мои записи</h1>
                        <p class="profile-hero-description">
                            Следите за запланированными посещениями, редактируйте данные записи и контролируйте информацию о мастере и времени.
                        </p>
                        <div class="profile-hero-meta">
                            <div class="profile-meta-card">
                                <span class="profile-meta-label">Активные записи</span>
                                <span class="profile-meta-value">{{ $events->where('status', \App\Models\Event::STATUS_SCHEDULED)->count() }}</span>
                            </div>
                            <div class="profile-meta-card">
                                <span class="profile-meta-label">Всего записей</span>
                                <span class="profile-meta-value">{{ $events->count() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="wrapper">
            @if (session('clientAppointmentUpdated'))
                <div class="profile-alert profile-alert--success">{{ session('clientAppointmentUpdated') }}</div>
            @endif

            @if ($events->isEmpty())
                <div class="client-appointments-empty profile-card">
                    <h2 class="client-appointments-empty-title">У вас пока нет записей</h2>
                    <p class="client-appointments-empty-text">
                        Запланируйте посещение через форму на главной странице, и ваши записи появятся здесь для быстрого доступа и редактирования.
                    </p>
                    <a href="{{ url('/') }}" class="profile-submit profile-submit--secondary client-appointments-empty-action">
                        Вернуться на главную
                    </a>
                </div>
            @else
                <div class="client-appointments-grid">
                    @foreach ($events as $event)
                        @php
                            $barber = $event->barber;
                            $barberName = $barber
                                ? trim(collect([$barber->surname, $barber->name, $barber->patronymic])->filter()->join(' '))
                                : '—';
                            $status = $event->status;
                            $statusLabel = $statusLabels[$status] ?? $status;
                        @endphp
                        <article class="client-appointment-card profile-card">
                            <header class="client-appointment-card-header">
                                <div>
                                    <span class="client-appointment-card-label">Услуга</span>
                                    <h3 class="client-appointment-card-title">{{ $categoryNames[(int) $event->category] ?? '—' }}</h3>
                                </div>
                                <span class="admin-appointments-status admin-appointments-status--{{ $status }}">
                                    {{ $statusLabel }}
                                </span>
                            </header>

                            <dl class="client-appointment-details">
                                <div class="client-appointment-detail">
                                    <dt>Дата</dt>
                                    <dd>{{ optional($event->start)->translatedFormat('d.m.Y') ?? '—' }}</dd>
                                </div>
                                <div class="client-appointment-detail">
                                    <dt>Время</dt>
                                    <dd>{{ $event->startTime }} – {{ $event->endTime }}</dd>
                                </div>
                                <div class="client-appointment-detail">
                                    <dt>Барбер</dt>
                                    <dd>{{ $barberName }}</dd>
                                </div>
                                <div class="client-appointment-detail">
                                    <dt>Контактный телефон</dt>
                                    <dd>{{ $event->number }}</dd>
                                </div>
                                @if($event->body)
                                    <div class="client-appointment-detail client-appointment-detail--full">
                                        <dt>Комментарий</dt>
                                        <dd>{{ $event->body }}</dd>
                                    </div>
                                @endif
                            </dl>

                            <footer class="client-appointment-card-footer">
                                <a href="{{ route('client.appointments.edit', $event) }}" class="client-appointment-edit-link">
                                    Редактировать запись
                                </a>
                            </footer>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
