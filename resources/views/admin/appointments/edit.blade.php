@extends('layouts.main')

@section('content')
    <section class="admin-appointments-section">
        <div class="wrapper">
            <div class="admin-appointments-header">
                <h1>Редактирование записи</h1>
                <p>Обновите данные выбранной записи: контактные данные клиента, время приёма и статус.</p>
            </div>

            <div class="admin-appointments-form-card">
                <form method="POST" action="{{ route('admin.appointments.update', $event) }}" class="admin-appointments-form">
                    @csrf
                    @method('PUT')

                    @if ($errors->any())
                        <div class="admin-appointments-alert admin-appointments-alert--error">
                            <p class="admin-appointments-alert-title">Проверьте введённые данные:</p>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="admin-appointments-grid">
                        <label class="admin-appointments-field">
                            <span class="admin-appointments-label">Имя клиента</span>
                            <input type="text" name="subject" value="{{ old('subject', $event->subject) }}" required class="admin-appointments-input">
                        </label>

                        <label class="admin-appointments-field">
                            <span class="admin-appointments-label">Номер телефона</span>
                            <input type="text" name="number" value="{{ old('number', $event->number) }}" required class="admin-appointments-input">
                        </label>

                        <label class="admin-appointments-field">
                            <span class="admin-appointments-label">Услуга</span>
                            <select name="category" class="admin-appointments-input" required>
                                @foreach ($categories as $id => $name)
                                    @php
                                        $isSelectedCategory = (int) old('category', (int) $event->category) === (int) $id;
                                    @endphp
                                    <option value="{{ $id }}" @if($isSelectedCategory) selected @endif>{{ $name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="admin-appointments-field">
                            <span class="admin-appointments-label">Барбер</span>
                            <select name="barber_id" class="admin-appointments-input" required>
                                @foreach ($barbers as $barber)
                                    @php
                                        $barberName = trim(collect([$barber->surname, $barber->name, $barber->patronymic])->filter()->join(' '));
                                        $isSelectedBarber = (int) old('barber_id', $event->barber_id) === (int) $barber->id;
                                    @endphp
                                    <option value="{{ $barber->id }}" @if($isSelectedBarber) selected @endif>
                                        {{ $barberName }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="admin-appointments-field">
                            <span class="admin-appointments-label">Дата</span>
                            <input type="date" name="start_date" value="{{ old('start_date', optional($event->start)->format('Y-m-d')) }}" required class="admin-appointments-input">
                        </label>

                        <label class="admin-appointments-field">
                            <span class="admin-appointments-label">Время начала</span>
                            <input type="time" name="start_time" value="{{ old('start_time', optional($event->start)->format('H:i')) }}" required class="admin-appointments-input">
                        </label>

                        <label class="admin-appointments-field admin-appointments-field--wide">
                            <span class="admin-appointments-label">Примечание</span>
                            <textarea name="body" rows="3" class="admin-appointments-input">{{ old('body', $event->body) }}</textarea>
                        </label>

                        <label class="admin-appointments-field">
                            <span class="admin-appointments-label">Статус</span>
                            <select name="status" class="admin-appointments-input" required>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $event->status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <div class="admin-appointments-actions-bar">
                        <a href="{{ route('admin.appointments.index') }}" class="btn-filament-outline">Вернуться</a>
                        <button type="submit" class="btn-filament-primary">Сохранить изменения</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
