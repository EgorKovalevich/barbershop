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

                                $barberUser = $appointment->barber;
                                $barberName = $barberUser
                                    ? trim(collect([$barberUser->surname ?? null, $barberUser->name ?? null])->filter()->join(' '))
                                    : null;

                                if (blank($barberName) && $barberUser?->name) {
                                    $barberName = $barberUser->name;
                                }
                            @endphp

                            <span class="profile-avatar-initials">{{ $initials }}</span>
                        </div>
                        <span class="profile-avatar-caption">Редактирование записи</span>
                    </div>
                    <div class="profile-hero-text">
                        <span class="profile-hero-label">{{ __('Обновите детали визита') }}</span>
                        <h1 class="profile-hero-title">{{ $appointment->subject }}</h1>
                        <p class="profile-hero-description">Измените дату, время и мастера. Мы проверим доступность и подтвердим обновления.</p>
                        <div class="profile-hero-meta">
                            <div class="profile-meta-card">
                                <span class="profile-meta-label">Текущая дата</span>
                                <span class="profile-meta-value">{{ optional($appointment->start)->format('d.m.Y') ?? '—' }}</span>
                            </div>
                            <div class="profile-meta-card">
                                <span class="profile-meta-label">Начало визита</span>
                                <span class="profile-meta-value">{{ optional($appointment->start)->format('H:i') ?? '—' }}</span>
                            </div>
                            <div class="profile-meta-card">
                                <span class="profile-meta-label">Барбер</span>
                                <span class="profile-meta-value">{{ $barberName ?? '—' }}</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="wrapper">
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

            <div class="profile-content">
                <div class="profile-column profile-column--details">
                    <section class="profile-card profile-card--details">
                        <h2 class="profile-card-title">Текущая запись</h2>
                        <p class="profile-card-subtitle">Проверьте информацию о визите перед обновлением.</p>

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
                        </article>
                    </section>

                    <section class="profile-card profile-card--activity">
                        <h2 class="profile-card-title">Советы по изменению записи</h2>
                        <p class="profile-card-subtitle">Проверьте расписание барбера и выбирайте время, свободное от других визитов.</p>

                        <ul class="profile-tips-list">
                            <li>Если нужное время занято, попробуйте выбрать другого барбера.</li>
                            <li>Избегайте выбора прошедших дат — система их не примет.</li>
                            <li>Сообщите мастеру о пожеланиях в комментарии.</li>
                        </ul>
                    </section>
                </div>

                <div class="profile-column profile-column--forms">
                    <section class="profile-card profile-card--form">
                        <h2 class="profile-card-title">Обновление записи</h2>
                        <p class="profile-card-subtitle">Выберите новые параметры визита и сохраните изменения.</p>

                        <form method="POST" action="{{ route('appointments.update', $appointment) }}" class="profile-form">
                            @csrf
                            @method('PUT')

                            <div class="profile-form-group">
                                <label for="subject" class="profile-form-label">Имя клиента</label>
                                <input id="subject" type="text" name="subject" value="{{ old('subject', $appointment->subject) }}" class="profile-input @error('subject') profile-input--error @enderror" required>
                                @error('subject')
                                    <p class="profile-input-message profile-input-message--error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="profile-form-group">
                                <label for="number" class="profile-form-label">Контактный телефон</label>
                                <input id="number" type="text" name="number" value="{{ old('number', $appointment->number) }}" class="profile-input @error('number') profile-input--error @enderror" required>
                                @error('number')
                                    <p class="profile-input-message profile-input-message--error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="profile-form-group">
                                <label for="category" class="profile-form-label">Услуга</label>
                                <select id="category" name="category" class="profile-input @error('category') profile-input--error @enderror" required>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected(old('category', $appointment->category) == $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('category')
                                    <p class="profile-input-message profile-input-message--error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="profile-form-group">
                                <label for="barber" class="profile-form-label">Барбер</label>
                                <select id="barber" name="barber" class="profile-input @error('barber') profile-input--error @enderror" required>
                                    @foreach ($barbers as $barber)
                                        @php
                                            $barberProfile = $barber->user;
                                            $barberOptionName = $barberProfile
                                                ? trim(collect([$barberProfile->surname ?? null, $barberProfile->name ?? null])->filter()->join(' '))
                                                : null;

                                            if (blank($barberOptionName) && $barberProfile?->name) {
                                                $barberOptionName = $barberProfile->name;
                                            }
                                        @endphp
                                        <option value="{{ $barber->user_id }}" @selected(old('barber', $appointment->barber_id) == $barber->user_id)>{{ $barberOptionName ?? 'Без имени' }}</option>
                                    @endforeach
                                </select>
                                @error('barber')
                                    <p class="profile-input-message profile-input-message--error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="profile-form-grid">
                                <div class="profile-form-group">
                                    <label for="start" class="profile-form-label">Дата визита</label>
                                    <input id="start" type="date" name="start" value="{{ old('start', optional($appointment->start)->format('Y-m-d')) }}" class="profile-input @error('start') profile-input--error @enderror" required>
                                    @error('start')
                                        <p class="profile-input-message profile-input-message--error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="profile-form-group">
                                    <label for="startTime" class="profile-form-label">Время визита</label>
                                    <input id="startTime" type="time" name="startTime" value="{{ old('startTime', optional($appointment->start)->format('H:i')) }}" class="profile-input @error('startTime') profile-input--error @enderror" required>
                                    @error('startTime')
                                        <p class="profile-input-message profile-input-message--error">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="profile-form-group">
                                <label for="body" class="profile-form-label">Комментарий для мастера</label>
                                <textarea id="body" name="body" rows="4" class="profile-input profile-textarea @error('body') profile-input--error @enderror">{{ old('body', $appointment->body) }}</textarea>
                                @error('body')
                                    <p class="profile-input-message profile-input-message--error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="profile-form-actions">
                                <a href="{{ route('appointments.index') }}" class="profile-submit profile-submit--secondary">Отменить</a>
                                <button type="submit" class="profile-submit">Сохранить изменения</button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
