@extends('layouts.main')

@section('content')
    <section class="admin-appointments-section">
        <div class="wrapper">
            <div class="admin-appointments-header">
                <h1>Записи клиентов</h1>
                <p>Управляйте актуальными записями клиентов: просматривайте дату, время, выбранные услуги и контактную информацию.</p>
            </div>

            @if (session('appointmentUpdated'))
                <div class="admin-appointments-alert admin-appointments-alert--success">
                    {{ session('appointmentUpdated') }}
                </div>
            @endif

            @if ($events->isEmpty())
                <div class="admin-appointments-empty">
                    <p>Записи не найдены. Клиенты смогут появиться здесь после бронирования времени через форму записи.</p>
                </div>
            @else
                <div class="admin-appointments-table-wrapper">
                    <table class="admin-appointments-table">
                        <thead>
                        <tr>
                            <th>Клиент</th>
                            <th>Контакты</th>
                            <th>Услуга</th>
                            <th>Барбер</th>
                            <th>Дата и время</th>
                            <th>Статус</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($events as $event)
                            <tr>
                                <td data-label="Клиент">
                                    <span class="admin-appointments-cell-title">{{ $event->subject }}</span>
                                    @if($event->body)
                                        <span class="admin-appointments-note">{{ $event->body }}</span>
                                    @endif
                                </td>
                                <td data-label="Контакты">
                                    <span class="admin-appointments-meta">Телефон:</span>
                                    <span>{{ $event->number }}</span>
                                </td>
                                <td data-label="Услуга">
                                    {{ $categoryNames[(int) $event->category] ?? '—' }}
                                </td>
                                <td data-label="Барбер">
                                    @php
                                        $barber = $event->barber;
                                    @endphp
                                    {{ $barber ? trim(collect([$barber->surname, $barber->name, $barber->patronymic])->filter()->join(' ')) : '—' }}
                                </td>
                                <td data-label="Дата и время">
                                    <span class="admin-appointments-meta">{{ optional($event->start)->translatedFormat('d.m.Y') }}</span>
                                    <span>{{ $event->startTime }} – {{ $event->endTime }}</span>
                                </td>
                                <td data-label="Статус">
                                    <span class="admin-appointments-status admin-appointments-status--{{ $event->status }}">
                                        {{ $statuses[$event->status] ?? $event->status }}
                                    </span>
                                </td>
                                <td class="admin-appointments-actions">
                                    <a href="{{ route('admin.appointments.edit', $event) }}" class="btn-filament-outline">Редактировать</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="admin-appointments-pagination">
                    {{ $events->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
