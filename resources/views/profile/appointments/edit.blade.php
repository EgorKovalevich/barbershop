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
                        <span class="profile-avatar-caption">Редактирование</span>
                    </div>
                    <div class="profile-hero-text">
                        <span class="profile-hero-label">{{ __('Управление записью') }}</span>
                        <h1 class="profile-hero-title">{{ __('Изменение записи к барберу') }}</h1>
                        <p class="profile-hero-description">Обновите дату, время и дополнительные детали записи. Изменения вступают в силу сразу после сохранения.</p>
                        <div class="profile-hero-meta">
                            <div class="profile-meta-card">
                                <span class="profile-meta-label">Текущая дата</span>
                                <span class="profile-meta-value">{{ optional($appointment->start)->translatedFormat('d.m.Y') ?? '—' }}</span>
                            </div>
                            <div class="profile-meta-card">
                                <span class="profile-meta-label">Текущее время</span>
                                <span class="profile-meta-value">{{ optional($appointment->start)->format('H:i') ?? '—' }}</span>
                            </div>
                            <div class="profile-meta-card">
                                <span class="profile-meta-label">Статус</span>
                                <span class="profile-meta-value">{{ $statusOptions[$appointment->status] ?? $appointment->status }}</span>
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

            <div class="profile-content profile-content--single">
                <div class="profile-column profile-column--forms">
                    <section class="profile-card profile-card--form">
                        <h2 class="profile-card-title">Данные записи</h2>
                        <p class="profile-card-subtitle">Измените выбранные параметры и сохраните, чтобы обновить визит.</p>

                        <form method="POST" action="{{ route('profile.appointments.update', $appointment) }}" class="profile-form">
                            @csrf
                            @method('PUT')

                            <div class="profile-form-group">
                                <label for="subject" class="profile-form-label">Название услуги</label>
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
                                <label for="category" class="profile-form-label">Категория услуги</label>
                                <select id="category" name="category" class="profile-input @error('category') profile-input--error @enderror" required>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected(old('category', $appointment->category) == $category->id)>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category')
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
                                <label for="barber" class="profile-form-label">Барбер</label>
                                <select id="barber" name="barber" class="profile-input @error('barber') profile-input--error @enderror" required>
                                    @foreach ($barbers as $barber)
                                        <option value="{{ $barber->user?->id }}" @selected(old('barber', $appointment->barber_id) == $barber->user?->id)>
                                            {{ trim(($barber->user?->surname ?? '') . ' ' . ($barber->user?->name ?? '')) ?: $barber->user?->email }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('barber')
                                    <p class="profile-input-message profile-input-message--error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="profile-form-group">
                                <label for="status" class="profile-form-label">Статус записи</label>
                                <select id="status" name="status" class="profile-input @error('status') profile-input--error @enderror" required>
                                    @foreach ($statusOptions as $status => $label)
                                        <option value="{{ $status }}" @selected(old('status', $appointment->status) === $status)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <p class="profile-input-message profile-input-message--error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="profile-form-group">
                                <label for="body" class="profile-form-label">Комментарий</label>
                                <textarea id="body" name="body" rows="4" class="profile-input profile-input--textarea @error('body') profile-input--error @enderror" placeholder="{{ __('Добавьте пожелания или уточнения') }}">{{ old('body', $appointment->body) }}</textarea>
                                @error('body')
                                    <p class="profile-input-message profile-input-message--error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="profile-form-actions">
                                <a href="{{ route('profile.appointments.index') }}" class="profile-submit profile-submit--secondary">{{ __('Вернуться к списку') }}</a>
                                <button type="submit" class="profile-submit">{{ __('Сохранить изменения') }}</button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
