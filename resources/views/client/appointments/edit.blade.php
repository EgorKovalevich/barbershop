@extends('layouts.main')

@section('content')
    <div class="profile-dashboard client-appointments-dashboard">
        <div class="profile-hero">
            <div class="wrapper">
                <div class="profile-hero-inner">
                    <div class="profile-hero-text">
                        <span class="profile-hero-label">Редактирование записи</span>
                        <h1 class="profile-hero-title">Обновить данные приёма</h1>
                        <p class="profile-hero-description">
                            Измените дату, время или комментарий для встречи. Мы сохраним обновления сразу после подтверждения.
                        </p>
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

            <div class="client-appointment-edit-card profile-card">
                <form method="POST" action="{{ route('client.appointments.update', $event) }}" class="profile-form client-appointment-form">
                    @csrf
                    @method('PUT')

                    <div class="profile-form-group">
                        <label for="subject" class="profile-form-label">Имя, на кого оформлена запись</label>
                        <input id="subject" type="text" name="subject" value="{{ old('subject', $event->subject) }}" class="profile-input @error('subject') profile-input--error @enderror" required>
                        @error('subject')
                            <p class="profile-input-message profile-input-message--error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="profile-form-group">
                        <label for="number" class="profile-form-label">Контактный телефон</label>
                        <input id="number" type="text" name="number" value="{{ old('number', $event->number) }}" class="profile-input @error('number') profile-input--error @enderror" required>
                        @error('number')
                            <p class="profile-input-message profile-input-message--error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="profile-form-group">
                        <label for="category" class="profile-form-label">Выбранная услуга</label>
                        <select id="category" name="category" class="profile-input @error('category') profile-input--error @enderror" required>
                            @foreach ($categories as $id => $name)
                                <option value="{{ $id }}" @selected((int) old('category', (int) $event->category) === (int) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('category')
                            <p class="profile-input-message profile-input-message--error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="profile-form-group">
                        <label for="barber_id" class="profile-form-label">Барбер</label>
                        <select id="barber_id" name="barber_id" class="profile-input @error('barber_id') profile-input--error @enderror" required>
                            @foreach ($barbers as $barber)
                                @php
                                    $name = trim(collect([$barber->surname, $barber->name, $barber->patronymic])->filter()->join(' '));
                                @endphp
                                <option value="{{ $barber->id }}" @selected((int) old('barber_id', $event->barber_id) === (int) $barber->id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('barber_id')
                            <p class="profile-input-message profile-input-message--error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="client-appointment-form-row">
                        <div class="profile-form-group">
                            <label for="start_date" class="profile-form-label">Дата визита</label>
                            <input id="start_date" type="date" name="start_date" value="{{ old('start_date', optional($event->start)->format('Y-m-d')) }}" class="profile-input @error('start_date') profile-input--error @enderror" required>
                            @error('start_date')
                                <p class="profile-input-message profile-input-message--error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="profile-form-group">
                            <label for="start_time" class="profile-form-label">Время</label>
                            <input id="start_time" type="time" name="start_time" value="{{ old('start_time', optional($event->start)->format('H:i')) }}" class="profile-input @error('start_time') profile-input--error @enderror" required>
                            @error('start_time')
                                <p class="profile-input-message profile-input-message--error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="profile-form-group">
                        <label for="body" class="profile-form-label">Комментарий для барбера</label>
                        <textarea id="body" name="body" rows="3" class="profile-input profile-textarea @error('body') profile-input--error @enderror">{{ old('body', $event->body) }}</textarea>
                        @error('body')
                            <p class="profile-input-message profile-input-message--error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="client-appointment-actions">
                        <a href="{{ route('client.appointments.index') }}" class="client-appointment-back-link">Вернуться к списку</a>
                        <button type="submit" class="profile-submit">Сохранить изменения</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
