@php
    $barberUser = $appointment->barber;
    $barberName = $barberUser
        ? trim(collect([$barberUser->surname ?? null, $barberUser->name ?? null])->filter()->join(' '))
        : null;

    if (blank($barberName) && $barberUser?->name) {
        $barberName = $barberUser->name;
    }

    $statusDisplay = $statusLabel ?? (\App\Models\Event::statusOptions()[$appointment->status] ?? 'Без статуса');
    $categoryDisplay = $categoryName ?? '—';
@endphp

<article class="profile-appointment">
    <div class="profile-appointment-header">
        <div>
            <span class="profile-appointment-date">{{ optional($appointment->start)->translatedFormat('d F Y') }}</span>
            <span class="profile-appointment-time">{{ optional($appointment->start)->format('H:i') }} – {{ optional($appointment->end)->format('H:i') }}</span>
        </div>
        <span class="profile-appointment-status">{{ $statusDisplay }}</span>
    </div>

    <dl class="profile-appointment-details">
        <div class="profile-appointment-detail">
            <dt>Барбер</dt>
            <dd>{{ $barberName ?? '—' }}</dd>
        </div>
        <div class="profile-appointment-detail">
            <dt>Услуга</dt>
            <dd>{{ $categoryDisplay }}</dd>
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

    @if (!empty($editUrl))
        <div class="profile-appointment-actions">
            <a href="{{ $editUrl }}" class="profile-appointment-edit">Редактировать</a>
        </div>
    @endif
</article>
